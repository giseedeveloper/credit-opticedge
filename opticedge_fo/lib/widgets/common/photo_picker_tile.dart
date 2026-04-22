import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../config/constants.dart';
import '../../core/utils/kyc_upload_limits.dart';

class PhotoPickerTile extends StatelessWidget {
  final String label;
  final bool required;
  final File? file;
  final ValueChanged<File?> onPicked;

  const PhotoPickerTile({
    super.key,
    required this.label,
    this.required = false,
    this.file,
    required this.onPicked,
  });

  Future<void> _pick(BuildContext context, ImageSource source) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: source,
      imageQuality: 75,
      maxWidth: 1200,
    );
    if (picked != null) {
      final file = File(picked.path);
      final msg = KycUploadLimits.validateOptional(file, label);
      if (msg != null) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(msg)),
          );
        }
        return;
      }
      onPicked(file);
    }
  }

  void _showOptions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppConstants.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 16),
              Text(label,
                  style: const TextStyle(
                      fontWeight: FontWeight.w600, fontSize: 15)),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppConstants.primarySurface,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.camera_alt_rounded,
                      color: AppConstants.primary),
                ),
                title: const Text('Take Photo'),
                onTap: () {
                  Navigator.pop(context);
                  _pick(context, ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppConstants.borderLight,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.photo_library_rounded,
                      color: AppConstants.textSecondary),
                ),
                title: const Text('Choose from Gallery'),
                onTap: () {
                  Navigator.pop(context);
                  _pick(context, ImageSource.gallery);
                },
              ),
              if (file != null)
                ListTile(
                  leading: Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.delete_outline_rounded,
                        color: AppConstants.error),
                  ),
                  title: const Text('Remove Photo'),
                  onTap: () {
                    Navigator.pop(context);
                    onPicked(null);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: file != null
          ? '$label — photo attached, tap to replace'
          : '$label — add photo',
      child: GestureDetector(
      onTap: () => _showOptions(context),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final maxW = constraints.maxWidth;
          final maxH = constraints.maxHeight;
          final isCompactTile = maxW < 120 || maxH < 110;
          final tileHeight = maxH.isFinite ? maxH : 100.0;

          return AnimatedContainer(
            duration: const Duration(milliseconds: 240),
            height: tileHeight,
            width: maxW.isFinite ? maxW : null,
            decoration: BoxDecoration(
              gradient: file != null
                  ? null
                  : LinearGradient(
                      colors: [
                        Colors.white,
                        AppConstants.primarySurface.withValues(alpha: 0.72),
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
              border: Border.all(
                color:
                    file != null ? AppConstants.success : AppConstants.border,
                width: file != null ? 1.5 : 1,
              ),
              borderRadius: BorderRadius.circular(22),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 18,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: file != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(21),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.file(file!, fit: BoxFit.cover),
                        Positioned(
                          left: 10,
                          bottom: 10,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.56),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              label,
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          top: 6,
                          right: 6,
                          child: Container(
                            padding: const EdgeInsets.all(3),
                            decoration: const BoxDecoration(
                                color: AppConstants.success,
                                shape: BoxShape.circle),
                            child: const Icon(Icons.check,
                                color: Colors.white, size: 12),
                          ),
                        ),
                      ],
                    ),
                  )
                : Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: isCompactTile ? 6 : 8,
                      vertical: isCompactTile ? 4 : 8,
                    ),
                    child: Center(
                      child: FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.center,
                        child: ConstrainedBox(
                          constraints: BoxConstraints(
                            maxWidth: maxW.isFinite
                                ? (maxW - (isCompactTile ? 12 : 16))
                                    .clamp(0, double.infinity)
                                : 160,
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: isCompactTile ? 32 : 44,
                                height: isCompactTile ? 32 : 44,
                                decoration: BoxDecoration(
                                  color: AppConstants.surface,
                                  borderRadius: BorderRadius.circular(
                                    isCompactTile ? 12 : 16,
                                  ),
                                ),
                                child: Icon(
                                  Icons.document_scanner_outlined,
                                  color: AppConstants.primary,
                                  size: isCompactTile ? 18 : 24,
                                ),
                              ),
                              SizedBox(height: isCompactTile ? 4 : 8),
                              Text(
                                label,
                                maxLines: isCompactTile ? 2 : 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: isCompactTile ? 9.5 : 11,
                                  fontWeight: FontWeight.w700,
                                  color: AppConstants.textPrimary,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              if (!isCompactTile) ...[
                                const SizedBox(height: 4),
                                const Text(
                                  'Tap to scan or attach',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                    color: AppConstants.textSecondary,
                                  ),
                                ),
                              ],
                              if (required && !isCompactTile)
                                Container(
                                  margin: const EdgeInsets.only(top: 5),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 3,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppConstants.errorSurface,
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: const Text(
                                    'Required',
                                    style: TextStyle(
                                      fontSize: 9,
                                      color: AppConstants.error,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
          );
        },
      ),
    ),
    );
  }
}
