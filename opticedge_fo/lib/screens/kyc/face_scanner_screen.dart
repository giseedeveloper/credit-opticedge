import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';
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

class _FaceScannerScreenState extends ConsumerState<FaceScannerScreen>
    with WidgetsBindingObserver {
  CameraController? _cameraController;
  late final FaceDetector _faceDetector;

  bool _isInitialized = false;
  bool _isProcessing = false;
  bool _verificationPassed = false;
  String? _error;
  FaceVerificationResult? _result;

  // Face detection state
  bool _faceDetected = false;
  bool _eyesOpen = false;
  bool _faceCentered = false;
  bool _smiling = false;
  double? _smileProbability;

  // Camera description for proper rotation
  CameraDescription? _cameraDescription;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        enableContours: true,
        enableClassification: true,
        enableTracking: true,
        enableLandmarks: true,
        performanceMode: FaceDetectorMode.fast,
      ),
    );

    _initCamera();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

    if (state == AppLifecycleState.inactive) {
      controller.stopImageStream();
    } else if (state == AppLifecycleState.resumed) {
      if (!controller.value.isStreamingImages) {
        _startImageStream();
      }
    }
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      if (cameras.isEmpty) {
        setState(() => _error = 'Hakuna kamera iliyopatikana');
        return;
      }

      _cameraDescription = cameras.firstWhere(
        (cam) => cam.lensDirection == CameraLensDirection.front,
        orElse: () => cameras.first,
      );

      _cameraController = CameraController(
        _cameraDescription!,
        ResolutionPreset.max,
        enableAudio: false,
        imageFormatGroup: Platform.isAndroid
            ? ImageFormatGroup.nv21
            : ImageFormatGroup.bgra8888,
      );

      await _cameraController!.initialize();

      if (mounted) {
        setState(() => _isInitialized = true);
        _startImageStream();
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

  void _startImageStream() {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

    controller.startImageStream(_processImage);
  }

  void _processImage(CameraImage image) {
    if (_isProcessing || _cameraDescription == null) return;

    final inputImage = _convertToInputImage(image);
    if (inputImage == null) return;

    _faceDetector.processImage(inputImage).then((faces) {
      if (!mounted) return;

      setState(() {
        _faceDetected = faces.isNotEmpty;

        if (faces.isNotEmpty) {
          final face = faces.first;

          // Eye open probability
          final leftEye = face.leftEyeOpenProbability ?? 0.0;
          final rightEye = face.rightEyeOpenProbability ?? 0.0;
          _eyesOpen = leftEye > 0.5 && rightEye > 0.5;

          // Smile probability
          _smileProbability = face.smilingProbability;
          _smiling = (_smileProbability ?? 0.0) > 0.3;

          // Center check (face should be in center 60% of frame)
          final centerX = face.boundingBox.center.dx;
          final frameWidth = image.width.toDouble();
          _faceCentered =
              centerX >= frameWidth * 0.2 && centerX <= frameWidth * 0.8;
        } else {
          _smileProbability = null;
        }
      });
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

  Future<void> _captureAndVerify() async {
    final controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) return;

    setState(() {
      _isProcessing = true;
      _error = null;
    });

    try {
      // Stop stream for capture
      await controller.stopImageStream();

      final image = await controller.takePicture();
      final file = File(image.path);

      final result = await FaceVerificationService.instance.verifyFace(
        widget.customerId,
        file,
      );

      if (mounted) {
        setState(() {
          _result = result;
          _verificationPassed = result.passed;
          _isProcessing = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isProcessing = false;
        });
        _startImageStream();
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
      final result = await FaceVerificationService.instance.verifyFace(
        widget.customerId,
        File(picked.path),
      );

      if (mounted) {
        setState(() {
          _result = result;
          _verificationPassed = result.passed;
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

  void _retry() {
    setState(() {
      _result = null;
      _verificationPassed = false;
      _error = null;
      _isProcessing = false;
      _faceDetected = false;
    });
    _startImageStream();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
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
    return _buildCameraPreview();
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
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
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
                ).animate().scale(duration: 400.ms, curve: Curves.elasticOut),
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
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.7),
                    fontSize: 15,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 40),
                _buildActionButton(
                  label: 'Jaribu Tena',
                  icon: Icons.refresh_rounded,
                  onPressed: _retry,
                  isPrimary: true,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCameraPreview() {
    final size = MediaQuery.sizeOf(context);
    final allChecksPassed = _faceDetected && _eyesOpen && _faceCentered;

    return Stack(
      fit: StackFit.expand,
      children: [
        // Full screen camera preview
        CameraPreview(_cameraController!),

        // Gradient overlays for better visibility
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          height: 120,
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black.withValues(alpha: 0.6),
                  Colors.transparent,
                ],
              ),
            ),
          ),
        ),

        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          height: 280,
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.bottomCenter,
                end: Alignment.topCenter,
                colors: [
                  Colors.black.withValues(alpha: 0.85),
                  Colors.black.withValues(alpha: 0.4),
                  Colors.transparent,
                ],
              ),
            ),
          ),
        ),

        // Face overlay guide
        Center(
          child: _buildFaceOverlay(),
        ),

        // Top bar with close button and title
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          child: _buildTopBar(),
        ),

        // Bottom controls
        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          child: _buildBottomControls(allChecksPassed),
        ),

        // Side indicators
        Positioned(
          left: 16,
          top: size.height * 0.35,
          child: _buildSideIndicators(),
        ),
      ],
    );
  }

  Widget _buildTopBar() {
    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        child: Row(
          children: [
            // Close button
            Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => context.pop(_verificationPassed),
                borderRadius: BorderRadius.circular(24),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: const BoxDecoration(
                    color: Colors.black38,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.close_rounded,
                    color: Colors.white,
                    size: 24,
                  ),
                ),
              ),
            ),

            const Spacer(),

            // Title
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: const BoxDecoration(
                color: Colors.black38,
                borderRadius: BorderRadius.all(Radius.circular(20)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.face_retouching_natural_rounded,
                    color: AppConstants.primary,
                    size: 20,
                  ),
                  SizedBox(width: 8),
                  Text(
                    'Uthibitishaji wa Uso',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),

            const Spacer(),

            const SizedBox(width: 48), // Balance the close button
          ],
        ),
      ),
    );
  }

  Widget _buildFaceOverlay() {
    final overlayColor = _faceDetected
        ? (_eyesOpen && _faceCentered
            ? DesignTokens.success
            : DesignTokens.warning)
        : Colors.white.withValues(alpha: 0.4);

    return AnimatedContainer(
      duration: 300.ms,
      width: 260,
      height: 320,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(160),
        border: Border.all(
          color: overlayColor,
          width: _faceDetected ? 3 : 2,
        ),
        boxShadow: _faceDetected
            ? [
                BoxShadow(
                  color: overlayColor.withValues(alpha: 0.3),
                  blurRadius: 20,
                  spreadRadius: 5,
                ),
              ]
            : null,
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Corner guides
          ..._buildCornerGuides(overlayColor),

          // Face detection animation
          if (_faceDetected && _eyesOpen && _faceCentered)
            Positioned.fill(
              child: Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(160),
                  border: Border.all(
                    color: DesignTokens.success.withValues(alpha: 0.5),
                    width: 1,
                  ),
                ),
              ).animate(onPlay: (c) => c.repeat(reverse: true)).scale(
                    begin: const Offset(1, 1),
                    end: const Offset(1.02, 1.02),
                    duration: 800.ms,
                  ),
            ),
        ],
      ),
    );
  }

  List<Widget> _buildCornerGuides(Color color) {
    const cornerSize = 30.0;
    const cornerWidth = 4.0;

    return [
      Positioned(
          top: 0,
          left: 0,
          child: _cornerGuide(color, cornerSize, cornerWidth, true, true)),
      Positioned(
          top: 0,
          right: 0,
          child: _cornerGuide(color, cornerSize, cornerWidth, true, false)),
      Positioned(
          bottom: 0,
          left: 0,
          child: _cornerGuide(color, cornerSize, cornerWidth, false, true)),
      Positioned(
          bottom: 0,
          right: 0,
          child: _cornerGuide(color, cornerSize, cornerWidth, false, false)),
    ];
  }

  Widget _cornerGuide(
      Color color, double size, double width, bool top, bool left) {
    return SizedBox(
      width: size,
      height: size,
      child: CustomPaint(
        painter: _CornerPainter(
          color: color,
          strokeWidth: width,
          topLeft: top && left,
          topRight: top && !left,
          bottomLeft: !top && left,
          bottomRight: !top && !left,
        ),
      ),
    );
  }

  Widget _buildSideIndicators() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _buildIndicatorChip(
          icon: Icons.face_rounded,
          label: 'Uso',
          active: _faceDetected,
        ),
        const SizedBox(height: 12),
        _buildIndicatorChip(
          icon: Icons.visibility_rounded,
          label: 'Macho',
          active: _eyesOpen,
        ),
        const SizedBox(height: 12),
        _buildIndicatorChip(
          icon: Icons.center_focus_strong_rounded,
          label: 'Kati',
          active: _faceCentered,
        ),
        const SizedBox(height: 12),
        _buildIndicatorChip(
          icon: Icons.sentiment_satisfied_rounded,
          label: 'Tabasamu',
          active: _smiling,
          optional: true,
        ),
      ],
    );
  }

  Widget _buildIndicatorChip({
    required IconData icon,
    required String label,
    required bool active,
    bool optional = false,
  }) {
    return AnimatedContainer(
      duration: 200.ms,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: active
            ? DesignTokens.success.withValues(alpha: 0.2)
            : Colors.black38,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: active
              ? DesignTokens.success.withValues(alpha: 0.5)
              : Colors.white24,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 18,
            color: active
                ? DesignTokens.success
                : Colors.white.withValues(alpha: 0.5),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              color: active
                  ? DesignTokens.success
                  : Colors.white.withValues(alpha: 0.5),
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          if (optional) ...[
            const SizedBox(width: 4),
            Icon(
              active ? Icons.check_circle : Icons.circle_outlined,
              size: 14,
              color: active
                  ? DesignTokens.success
                  : Colors.white.withValues(alpha: 0.3),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildBottomControls(bool canCapture) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Status message
            AnimatedSwitcher(
              duration: 300.ms,
              child: Container(
                key: ValueKey('$_faceDetected$_eyesOpen$_faceCentered'),
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                decoration: BoxDecoration(
                  color: canCapture
                      ? DesignTokens.success.withValues(alpha: 0.2)
                      : Colors.black38,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(
                    color: canCapture
                        ? DesignTokens.success.withValues(alpha: 0.4)
                        : Colors.white24,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      canCapture
                          ? Icons.check_circle_rounded
                          : Icons.info_outline_rounded,
                      color: canCapture
                          ? DesignTokens.success
                          : Colors.white.withValues(alpha: 0.8),
                      size: 20,
                    ),
                    const SizedBox(width: 10),
                    Flexible(
                      child: Text(
                        canCapture
                            ? 'Uso umegundulwa! Piga picha sasa'
                            : _faceDetected
                                ? 'Elekeza uso vizuri ndani ya mduara'
                                : 'Weka uso wako ndani ya mduara',
                        style: TextStyle(
                          color: canCapture
                              ? DesignTokens.success
                              : Colors.white.withValues(alpha: 0.8),
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // Capture button
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Gallery button
                _buildCircleButton(
                  icon: Icons.photo_library_rounded,
                  onPressed: _isProcessing ? null : _pickFromGallery,
                  size: 52,
                ),

                const SizedBox(width: 24),

                // Main capture button
                _buildCaptureButton(canCapture),

                const SizedBox(width: 24),

                // Flash placeholder (for symmetry)
                const SizedBox(width: 52),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCaptureButton(bool canCapture) {
    const size = 80.0;

    return GestureDetector(
      onTap: canCapture && !_isProcessing ? _captureAndVerify : null,
      child: AnimatedContainer(
        duration: 200.ms,
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: canCapture
              ? AppConstants.primary
              : Colors.white.withValues(alpha: 0.3),
          border: Border.all(
            color: canCapture
                ? AppConstants.primaryLight
                : Colors.white.withValues(alpha: 0.2),
            width: 4,
          ),
          boxShadow: canCapture
              ? [
                  BoxShadow(
                    color: AppConstants.primary.withValues(alpha: 0.4),
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
            : const Icon(
                Icons.camera_alt_rounded,
                color: Colors.white,
                size: 32,
              ),
      ),
    );
  }

  Widget _buildCircleButton({
    required IconData icon,
    VoidCallback? onPressed,
    double size = 48,
  }) {
    return GestureDetector(
      onTap: onPressed,
      child: Container(
        width: size,
        height: size,
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.black38,
          border: Border.fromBorderSide(BorderSide(
            color: Colors.white24,
          )),
        ),
        child: Icon(
          icon,
          color: onPressed != null ? Colors.white : Colors.white38,
          size: size * 0.45,
        ),
      ),
    );
  }

  Widget _buildResult() {
    final passed = _result!.passed;
    final color = passed ? DesignTokens.success : DesignTokens.warning;
    final score = (_result!.score * 100).round();

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
                    passed
                        ? Icons.verified_user_rounded
                        : Icons.warning_amber_rounded,
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
                  passed
                      ? 'Uthibitishaji Umefanikiwa!'
                      : 'Uthibitishaji Haujakamilika',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                  ),
                ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1, end: 0),

                const SizedBox(height: 12),

                Text(
                  passed
                      ? 'Uso wako umelinganishwa na picha ya kitambulisho'
                      : 'Tafadhali jaribu tena na picha nyingine',
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

                if (_result!.reason != null && !passed) ...[
                  const SizedBox(height: 20),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: DesignTokens.warning.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: DesignTokens.warning.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.info_outline_rounded,
                          color: DesignTokens.warning,
                          size: 20,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _result!.reason!,
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
                if (passed)
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
                          onPressed: _retry,
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
          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
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
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                icon,
                color: Colors.white,
                size: 20,
              ),
              const SizedBox(width: 10),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Custom painter for corner guides
class _CornerPainter extends CustomPainter {
  final Color color;
  final double strokeWidth;
  final bool topLeft;
  final bool topRight;
  final bool bottomLeft;
  final bool bottomRight;

  _CornerPainter({
    required this.color,
    required this.strokeWidth,
    this.topLeft = false,
    this.topRight = false,
    this.bottomLeft = false,
    this.bottomRight = false,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = strokeWidth
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    final path = Path();
    final w = size.width;
    final h = size.height;
    final cornerLength = w * 0.4;

    if (topLeft) {
      path.moveTo(0, cornerLength);
      path.lineTo(0, 0);
      path.lineTo(cornerLength, 0);
    }
    if (topRight) {
      path.moveTo(w - cornerLength, 0);
      path.lineTo(w, 0);
      path.lineTo(w, cornerLength);
    }
    if (bottomLeft) {
      path.moveTo(0, h - cornerLength);
      path.lineTo(0, h);
      path.lineTo(cornerLength, h);
    }
    if (bottomRight) {
      path.moveTo(w - cornerLength, h);
      path.lineTo(w, h);
      path.lineTo(w, h - cornerLength);
    }

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _CornerPainter oldDelegate) {
    return oldDelegate.color != color || oldDelegate.strokeWidth != strokeWidth;
  }
}
