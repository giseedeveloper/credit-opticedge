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
    with WidgetsBindingObserver, SingleTickerProviderStateMixin {
  CameraController? _cameraController;
  late final FaceDetector _faceDetector;
  List<CameraDescription> _availableCameras = [];
  bool _isSwitchingCamera = false;

  /// Bumps when switching cameras so stale ML Kit callbacks cannot touch state.
  int _cameraSession = 0;

  bool _isInitialized = false;
  bool _isProcessing = false;
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
  bool _previousEyesOpen = true;
  bool _blinkDetected = false;
  int _blinkCount = 0;
  DateTime? _lastBlinkTime;

  // Animation controller for scan line
  late final AnimationController _scanLineController;

  // Camera description for proper rotation
  CameraDescription? _cameraDescription;

  /// True after ID front is confirmed on server this session (upload or status check).
  bool _idFrontSyncedThisSession = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    // Initialize scan line animation
    _scanLineController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat();

    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        enableContours: false,
        enableClassification: true,
        enableTracking: true,
        enableLandmarks: false,
        performanceMode: FaceDetectorMode.fast,
      ),
    );

    _initCamera();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

    Future<void> run() async {
      if (!mounted) return;
      if (state == AppLifecycleState.inactive ||
          state == AppLifecycleState.paused) {
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
    if (_isProcessing || _cameraDescription == null || !mounted) return;

    _isProcessing = true;

    final inputImage = _convertToInputImage(image);
    if (inputImage == null) {
      _isProcessing = false;
      return;
    }

    _faceDetector.processImage(inputImage).then((faces) {
      if (!mounted || session != _cameraSession) return;

      setState(() {
        _faceDetected = faces.length == 1;

        // Error: multiple faces
        if (faces.length > 1) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Nyuso zaidi ya moja zimeonekana. Kuwa pekee.';
          return;
        }

        // Error: no face
        if (faces.isEmpty) {
          _scanPhase = ScanPhase.searching;
          _errorInstruction = null;
          return;
        }

        final face = faces.first;

        // Eye open probability
        final leftEye = face.leftEyeOpenProbability ?? 0.0;
        final rightEye = face.rightEyeOpenProbability ?? 0.0;
        _eyesOpen = leftEye > 0.5 && rightEye > 0.5;

        // Blink detection for liveness
        final eyesJustClosed = _previousEyesOpen && !_eyesOpen;
        final eyesJustOpened = !_previousEyesOpen && _eyesOpen;

        if (eyesJustClosed || eyesJustOpened) {
          final now = DateTime.now();
          if (_lastBlinkTime != null &&
              now.difference(_lastBlinkTime!).inMilliseconds < 500) {
            _blinkCount++;
            if (_blinkCount >= 2) {
              _blinkDetected = true;
            }
          }
          _lastBlinkTime = now;
        }
        _previousEyesOpen = _eyesOpen;

        // Center check (face should be in center 60% of frame)
        final centerX = face.boundingBox.center.dx;
        final frameWidth = image.width.toDouble();
        _faceCentered =
            centerX >= frameWidth * 0.2 && centerX <= frameWidth * 0.8;

        // Face size check (too far or too close)
        final faceArea = face.boundingBox.width * face.boundingBox.height;
        final frameArea = frameWidth * image.height.toDouble();
        final faceRatio = faceArea / frameArea;

        // Smart phase transitions
        if (!_faceCentered) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Leta uso katikati ya frame.';
        } else if (faceRatio < 0.05) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Uso uko mbali sana. Karibia kidogo.';
        } else if (faceRatio > 0.4) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Uso uko karibu sana. Kaa mbali kidogo.';
        } else if (!_eyesOpen && _scanPhase != ScanPhase.livenessBlink) {
          _scanPhase = ScanPhase.error;
          _errorInstruction = 'Fungua macho yako.';
        } else if (_faceDetected && _faceCentered && _eyesOpen) {
          // Good position - check liveness
          if (!_blinkDetected) {
            _scanPhase = ScanPhase.livenessBlink;
            _errorInstruction = null;
          } else {
            _scanPhase = ScanPhase.detected;
            _errorInstruction = null;
          }
        }
      });
    }).catchError((_) {
      // Silently ignore errors
    }).whenComplete(() {
      if (mounted && session == _cameraSession) {
        _isProcessing = false;
      }
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

  Future<void> _captureAndVerify() async {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

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
          await _startImageStream();
        }
        return;
      }

      await _safeStopImageStream();

      final image = await controller.takePicture();
      final file = File(image.path);

      final result = await FaceVerificationService.instance.verifyFace(
        widget.customerId,
        file,
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
      _faceDetected = false;
      _eyesOpen = false;
      _faceCentered = false;
      // Reset smart scanning state
      _scanPhase = ScanPhase.searching;
      _errorInstruction = null;
      _blinkDetected = false;
      _blinkCount = 0;
      _previousEyesOpen = true;
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        unawaited(_startImageStream());
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _scanLineController.dispose();
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
    if (_error != null) return _buildError();
    if (_result != null) return _buildResult();
    if (!_isInitialized) return _buildLoading();
    return _buildCameraPreview(context);
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
    final w = screen.width * 0.68;
    final h = w * 1.22;
    return Rect.fromCenter(
      center: Offset(screen.width / 2, screen.height * 0.36),
      width: w,
      height: h,
    );
  }

  Color _ringColorForPhase() {
    final isReady = _scanPhase == ScanPhase.detected;
    final isError = _scanPhase == ScanPhase.error;
    if (isError) {
      return DesignTokens.error;
    }
    if (isReady) {
      return DesignTokens.success;
    }
    if (_faceDetected) {
      return AppConstants.primary;
    }
    return Colors.white.withValues(alpha: 0.88);
  }

  Widget _buildCameraPreview(BuildContext context) {
    final canCapture = _scanPhase == ScanPhase.detected ||
        _scanPhase == ScanPhase.livenessBlink;
    final size = MediaQuery.sizeOf(context);
    final faceRect = _faceGuideRect(size);
    final ringColor = _ringColorForPhase();
    final isReady = _scanPhase == ScanPhase.detected;

    return Stack(
      fit: StackFit.expand,
      children: [
        CameraPreview(_cameraController!),
        IgnorePointer(
          child: CustomPaint(
            size: size,
            painter: _FaceCutoutDimPainter(
              faceOval: faceRect,
              dimColor: Colors.black.withValues(alpha: 0.55),
            ),
          ),
        ),
        IgnorePointer(
          child: CustomPaint(
            size: size,
            painter: _FaceGuideRingPainter(
              faceOval: faceRect,
              ringColor: ringColor,
              strokeWidth: isReady ? 3 : 2,
            ),
          ),
        ),
        if (_scanPhase == ScanPhase.searching ||
            _scanPhase == ScanPhase.detected)
          _buildAnimatedScanLine(faceRect),
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          child: _buildTopBar(),
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

  Widget _buildAnimatedScanLine(Rect faceRect) {
    return Positioned.fill(
      child: IgnorePointer(
        child: AnimatedBuilder(
          animation: _scanLineController,
          builder: (context, child) {
            final progress = _scanLineController.value;
            final yPosition =
                faceRect.top + 16 + progress * (faceRect.height - 32);

            return CustomPaint(
              painter: _ScanLinePainter(
                position: yPosition,
                color: _scanPhase == ScanPhase.detected
                    ? DesignTokens.success.withValues(alpha: 0.75)
                    : AppConstants.primary.withValues(alpha: 0.75),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildSmartStatusPanel(bool canCapture) {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.bottomCenter,
            end: Alignment.topCenter,
            colors: [
              Colors.black.withValues(alpha: 0.82),
              Colors.black.withValues(alpha: 0.45),
              Colors.transparent,
            ],
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Smart instruction text
            _buildSmartInstruction(),

            const SizedBox(height: 24),

            // Progress indicators
            _buildProgressIndicators(),

            const SizedBox(height: 24),

            // Capture button row
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Gallery button
                _buildCircleButton(
                  icon: Icons.photo_library_rounded,
                  onPressed: _isProcessing ? null : _pickFromGallery,
                ),

                const SizedBox(width: 24),

                // Capture button
                _buildCaptureButton(canCapture),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSmartInstruction() {
    final (icon, text, color) = _getInstructionForPhase();

    return AnimatedContainer(
      duration: 300.ms,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(
          color: color.withValues(alpha: 0.3),
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Flexible(
            child: Text(
              text,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
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
          Icons.check_circle_rounded,
          'Kaa bila kutikisika.',
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

  Widget _buildProgressIndicators() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _buildProgressStep('Uso', _faceDetected, Icons.face_rounded),
        _buildProgressConnector(_faceDetected),
        _buildProgressStep('Macho', _eyesOpen, Icons.visibility_rounded),
        _buildProgressConnector(_eyesOpen),
        _buildProgressStep(
            'Kati', _faceCentered, Icons.center_focus_strong_rounded),
        _buildProgressConnector(_faceCentered && _blinkDetected),
        _buildProgressStep(
            'Liveness', _blinkDetected, Icons.fingerprint_rounded),
      ],
    );
  }

  Widget _buildProgressStep(String label, bool completed, IconData icon) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        AnimatedContainer(
          duration: 300.ms,
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: completed
                ? DesignTokens.success
                : Colors.white.withValues(alpha: 0.1),
            shape: BoxShape.circle,
            border: Border.all(
              color: completed
                  ? DesignTokens.success
                  : Colors.white.withValues(alpha: 0.3),
              width: 2,
            ),
          ),
          child: Icon(
            completed ? Icons.check_rounded : icon,
            size: 18,
            color:
                completed ? Colors.white : Colors.white.withValues(alpha: 0.5),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(
            color:
                completed ? Colors.white : Colors.white.withValues(alpha: 0.5),
            fontSize: 11,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildProgressConnector(bool completed) {
    return Container(
      width: 30,
      height: 2,
      margin: const EdgeInsets.only(bottom: 22),
      decoration: BoxDecoration(
        color: completed
            ? DesignTokens.success
            : Colors.white.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(1),
      ),
    );
  }

  Widget _buildCaptureButton(bool canCapture) {
    return GestureDetector(
      onTap: canCapture && !_isProcessing ? _captureAndVerify : null,
      child: Container(
        width: 80,
        height: 80,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: canCapture
              ? DesignTokens.success
              : Colors.white.withValues(alpha: 0.2),
          border: Border.all(
            color:
                canCapture ? Colors.white : Colors.white.withValues(alpha: 0.4),
            width: 4,
          ),
          boxShadow: canCapture
              ? [
                  BoxShadow(
                    color: DesignTokens.success.withValues(alpha: 0.4),
                    blurRadius: 20,
                    spreadRadius: 2,
                  ),
                ]
              : null,
        ),
        child: _isProcessing
            ? const Padding(
                padding: EdgeInsets.all(20),
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 3,
                ),
              )
            : Icon(
                Icons.camera_alt_rounded,
                color: canCapture
                    ? Colors.white
                    : Colors.white.withValues(alpha: 0.5),
                size: 32,
              ),
      ),
    );
  }

  Widget _buildCircleButton({
    required IconData icon,
    VoidCallback? onPressed,
  }) {
    return GestureDetector(
      onTap: onPressed,
      child: Container(
        width: 56,
        height: 56,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: onPressed != null
              ? Colors.black.withValues(alpha: 0.5)
              : Colors.black.withValues(alpha: 0.3),
          border: Border.all(
            color: onPressed != null
                ? Colors.white.withValues(alpha: 0.2)
                : Colors.white.withValues(alpha: 0.1),
            width: 1.5,
          ),
        ),
        child: Icon(
          icon,
          color: onPressed != null
              ? Colors.white
              : Colors.white.withValues(alpha: 0.3),
          size: 24,
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
            const Spacer(),
            ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
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
            const Spacer(),
            if (canSwitch)
              ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: _isSwitchingCamera ? null : () => unawaited(_switchCamera()),
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

  /// User-facing Swahili for known API / face-match reasons.
  String _localizedFaceMatchReason(String reason) {
    final l = reason.toLowerCase();
    if (l.contains('face match service is not configured')) {
      return 'Huduma ya ulinganisho wa uso haijawekwa kwenye seva (kumbukumbu FACE_MATCH_URL). Msimamizi wa mfumo aihangaishe kisha ajaribu tena.';
    }
    if (l.contains('face match service is unreachable')) {
      return 'Huduma ya ulinganisho haipatikani kwa sasa. Angalia mtandao au jaribu tena baada ya muda mfupi.';
    }
    if (l.contains('face match failed') && l.contains('manual')) {
      return 'Ulinganisho haukufanikiwa. Jaribu picha nyingine au omba uhakiki wa mkono.';
    }
    if (l.contains('multiple_faces_detected')) {
      return 'Nyuso zaidi ya moja zimeonekana. Hakikisha mtu mmoja tu anaonekana kwenye frame.';
    }
    if (l.contains('no_face_detected')) {
      return 'Uso haujaonekana vizuri. Weka uso katikati ya frame na ujaribu tena.';
    }
    if (l.contains('face_too_small')) {
      return 'Uso uko mbali sana. Msogeze mteja karibu kidogo kwenye kamera.';
    }
    if (l.contains('image_blurry')) {
      return 'Picha imeblur. Simamisha kamera kwa utulivu na mwanga wa kutosha.';
    }
    if (l.contains('image_too_dark')) {
      return 'Picha ni giza sana. Ongeza mwanga kabla ya kupiga picha tena.';
    }
    if (l.contains('image_too_bright')) {
      return 'Mwanga ni mkali sana. Punguza mwanga mkali na ujaribu tena.';
    }
    return reason;
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

                const SizedBox(height: 32),

                // Score display
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 20,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.15),
                    ),
                  ),
                  child: Column(
                    children: [
                      Text(
                        'Alama ya Ulinganisho',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.6),
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            '$score',
                            style: TextStyle(
                              color: color,
                              fontSize: 48,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Text(
                              '%',
                              style: TextStyle(
                                color: color,
                                fontSize: 20,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      // Progress bar
                      ClipRRect(
                        borderRadius: BorderRadius.circular(99),
                        child: SizedBox(
                          width: 200,
                          child: LinearProgressIndicator(
                            value: score / 100,
                            minHeight: 8,
                            backgroundColor:
                                Colors.white.withValues(alpha: 0.1),
                            valueColor: AlwaysStoppedAnimation(color),
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1, end: 0),

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
                            _localizedFaceMatchReason(_result!.reason!),
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

/// Thin ring + soft glow around the face guide oval.
class _FaceGuideRingPainter extends CustomPainter {
  final Rect faceOval;
  final Color ringColor;
  final double strokeWidth;

  _FaceGuideRingPainter({
    required this.faceOval,
    required this.ringColor,
    required this.strokeWidth,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final glow = Paint()
      ..color = ringColor.withValues(alpha: 0.2)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth + 10
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 10);
    canvas.drawOval(faceOval, glow);

    canvas.drawOval(
      faceOval,
      Paint()
        ..color = ringColor
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth,
    );
  }

  @override
  bool shouldRepaint(covariant _FaceGuideRingPainter oldDelegate) {
    return oldDelegate.faceOval != faceOval ||
        oldDelegate.ringColor != ringColor ||
        oldDelegate.strokeWidth != strokeWidth;
  }
}

/// Custom painter for animated scan line
class _ScanLinePainter extends CustomPainter {
  final double position;
  final Color color;

  _ScanLinePainter({
    required this.position,
    required this.color,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 2
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    // Draw horizontal scan line at the given position
    final y = position;
    const lineLength = 180.0;
    final centerX = size.width / 2;

    // Main scan line
    canvas.drawLine(
      Offset(centerX - lineLength, y),
      Offset(centerX + lineLength, y),
      paint,
    );

    // Glow effect
    final glowPaint = Paint()
      ..color = color.withValues(alpha: 0.3)
      ..strokeWidth = 6
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 4);

    canvas.drawLine(
      Offset(centerX - lineLength, y),
      Offset(centerX + lineLength, y),
      glowPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _ScanLinePainter oldDelegate) {
    return oldDelegate.position != position || oldDelegate.color != color;
  }
}
