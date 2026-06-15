import 'dart:async';
import 'dart:io';
import 'dart:ui' show ImageFilter;

import 'package:camera/camera.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/api/api_client.dart';
import '../../core/services/face_verification_service.dart';
import '../../core/utils/face_match_reason_text.dart';

/// Face Scanner Screen — Full screen camera preview with live face detection
/// Used for KYC Step 2 to capture and verify customer face against ID photo
///
/// Features:
/// - Full screen camera preview (like camera package example)
/// - Live face detection with ML Kit
/// - Animated face overlay guide
/// - Quality indicators: face detected, eyes open, centered, smiling
/// - Capture button with haptic feedback
class FaceScannerScreen extends ConsumerStatefulWidget {
  final String customerId;
  final String? idFrontUrl;

  const FaceScannerScreen({
    super.key,
    required this.customerId,
    this.idFrontUrl,
  });

  @override
  ConsumerState<FaceScannerScreen> createState() => _FaceScannerScreenState();
}

/// Scanning phase for smart UI feedback
enum ScanPhase {
  searching, // Looking for face
  detected, // Face found, hold still
  livenessBlink, // Blink detection
  livenessTurn, // Turn head slightly
  capturing, // Taking photo
  success, // Verification complete
  error, // Error state
}

class _FaceScannerScreenState extends ConsumerState<FaceScannerScreen>
    with WidgetsBindingObserver, TickerProviderStateMixin {
  CameraController? _cameraController;
  late final FaceDetector _faceDetector;
  List<CameraDescription> _availableCameras = [];
  bool _isSwitchingCamera = false;

  /// Bumps when switching cameras so stale ML Kit callbacks cannot touch state.
  int _cameraSession = 0;

  bool _isInitialized = false;
  bool _isProcessing = false;

  /// ML Kit is handling a camera frame (separate from [_isProcessing] capture / gallery).
  bool _isFrameProcessing = false;
  bool _verificationPassed = false;
  String? _error;
  FaceVerificationResult? _result;

  // Face detection state
  bool _faceDetected = false;
  bool _eyesOpen = false;
  bool _faceCentered = false;

  // Smart scanning state
  ScanPhase _scanPhase = ScanPhase.searching;
  String? _errorInstruction;

  // Liveness detection
  bool _blinkDetected = false;

  // Animation controller for scan line
  late final AnimationController _scanLineController;

  /// Soft pulse on the face frame when ready to capture.
  late final AnimationController _pulseController;

  // Camera description for proper rotation
  CameraDescription? _cameraDescription;

  /// True after ID front is confirmed on server this session (upload or status check).
  bool _idFrontSyncedThisSession = false;

  /// Modern flow: tips before camera permission + preview.
  bool _preScanAcknowledged = false;

  /// Hold still in [detected] before auto-capture.
  static const Duration _detectedHoldDuration = Duration(milliseconds: 1800);

  /// Let camera settle after stopping ML Kit stream before still capture.
  static const Duration _captureSettleDuration = Duration(milliseconds: 350);

  static const int _maxVerifyAttempts = 3;

  /// Re-take still photo locally when preview stream sees a face but JPEG does not.
  static const int _maxLocalCaptureAttempts = 4;

  /// Auto-capture after user stays in [detected] briefly.
  Timer? _detectedHoldTimer;

  /// Blink: saw eyes closed, then open again.
  bool _eyesClosedForBlink = false;

  /// When [livenessTurn] started (for timeout skip if yaw never reports).
  DateTime? _livenessTurnStartedAt;

  /// Head-yaw liveness satisfied (or timed out).
  bool _turnChallengeMet = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    // Initialize scan line animation
    _scanLineController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat();

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);

    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        enableContours: false,
        enableClassification: true,
        enableTracking: true,
        enableLandmarks: false,
        // Accurate mode: headEulerAngleY is needed for the "turn head" liveness step.
        performanceMode: FaceDetectorMode.accurate,
      ),
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

    Future<void> run() async {
      if (!mounted) return;
      if (state == AppLifecycleState.inactive ||
          state == AppLifecycleState.paused) {
        _detectedHoldTimer?.cancel();
        _detectedHoldTimer = null;
        await _safeStopImageStream();
      } else if (state == AppLifecycleState.resumed) {
        await _startImageStream();
      }
    }

    unawaited(run());
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      _availableCameras = cameras;
      if (_availableCameras.isEmpty) {
        setState(() => _error = 'Hakuna kamera iliyopatikana');
        return;
      }

      _cameraDescription ??= _availableCameras.firstWhere(
        (cam) => cam.lensDirection == CameraLensDirection.front,
        orElse: () => _availableCameras.first,
      );

      await _initializeCameraController(_cameraDescription!);

      if (mounted) {
        setState(() {
          _isInitialized = true;
          _error = null;
        });
        await _startImageStream();
      }
    } on CameraException catch (e) {
      setState(() {
        _error = switch (e.code) {
          'CameraAccessDenied' =>
            'Ruhusa ya kamera imekataliwa. Ruhusu kwenye mipangilio.',
          'CameraAccessDeniedNeverAskAgain' =>
            'Ruhusa ya kamera imekataliwa. Nenda mipangilio kuwezesha.',
          _ => 'Hitilafu ya kamera: ${e.description ?? e.code}',
        };
      });
    } catch (e) {
      setState(() => _error = 'Haiwezi kuanza kamera: $e');
    }
  }

  Future<void> _initializeCameraController(CameraDescription description) async {
    _cameraController = CameraController(
      description,
      ResolutionPreset.high,
      enableAudio: false,
      imageFormatGroup:
          Platform.isAndroid ? ImageFormatGroup.nv21 : ImageFormatGroup.bgra8888,
    );
    await _cameraController!.initialize();
  }

  Future<void> _switchCamera() async {
    if (_isSwitchingCamera || _availableCameras.length < 2) {
      return;
    }

    final current = _cameraDescription;
    if (current == null) {
      return;
    }

    final preferredDirection = current.lensDirection == CameraLensDirection.front
        ? CameraLensDirection.back
        : CameraLensDirection.front;

    final next = _availableCameras.firstWhere(
      (camera) => camera.lensDirection == preferredDirection,
      orElse: () {
        final currentIndex = _availableCameras.indexWhere(
          (camera) => camera.name == current.name,
        );
        final nextIndex = (currentIndex + 1) % _availableCameras.length;
        return _availableCameras[nextIndex];
      },
    );

    _isSwitchingCamera = true;
    try {
      await _safeStopImageStream();
      _cameraSession++;
      await _cameraController?.dispose();
      _cameraDescription = next;

      if (mounted) {
        setState(() {
          _isInitialized = false;
          _error = null;
        });
      }

      await _initializeCameraController(next);

      if (mounted) {
        setState(() {
          _isInitialized = true;
          _error = null;
        });
        await _startImageStream();
      }
    } on CameraException catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Hitilafu ya kubadili kamera: ${e.description ?? e.code}';
        });
      }
    } finally {
      _isSwitchingCamera = false;
    }
  }

  Future<void> _safeStopImageStream() async {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;
    if (!controller.value.isStreamingImages) return;
    try {
      await controller.stopImageStream();
    } on CameraException catch (_) {
      // State mismatch with platform; safe to ignore.
    }
  }

  Future<void> _startImageStream() async {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;
    if (!mounted) return;
    if (controller.value.isStreamingImages) return;

    try {
      await controller.startImageStream(_processImage);
    } on CameraException catch (e) {
      if (!mounted) return;
      final msg = e.description ?? e.code;
      if (msg.contains('streaming images') &&
          msg.contains('started streaming')) {
        return;
      }
      setState(() {
        _error = 'Hitilafu ya kamera: $msg';
      });
    }
  }

  void _processImage(CameraImage image) {
    final session = _cameraSession;
    if (_isFrameProcessing || _cameraDescription == null || !mounted) return;

    _isFrameProcessing = true;

    final inputImage = _convertToInputImage(image);
    if (inputImage == null) {
      _isFrameProcessing = false;
      return;
    }

    _faceDetector.processImage(inputImage).then((faces) {
      if (!mounted || session != _cameraSession) return;

      setState(() {
        _faceDetected = faces.length == 1;

        if (faces.length > 1) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Nyuso zaidi ya moja zimeonekana. Kuwa pekee.';
          return;
        }

        if (faces.isEmpty) {
          _scanPhase = ScanPhase.searching;
          _errorInstruction = null;
          return;
        }

        final face = faces.first;

        final leftEye = face.leftEyeOpenProbability ?? 0.0;
        final rightEye = face.rightEyeOpenProbability ?? 0.0;
        _eyesOpen = leftEye > 0.5 && rightEye > 0.5;

        final centerX = face.boundingBox.center.dx;
        final frameWidth = image.width.toDouble();
        _faceCentered =
            centerX >= frameWidth * 0.2 && centerX <= frameWidth * 0.8;

        final faceArea = face.boundingBox.width * face.boundingBox.height;
        final frameArea = frameWidth * image.height.toDouble();
        final faceRatio = faceArea / frameArea;

        if (!_faceCentered) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Leta uso katikati ya frame.';
          return;
        }
        if (faceRatio < 0.05) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Uso uko mbali sana. Karibia kidogo.';
          return;
        }
        if (faceRatio > 0.4) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Uso uko karibu sana. Kaa mbali kidogo.';
          return;
        }

        // During blink step only, allow eyes closed without "open your eyes" error.
        if (!_eyesOpen && _scanPhase != ScanPhase.livenessBlink) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Fungua macho yako.';
          return;
        }

        // --- Liveness: blink → turn → ready (auto-capture) ---
        if (!_blinkDetected) {
          _scanPhase = ScanPhase.livenessBlink;
          _errorInstruction = null;
          _livenessTurnStartedAt = null;
          _turnChallengeMet = false;

          final leftClosed = leftEye < 0.28;
          final rightClosed = rightEye < 0.28;
          if (leftClosed && rightClosed) {
            _eyesClosedForBlink = true;
          }
          if (_eyesClosedForBlink && leftEye > 0.55 && rightEye > 0.55) {
            _blinkDetected = true;
            _eyesClosedForBlink = false;
          }
          return;
        }

        if (!_turnChallengeMet) {
          _scanPhase = ScanPhase.livenessTurn;
          _errorInstruction = null;
          _livenessTurnStartedAt ??= DateTime.now();

          final yaw = face.headEulerAngleY;
          final timedOut = DateTime.now().difference(_livenessTurnStartedAt!) >
              const Duration(seconds: 8);
          if (yaw != null && yaw.abs() >= 12) {
            _turnChallengeMet = true;
          } else if (timedOut) {
            _turnChallengeMet = true;
          } else {
            return;
          }
        }

        _scanPhase = ScanPhase.detected;
        _errorInstruction = null;
      });
    }).catchError((_) {
      // Silently ignore errors
    }).whenComplete(() {
      if (!mounted || session != _cameraSession) {
        return;
      }

      _isFrameProcessing = false;

      if (_scanPhase != ScanPhase.detected || _result != null || _error != null) {
        _detectedHoldTimer?.cancel();
        _detectedHoldTimer = null;
      } else {
        _detectedHoldTimer ??= Timer(_detectedHoldDuration, () {
          if (!mounted || session != _cameraSession) {
            return;
          }
          if (_scanPhase != ScanPhase.detected || _result != null || _error != null) {
            return;
          }
          unawaited(_captureAndVerify());
        });
      }

      // Note: [_isProcessing] is reserved for capture / gallery UX.
    });
  }

  InputImage? _convertToInputImage(CameraImage image) {
    final camera = _cameraDescription;
    if (camera == null) return null;

    final rotation = InputImageRotationValue.fromRawValue(
      camera.sensorOrientation,
    );
    if (rotation == null) return null;

    final format = InputImageFormatValue.fromRawValue(image.format.raw);
    if (format == null) return null;

    final plane = image.planes.first;
    return InputImage.fromBytes(
      bytes: plane.bytes,
      metadata: InputImageMetadata(
        size: Size(image.width.toDouble(), image.height.toDouble()),
        rotation: rotation,
        format: format,
        bytesPerRow: plane.bytesPerRow,
      ),
    );
  }

  /// Ensures [id_front_photo_path] exists on the server before face verify.
  /// Local file paths from Step 2 are uploaded via the dedicated ID-photo API.
  Future<String?> _ensureIdFrontOnServer() async {
    if (_idFrontSyncedThisSession) {
      return null;
    }

    final raw = widget.idFrontUrl?.trim();
    if (raw == null || raw.isEmpty) {
      return 'Pakia picha ya mbele ya kitambulisho kwanza kwenye hatua ya 2.';
    }

    final decoded = Uri.decodeFull(raw);

    if (decoded.startsWith('http://') || decoded.startsWith('https://')) {
      try {
        final status =
            await FaceVerificationService.instance.getStatus(widget.customerId);
        if (!status.hasIdFront) {
          return 'Picha ya ID bado haijahifadhiwa kwenye seva. Gusa Endelea kwenye hatua ya kitambulisho kisha rudi hapa.';
        }
        _idFrontSyncedThisSession = true;
        return null;
      } on DioException catch (e) {
        return ApiClient.instance.parseError(e);
      }
    }

    final file = File(decoded);
    if (!await file.exists()) {
      return 'Faili la picha ya ID halipatikani. Chagua tena picha ya mbele ya kitambulisho.';
    }

    try {
      await FaceVerificationService.instance.uploadIdPhoto(
        widget.customerId,
        file,
      );
      _idFrontSyncedThisSession = true;
      return null;
    } on DioException catch (e) {
      return ApiClient.instance.parseError(e);
    }
  }

  Future<bool> _stillPhotoHasUsableFace(String path) async {
    try {
      final faces = await _faceDetector.processImage(
        InputImage.fromFilePath(path),
      );
      return faces.length == 1;
    } catch (_) {
      return false;
    }
  }

  /// Capture a JPEG that ML Kit can read (preview stream and still photo often differ).
  Future<File?> _captureStillWithFace(CameraController controller) async {
    for (var attempt = 1; attempt <= _maxLocalCaptureAttempts; attempt++) {
      final image = await controller.takePicture();

      if (await _stillPhotoHasUsableFace(image.path)) {
        return File(image.path);
      }

      if (mounted) {
        setState(() {
          _scanPhase = ScanPhase.capturing;
          _errorInstruction =
              'Picha iliyochukuliwa haikuwa wazi. Tunapiga tena ($attempt/$_maxLocalCaptureAttempts)...';
        });
      }

      if (attempt < _maxLocalCaptureAttempts) {
        await Future<void>.delayed(const Duration(milliseconds: 450));
      }
    }

    return null;
  }

  Future<void> _captureAndVerify() async {
    _detectedHoldTimer?.cancel();
    _detectedHoldTimer = null;

    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) {
      return;
    }

    setState(() {
      _isProcessing = true;
      _error = null;
      _scanPhase = ScanPhase.capturing;
      _errorInstruction = 'Inachukua picha... simama bila kusogea.';
    });

    try {
      final syncErr = await _ensureIdFrontOnServer();
      if (syncErr != null) {
        if (mounted) {
          setState(() {
            _error = syncErr;
            _isProcessing = false;
          });
          await _startImageStream();
        }
        return;
      }

      await _safeStopImageStream();
      await Future<void>.delayed(_captureSettleDuration);

      FaceVerificationResult? lastResult;

      for (var attempt = 1; attempt <= _maxVerifyAttempts; attempt++) {
        if (attempt > 1) {
          await Future<void>.delayed(const Duration(milliseconds: 700));
        }

        final file = await _captureStillWithFace(controller);
        if (file == null) {
          if (mounted) {
            setState(() {
              _errorInstruction =
                  'Picha iliyochukuliwa haikuonyesha uso wazi. Simama bila kusogea na ujaribu tena.';
              _scanPhase = ScanPhase.error;
            });
          }
          break;
        }

        lastResult = await FaceVerificationService.instance.verifyFace(
          widget.customerId,
          file,
        );

        final shouldRetry = attempt < _maxVerifyAttempts &&
            _shouldAutoRetryVerification(lastResult);

        if (!shouldRetry) {
          break;
        }

        if (mounted) {
          setState(() {
            _errorInstruction =
                'Picha haijatosi. Tunajaribu tena ($attempt/$_maxVerifyAttempts)...';
          });
        }
      }

      if (!mounted || lastResult == null) {
        if (mounted && lastResult == null) {
          setState(() {
            _isProcessing = false;
          });
          await _startImageStream();
        }
        return;
      }

      setState(() {
        _result = lastResult;
        _verificationPassed = lastResult!.isFaceStepComplete;
        _isProcessing = false;
        _errorInstruction = null;
      });
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = ApiClient.instance.parseError(e);
          _isProcessing = false;
        });
        await _startImageStream();
      }
    } on CameraException catch (e) {
      if (mounted) {
        setState(() {
          _error = e.description ?? e.code;
          _isProcessing = false;
        });
        await _startImageStream();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isProcessing = false;
        });
        await _startImageStream();
      }
    }
  }

  bool _shouldAutoRetryVerification(FaceVerificationResult result) {
    if (result.isFaceStepComplete) {
      return false;
    }

    final reason = (result.reason ?? '').toLowerCase();

    if (reason.contains('unreachable') ||
        reason.contains('internal_error') ||
        reason.contains('internal error') ||
        reason.contains('not configured')) {
      return true;
    }

    if (reason.contains('blurry') ||
        reason.contains('too_dark') ||
        reason.contains('too_bright') ||
        reason.contains('too_small') ||
        reason.contains('multiple_faces') ||
        reason.contains('invalid_image') ||
        reason.contains('no_face_detected') ||
        reason.contains('headshot:')) {
      return true;
    }

    return result.isReviewBand && reason.isNotEmpty;
  }

  Future<void> _pickFromGallery() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1024,
      maxHeight: 1024,
      imageQuality: 85,
    );

    if (picked == null) return;

    setState(() {
      _isProcessing = true;
      _error = null;
    });

    try {
      final syncErr = await _ensureIdFrontOnServer();
      if (syncErr != null) {
        if (mounted) {
          setState(() {
            _error = syncErr;
            _isProcessing = false;
          });
        }
        return;
      }

      final result = await FaceVerificationService.instance.verifyFace(
        widget.customerId,
        File(picked.path),
      );

      if (mounted) {
        setState(() {
          _result = result;
          _verificationPassed = result.isFaceStepComplete;
          _isProcessing = false;
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = ApiClient.instance.parseError(e);
          _isProcessing = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isProcessing = false;
        });
      }
    }
  }

  Future<void> _retry() async {
    await _safeStopImageStream();

    if (!mounted) return;

    setState(() {
      _result = null;
      _verificationPassed = false;
      _error = null;
      _isProcessing = false;
      _isFrameProcessing = false;
      _faceDetected = false;
      _eyesOpen = false;
      _faceCentered = false;
      _scanPhase = ScanPhase.searching;
      _errorInstruction = null;
      _blinkDetected = false;
      _eyesClosedForBlink = false;
      _turnChallengeMet = false;
      _livenessTurnStartedAt = null;
    });

    _detectedHoldTimer?.cancel();
    _detectedHoldTimer = null;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        unawaited(_startImageStream());
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _detectedHoldTimer?.cancel();
    _scanLineController.dispose();
    _pulseController.dispose();
    _cameraController?.dispose();
    _faceDetector.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (!_preScanAcknowledged) {
      return _buildPreScanOnboarding();
    }
    if (_error != null) return _buildError();
    if (_result != null) return _buildResult();
    if (!_isInitialized) return _buildLoading();
    return _buildCameraPreview(context);
  }

  Widget _buildPreScanOnboarding() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [DesignTokens.heroStart, DesignTokens.heroEnd],
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: SingleChildScrollView(
                  physics: const BouncingScrollPhysics(),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      IconButton(
                        onPressed: () => context.pop(false),
                        style: IconButton.styleFrom(
                          foregroundColor: Colors.white,
                          backgroundColor: Colors.white.withValues(alpha: 0.1),
                        ),
                        icon: const Icon(Icons.close_rounded),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Tayari kwa skani ya uso',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: -0.5,
                          height: 1.15,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Tutakusanya selfie ya moja kwa moja na kulinganisha na picha ya kitambulisho.',
                        style: TextStyle(
                          fontSize: 14,
                          height: 1.4,
                          color: Colors.white.withValues(alpha: 0.78),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 20),
                      Center(
                        child: Container(
                          width: 160,
                          height: 190,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(32),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.22),
                              width: 2,
                            ),
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [
                                Colors.white.withValues(alpha: 0.14),
                                Colors.white.withValues(alpha: 0.04),
                              ],
                            ),
                          ),
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              Icon(
                                Icons.face_retouching_natural_rounded,
                                size: 64,
                                color: Colors.white.withValues(alpha: 0.35),
                              ),
                              ..._cornerBrackets(
                                12,
                                Colors.white.withValues(alpha: 0.55),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      _preScanTip(
                        Icons.wb_sunny_outlined,
                        'Mwanga mzuri',
                        'Epuka kivuli kali nyuma ya uso; mwanga wa kawaida wa ndani au nje ni bora.',
                      ),
                      const SizedBox(height: 12),
                      _preScanTip(
                        Icons.person_outline_rounded,
                        'Mtu mmoja',
                        'Hakikisha ni mteja pekee anayeonekana kwenye skrini.',
                      ),
                      const SizedBox(height: 12),
                      _preScanTip(
                        Icons.auto_fix_high_rounded,
                        'Hatua tatu',
                        'Kunyaza macho, kugeuza kichwa kidogo, kisha picha inachukuliwa kiotomatiki.',
                      ),
                      const SizedBox(height: 12),
                      _preScanTip(
                        Icons.verified_user_outlined,
                        'Kiwango cha kupita',
                        'Ulinganisho wa ${AppConstants.faceMatchPassPercent}% au zaidi hupita; ${AppConstants.faceMatchReviewPercent}–${AppConstants.faceMatchPassPercent - 1}% inahitaji ukaguzi wa HQ.',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: AppConstants.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  onPressed: () async {
                    setState(() => _preScanAcknowledged = true);
                    final idSync = _ensureIdFrontOnServer();
                    await _initCamera();
                    await idSync;
                  },
                  child: const Text(
                    'Anza skani',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
              Center(
                child: TextButton(
                  onPressed: () => context.pop(false),
                  child: Text(
                    'Rudi nyuma',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.75),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _cornerBrackets(double inset, Color color) {
    const len = 26.0;
    const thick = 3.0;
    Widget bracket(Alignment align, bool top, bool left) {
      return Align(
        alignment: align,
        child: Container(
          width: len,
          height: len,
          margin: EdgeInsets.all(inset),
          decoration: BoxDecoration(
            border: Border(
              top: top
                  ? BorderSide(color: color, width: thick)
                  : BorderSide.none,
              bottom: !top
                  ? BorderSide(color: color, width: thick)
                  : BorderSide.none,
              left: left
                  ? BorderSide(color: color, width: thick)
                  : BorderSide.none,
              right: !left
                  ? BorderSide(color: color, width: thick)
                  : BorderSide.none,
            ),
          ),
        ),
      );
    }

    return [
      bracket(Alignment.topLeft, true, true),
      bracket(Alignment.topRight, true, false),
      bracket(Alignment.bottomLeft, false, true),
      bracket(Alignment.bottomRight, false, false),
    ];
  }

  Widget _preScanTip(IconData icon, String title, String body) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 46,
          height: 46,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
          ),
          child: Icon(icon, color: AppConstants.primaryLight, size: 24),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                body,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.72),
                  fontSize: 13,
                  height: 1.4,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildLoading() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [DesignTokens.heroStart, DesignTokens.heroEnd],
        ),
      ),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const CircularProgressIndicator(
                color: AppConstants.primary,
                strokeWidth: 3,
              ),
            ),
            const SizedBox(height: 32),
            const Text(
              'Inaanza kamera...',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Tafadhali subiri kidogo',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.7),
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildError() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [DesignTokens.heroStart, DesignTokens.heroEnd],
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(28),
                        decoration: BoxDecoration(
                          color: DesignTokens.error.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: DesignTokens.error.withValues(alpha: 0.3),
                            width: 2,
                          ),
                        ),
                        child: const Icon(
                          Icons.error_outline_rounded,
                          size: 56,
                          color: DesignTokens.error,
                        ),
                      ).animate().scale(
                          duration: 400.ms, curve: Curves.elasticOut),
                      const SizedBox(height: 32),
                      const Text(
                        'Hitilafu imetokea',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        _error!,
                        textAlign: TextAlign.center,
                        softWrap: true,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.7),
                          fontSize: 15,
                          height: 1.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),
              _buildActionButton(
                label: 'Jaribu Tena',
                icon: Icons.refresh_rounded,
                onPressed: () => unawaited(_retry()),
                isPrimary: true,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Rect _faceGuideRect(Size screen) {
    final w = screen.width * 0.72;
    final h = w * 1.18;
    return Rect.fromCenter(
      center: Offset(screen.width / 2, screen.height * 0.34),
      width: w,
      height: h,
    );
  }

  int get _activeLivenessStep {
    if (_scanPhase == ScanPhase.detected || _scanPhase == ScanPhase.capturing) {
      return 3;
    }
    if (_scanPhase == ScanPhase.livenessTurn) {
      return _turnChallengeMet ? 3 : 2;
    }
    if (_scanPhase == ScanPhase.livenessBlink) {
      return _blinkDetected ? 2 : 1;
    }
    if (_faceDetected && _faceCentered) {
      return 1;
    }
    return 0;
  }

  Color _ringColorForPhase() {
    if (_scanPhase == ScanPhase.error) {
      return DesignTokens.error;
    }
    if (_scanPhase == ScanPhase.detected) {
      return DesignTokens.success;
    }
    if (_scanPhase == ScanPhase.livenessBlink ||
        _scanPhase == ScanPhase.livenessTurn) {
      return AppConstants.primary;
    }
    if (_faceDetected) {
      return AppConstants.primary.withValues(alpha: 0.85);
    }
    return Colors.white.withValues(alpha: 0.88);
  }

  Widget _buildCameraPreview(BuildContext context) {
    final canCapture = _scanPhase == ScanPhase.detected;
    final size = MediaQuery.sizeOf(context);
    final faceRect = _faceGuideRect(size);
    final ringColor = _ringColorForPhase();
    final isReady = _scanPhase == ScanPhase.detected;
    final showPulse = isReady || _scanPhase == ScanPhase.capturing;

    return Stack(
      fit: StackFit.expand,
      children: [
        CameraPreview(_cameraController!),
        IgnorePointer(
          child: CustomPaint(
            size: size,
            painter: _FaceCutoutDimPainter(
              faceOval: faceRect,
              dimColor: Colors.black.withValues(alpha: 0.62),
            ),
          ),
        ),
        IgnorePointer(
          child: AnimatedBuilder(
            animation: Listenable.merge([
              _pulseController,
              _scanLineController,
            ]),
            builder: (context, _) {
              return CustomPaint(
                size: size,
                painter: _ModernFaceFramePainter(
                  faceOval: faceRect,
                  ringColor: ringColor,
                  pulse: showPulse ? _pulseController.value : 0,
                  isReady: isReady,
                  showScanLine: _scanPhase == ScanPhase.searching ||
                      _scanPhase == ScanPhase.livenessBlink ||
                      _scanPhase == ScanPhase.livenessTurn ||
                      _scanPhase == ScanPhase.error,
                  scanProgress: _scanLineController.value,
                  scanColor: _scanPhase == ScanPhase.error
                      ? DesignTokens.error
                      : AppConstants.primary,
                ),
              );
            },
          ),
        ),
        if (_isProcessing)
          Container(
            color: Colors.black.withValues(alpha: 0.35),
            child: Center(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(24),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 28,
                      vertical: 22,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.16),
                      ),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const SizedBox(
                          width: 36,
                          height: 36,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 3,
                          ),
                        ),
                        const SizedBox(height: 14),
                        Text(
                          _scanPhase == ScanPhase.capturing
                              ? 'Inachukua na kuthibitisha...'
                              : 'Inathibitisha uso...',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          child: _buildTopBar(),
        ),
        Positioned(
          left: 20,
          right: 20,
          bottom: MediaQuery.paddingOf(context).bottom + 200,
          child: IgnorePointer(
            child: _buildFloatingHeroInstruction(),
          ),
        ),
        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          child: _buildSmartStatusPanel(canCapture),
        ),
      ],
    );
  }

  /// Glanceable instruction above the bottom glass panel.
  Widget _buildFloatingHeroInstruction() {
    final (icon, text, color) = _getInstructionForPhase();

    return ClipRRect(
      borderRadius: BorderRadius.circular(22),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
          decoration: BoxDecoration(
            color: Colors.black.withValues(alpha: 0.42),
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: color.withValues(alpha: 0.35)),
          ),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  text,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    height: 1.3,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSmartStatusPanel(bool canCapture) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
            child: Container(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 20),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(28),
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.14),
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  _buildLivenessTimeline(),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      Expanded(
                        child: _buildCaptureButton(canCapture),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _isProcessing
                              ? null
                              : () => unawaited(_pickFromGallery()),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.white,
                            side: BorderSide(
                              color: Colors.white.withValues(alpha: 0.28),
                            ),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          icon: const Icon(Icons.photo_library_outlined, size: 18),
                          label: const Text(
                            'Gallery',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    canCapture
                        ? 'Picha itachukuliwa kiotomatiki — au gusa kitufe cha kamera.'
                        : 'Fuata hatua 4 hapo juu ili kufungua skani.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.55),
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      height: 1.35,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  (IconData, String, Color) _getInstructionForPhase() {
    // If there's an error instruction, show it
    if (_errorInstruction != null) {
      return (
        Icons.error_outline_rounded,
        _errorInstruction!,
        DesignTokens.error
      );
    }

    return switch (_scanPhase) {
      ScanPhase.searching => (
          Icons.search_rounded,
          'Weka uso ndani ya frame.',
          Colors.white
        ),
      ScanPhase.detected => (
          Icons.auto_awesome_rounded,
          'Uso umeonekana — subiri, picha inachukuliwa…',
          DesignTokens.success
        ),
      ScanPhase.livenessBlink => (
          Icons.remove_red_eye_rounded,
          'Kunyaza macho yako.',
          AppConstants.primary
        ),
      ScanPhase.livenessTurn => (
          Icons.rotate_right_rounded,
          'Geuza kichwa kidogo.',
          AppConstants.primary
        ),
      ScanPhase.capturing => (
          Icons.camera_alt_rounded,
          'Inachukua picha...',
          AppConstants.primary
        ),
      ScanPhase.success => (
          Icons.verified_rounded,
          'Uthibitishaji umekamilika!',
          DesignTokens.success
        ),
      ScanPhase.error => (
          Icons.error_outline_rounded,
          _errorInstruction ?? 'Hitilafu imetokea.',
          DesignTokens.error
        ),
    };
  }

  Widget _buildLivenessTimeline() {
    const steps = ['Msimamo', 'Blink', 'Geuza', 'Skani'];
    final active = _activeLivenessStep;

    return Row(
      children: List.generate(steps.length * 2 - 1, (index) {
        if (index.isOdd) {
          final stepIndex = index ~/ 2;
          final done = active > stepIndex;
          return Expanded(
            child: Container(
              height: 2,
              margin: const EdgeInsets.only(bottom: 18),
              decoration: BoxDecoration(
                color: done
                    ? DesignTokens.success
                    : Colors.white.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          );
        }

        final stepIndex = index ~/ 2;
        final done = active > stepIndex;
        final current = active == stepIndex;

        return _buildTimelineStep(
          label: steps[stepIndex],
          done: done,
          current: current,
        );
      }),
    );
  }

  Widget _buildTimelineStep({
    required String label,
    required bool done,
    required bool current,
  }) {
    final color = done
        ? DesignTokens.success
        : current
            ? AppConstants.primaryLight
            : Colors.white.withValues(alpha: 0.35);

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        AnimatedContainer(
          duration: 250.ms,
          width: current ? 34 : 30,
          height: current ? 34 : 30,
          decoration: BoxDecoration(
            color: done
                ? DesignTokens.success
                : current
                    ? AppConstants.primary.withValues(alpha: 0.22)
                    : Colors.white.withValues(alpha: 0.08),
            shape: BoxShape.circle,
            border: Border.all(color: color, width: current ? 2.2 : 1.6),
            boxShadow: current
                ? [
                    BoxShadow(
                      color: AppConstants.primary.withValues(alpha: 0.35),
                      blurRadius: 12,
                    ),
                  ]
                : null,
          ),
          child: Icon(
            done ? Icons.check_rounded : Icons.circle,
            size: done ? 18 : 8,
            color: done ? Colors.white : color,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(
            color: done || current
                ? Colors.white
                : Colors.white.withValues(alpha: 0.45),
            fontSize: 10,
            fontWeight: current ? FontWeight.w800 : FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _buildCaptureButton(bool canCapture) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: canCapture && !_isProcessing ? _captureAndVerify : null,
        borderRadius: BorderRadius.circular(16),
        child: AnimatedContainer(
          duration: 200.ms,
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: canCapture
                ? LinearGradient(
                    colors: [
                      DesignTokens.success,
                      DesignTokens.success.withValues(alpha: 0.82),
                    ],
                  )
                : null,
            color: canCapture ? null : Colors.white.withValues(alpha: 0.08),
            border: Border.all(
              color: canCapture
                  ? DesignTokens.success.withValues(alpha: 0.5)
                  : Colors.white.withValues(alpha: 0.2),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_isProcessing)
                const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2.2,
                  ),
                )
              else
                Icon(
                  Icons.camera_alt_rounded,
                  color: canCapture
                      ? Colors.white
                      : Colors.white.withValues(alpha: 0.45),
                  size: 20,
                ),
              const SizedBox(width: 8),
              Text(
                _isProcessing ? 'Inachakata...' : 'Piga picha',
                style: TextStyle(
                  color: canCapture
                      ? Colors.white
                      : Colors.white.withValues(alpha: 0.45),
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTopBar() {
    final canSwitch = _availableCameras.length > 1;
    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => context.pop(_verificationPassed),
                    child: Container(
                      padding: const EdgeInsets.all(11),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.38),
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.12),
                        ),
                      ),
                      child: const Icon(
                        Icons.close_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                    ),
                  ),
                ),
              ),
            ),
            Expanded(
              child: Center(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: BackdropFilter(
                    filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 9,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.38),
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.14),
                        ),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.face_retouching_natural_rounded,
                            color: AppConstants.primary,
                            size: 18,
                          ),
                          SizedBox(width: 8),
                          Text(
                            'Uthibitishaji wa Uso',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              letterSpacing: 0.2,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
            ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                child: Material(
                  color: Colors.transparent,
                  child: PopupMenuButton<String>(
                    enabled: !_isProcessing,
                    color: Colors.white,
                    icon: Icon(
                      Icons.more_horiz_rounded,
                      color: _isProcessing
                          ? Colors.white.withValues(alpha: 0.35)
                          : Colors.white,
                    ),
                    position: PopupMenuPosition.under,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    onSelected: (value) {
                      if (value == 'gallery') {
                        unawaited(_pickFromGallery());
                      }
                    },
                    itemBuilder: (context) => [
                      const PopupMenuItem<String>(
                        value: 'gallery',
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Icon(Icons.photo_library_outlined),
                          title: Text('Chagua kutoka gallery'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(width: 6),
            if (canSwitch)
              ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: _isSwitchingCamera
                          ? null
                          : () => unawaited(_switchCamera()),
                      child: Container(
                        padding: const EdgeInsets.all(11),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.38),
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.12),
                          ),
                        ),
                        child: Icon(
                          Icons.flip_camera_android_rounded,
                          color: _isSwitchingCamera
                              ? Colors.white.withValues(alpha: 0.55)
                              : Colors.white,
                          size: 22,
                        ),
                      ),
                    ),
                  ),
                ),
              )
            else
              const SizedBox(width: 44),
          ],
        ),
      ),
    );
  }

  Widget _buildResult() {
    final isSuccess = _result!.isFaceStepComplete;
    final isReview = _result!.isReviewBand;
    final color = isSuccess
        ? DesignTokens.success
        : isReview
            ? DesignTokens.warning
            : DesignTokens.error;
    final score = (_result!.score * 100).round();

    final title = isSuccess
        ? 'Uthibitishaji Umefanikiwa!'
        : isReview
            ? 'Imehifadhiwa — ukaguzi wa kati'
            : 'Haikupitisha kiotomatiki';

    final subtitle = isSuccess
        ? 'Uso wako umelinganishwa na picha ya kitambulisho'
        : isReview
            ? 'Alama ni ya kati (si mbaya wala kamili). Msimamizi anaweza kuidhinisha kwa mkono, au jaribu picha nyingine.'
            : 'Tafadhali jaribu tena na picha nyingine au mwanga bora.';

    final resultIcon = isSuccess
        ? Icons.verified_user_rounded
        : isReview
            ? Icons.pending_actions_rounded
            : Icons.error_outline_rounded;

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [DesignTokens.heroStart, DesignTokens.heroEnd],
        ),
      ),
      child: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Result icon with animation
                Container(
                  padding: const EdgeInsets.all(32),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: color.withValues(alpha: 0.3),
                      width: 3,
                    ),
                  ),
                  child: Icon(
                    resultIcon,
                    size: 72,
                    color: color,
                  ),
                )
                    .animate()
                    .scale(duration: 500.ms, curve: Curves.elasticOut)
                    .fadeIn(),

                const SizedBox(height: 40),

                // Status text
                Text(
                  title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                  ),
                ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1, end: 0),

                const SizedBox(height: 12),

                Text(
                  subtitle,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.7),
                    fontSize: 15,
                  ),
                ),

                const SizedBox(height: 28),

                SizedBox(
                  width: 132,
                  height: 132,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      SizedBox(
                        width: 132,
                        height: 132,
                        child: CircularProgressIndicator(
                          value: score / 100,
                          strokeWidth: 8,
                          backgroundColor: Colors.white.withValues(alpha: 0.12),
                          color: color,
                          strokeCap: StrokeCap.round,
                        ),
                      ),
                      Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            '$score',
                            style: TextStyle(
                              color: color,
                              fontSize: 36,
                              fontWeight: FontWeight.w900,
                              height: 1,
                            ),
                          ),
                          Text(
                            '%',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.55),
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1, end: 0),

                const SizedBox(height: 10),
                Text(
                  'Alama ya ulinganisho',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.55),
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),

                if (_result!.reason != null && !isSuccess) ...[
                  const SizedBox(height: 20),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: color.withValues(alpha: 0.35),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.info_outline_rounded,
                          color: color,
                          size: 20,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            localizedFaceMatchReason(_result!.reason!),
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.8),
                              fontSize: 13,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 48),

                // Action buttons
                if (isSuccess)
                  _buildActionButton(
                    label: 'Endelea',
                    icon: Icons.arrow_forward_rounded,
                    onPressed: () => context.pop(true),
                    isPrimary: true,
                    color: DesignTokens.success,
                  )
                else
                  Row(
                    children: [
                      Expanded(
                        child: _buildActionButton(
                          label: 'Jaribu Tena',
                          icon: Icons.refresh_rounded,
                          onPressed: () => unawaited(_retry()),
                          isPrimary: true,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _buildActionButton(
                          label: 'Rudi',
                          icon: Icons.arrow_back_rounded,
                          onPressed: () => context.pop(false),
                          isPrimary: false,
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildActionButton({
    required String label,
    required IconData icon,
    VoidCallback? onPressed,
    bool isPrimary = true,
    Color? color,
  }) {
    final bgColor = color ?? AppConstants.primary;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(24),
        child: AnimatedContainer(
          duration: 200.ms,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            color: isPrimary ? bgColor : Colors.white.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(24),
            border: isPrimary
                ? null
                : Border.all(
                    color: Colors.white.withValues(alpha: 0.2),
                  ),
            boxShadow: isPrimary
                ? [
                    BoxShadow(
                      color: bgColor.withValues(alpha: 0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 8),
                    ),
                  ]
                : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.max,
            children: [
              Icon(
                icon,
                color: Colors.white,
                size: 20,
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Text(
                  label,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Dims the full frame except a clear face oval (modern ID-scan style mask).
class _FaceCutoutDimPainter extends CustomPainter {
  final Rect faceOval;
  final Color dimColor;

  _FaceCutoutDimPainter({
    required this.faceOval,
    required this.dimColor,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final outer = Path()
      ..addRect(Rect.fromLTWH(0, 0, size.width, size.height));
    final inner = Path()..addOval(faceOval);
    final cut = Path.combine(PathOperation.difference, outer, inner);
    canvas.drawPath(cut, Paint()..color = dimColor);
  }

  @override
  bool shouldRepaint(covariant _FaceCutoutDimPainter oldDelegate) {
    return oldDelegate.faceOval != faceOval || oldDelegate.dimColor != dimColor;
  }
}

/// Modern ID-scan frame: corner brackets, glow, and scan beam inside the oval.
class _ModernFaceFramePainter extends CustomPainter {
  final Rect faceOval;
  final Color ringColor;
  final double pulse;
  final bool isReady;
  final bool showScanLine;
  final double scanProgress;
  final Color scanColor;

  _ModernFaceFramePainter({
    required this.faceOval,
    required this.ringColor,
    required this.pulse,
    required this.isReady,
    required this.showScanLine,
    required this.scanProgress,
    required this.scanColor,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final expand = pulse * 6;
    final oval = faceOval.inflate(expand);

    final glow = Paint()
      ..color = ringColor.withValues(alpha: 0.18 + pulse * 0.12)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 12
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 12);
    canvas.drawOval(oval, glow);

    canvas.drawOval(
      oval,
      Paint()
        ..color = ringColor.withValues(alpha: isReady ? 0.95 : 0.75)
        ..style = PaintingStyle.stroke
        ..strokeWidth = isReady ? 2.8 : 2,
    );

    _drawCornerBrackets(canvas, oval, ringColor, isReady ? 3 : 2.2);

    if (showScanLine) {
      final y = oval.top + 12 + scanProgress * (oval.height - 24);
      final linePaint = Paint()
        ..shader = LinearGradient(
          colors: [
            scanColor.withValues(alpha: 0),
            scanColor.withValues(alpha: 0.85),
            scanColor.withValues(alpha: 0),
          ],
        ).createShader(Rect.fromLTWH(oval.left, y, oval.width, 2))
        ..strokeWidth = 2.5
        ..strokeCap = StrokeCap.round;
      canvas.drawLine(
        Offset(oval.left + 12, y),
        Offset(oval.right - 12, y),
        linePaint,
      );
    }
  }

  void _drawCornerBrackets(
    Canvas canvas,
    Rect rect,
    Color color,
    double stroke,
  ) {
    const len = 28.0;
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeCap = StrokeCap.round;

    void corner(Offset start, Offset hEnd, Offset vEnd) {
      canvas.drawLine(start, hEnd, paint);
      canvas.drawLine(start, vEnd, paint);
    }

    corner(
      Offset(rect.left, rect.top + len),
      Offset(rect.left, rect.top),
      Offset(rect.left + len, rect.top),
    );
    corner(
      Offset(rect.right - len, rect.top),
      Offset(rect.right, rect.top),
      Offset(rect.right, rect.top + len),
    );
    corner(
      Offset(rect.left, rect.bottom - len),
      Offset(rect.left, rect.bottom),
      Offset(rect.left + len, rect.bottom),
    );
    corner(
      Offset(rect.right - len, rect.bottom),
      Offset(rect.right, rect.bottom),
      Offset(rect.right, rect.bottom - len),
    );
  }

  @override
  bool shouldRepaint(covariant _ModernFaceFramePainter oldDelegate) {
    return oldDelegate.faceOval != faceOval ||
        oldDelegate.ringColor != ringColor ||
        oldDelegate.pulse != pulse ||
        oldDelegate.isReady != isReady ||
        oldDelegate.showScanLine != showScanLine ||
        oldDelegate.scanProgress != scanProgress;
  }
}
