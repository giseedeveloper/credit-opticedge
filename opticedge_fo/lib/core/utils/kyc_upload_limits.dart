import 'dart:io';

/// Validates KYC photo payloads before multipart upload.
abstract final class KycUploadLimits {
  /// Per-file cap (matches typical mobile gateway limits).
  static const int maxBytesPerPhoto = 8 * 1024 * 1024;

  static String formatMb(int bytes) =>
      (bytes / (1024 * 1024)).toStringAsFixed(1);

  static String formatMaxMb() => (maxBytesPerPhoto / (1024 * 1024)).toStringAsFixed(0);

  /// Returns an error message, or null if [file] is null or within limits.
  static String? validateOptional(File? file, String label) {
    if (file == null) {
      return null;
    }
    try {
      final len = file.lengthSync();
      if (len > maxBytesPerPhoto) {
        return '$label must be under ${formatMaxMb()} MB (this file is ${formatMb(len)} MB). '
            'Try cropping or retaking at a lower resolution.';
      }
    } catch (_) {
      return 'Could not read $label. Pick the photo again.';
    }
    return null;
  }

  /// Concatenates multiple validation messages.
  static String? validateMany(List<(File?, String)> items) {
    final messages = <String>[];
    for (final item in items) {
      final msg = validateOptional(item.$1, item.$2);
      if (msg != null) {
        messages.add(msg);
      }
    }
    if (messages.isEmpty) {
      return null;
    }
    return messages.join('\n');
  }
}
