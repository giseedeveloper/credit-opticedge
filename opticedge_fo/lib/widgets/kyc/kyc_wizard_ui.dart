import 'package:flutter/material.dart';

import '../../config/constants.dart';
import '../common/glass_card.dart';

/// Compact sizing for the FO KYC registration wizard.
abstract final class KycWizardUi {
  static const EdgeInsets pagePadding = EdgeInsets.fromLTRB(14, 10, 14, 16);

  static const EdgeInsets cardPadding = EdgeInsets.all(12);

  static const BorderRadius cardRadius = BorderRadius.all(Radius.circular(16));

  static const double sectionGap = 12;

  static const double cardGap = 10;

  static const double innerGap = 8;

  static const double sectionTitleSize = 14;

  static const double cardTitleSize = 12;

  static const double bodySize = 12;

  static const double labelSize = 11;

  static ThemeData compactFormTheme(ThemeData base) {
    final inputBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AppConstants.border),
    );

    return base.copyWith(
      inputDecorationTheme: base.inputDecorationTheme.copyWith(
        isDense: true,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        labelStyle: const TextStyle(
          fontSize: labelSize,
          fontWeight: FontWeight.w600,
        ),
        hintStyle: TextStyle(
          fontSize: bodySize,
          color: base.hintColor,
        ),
        floatingLabelStyle: const TextStyle(
          fontSize: labelSize,
          fontWeight: FontWeight.w700,
        ),
        border: inputBorder,
        enabledBorder: inputBorder,
        focusedBorder: inputBorder.copyWith(
          borderSide: const BorderSide(
            color: AppConstants.primary,
            width: 1.4,
          ),
        ),
        errorBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: AppConstants.error),
        ),
      ),
      textTheme: base.textTheme.copyWith(
        bodyLarge: base.textTheme.bodyLarge?.copyWith(fontSize: 13),
        bodyMedium: base.textTheme.bodyMedium?.copyWith(fontSize: bodySize),
        bodySmall: base.textTheme.bodySmall?.copyWith(fontSize: labelSize),
        titleMedium: base.textTheme.titleMedium?.copyWith(fontSize: 14),
        titleSmall: base.textTheme.titleSmall?.copyWith(fontSize: cardTitleSize),
      ),
    );
  }
}

class KycSectionHeader extends StatelessWidget {
  const KycSectionHeader({
    super.key,
    required this.title,
    this.subtitle = '',
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: TextStyle(
            fontSize: KycWizardUi.sectionTitleSize,
            fontWeight: FontWeight.w800,
            color: Theme.of(context).textTheme.bodyLarge?.color,
          ),
        ),
        if (subtitle.trim().isNotEmpty) ...[
          const SizedBox(height: 3),
          Text(
            subtitle,
            style: TextStyle(
              fontSize: KycWizardUi.bodySize,
              height: 1.35,
              color: Theme.of(context).textTheme.bodyMedium?.color,
            ),
          ),
        ],
      ],
    );
  }
}

class KycFormCard extends StatelessWidget {
  const KycFormCard({
    super.key,
    this.title,
    this.subtitle,
    required this.child,
  });

  final String? title;
  final String? subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return GlassCard.surface(
      context,
      padding: KycWizardUi.cardPadding,
      borderRadius: KycWizardUi.cardRadius,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title != null) ...[
            Text(
              title!,
              style: TextStyle(
                fontSize: KycWizardUi.cardTitleSize,
                fontWeight: FontWeight.w800,
                color: Theme.of(context).textTheme.bodyLarge?.color,
              ),
            ),
            if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
              const SizedBox(height: 3),
              Text(
                subtitle!,
                style: TextStyle(
                  fontSize: KycWizardUi.labelSize,
                  height: 1.35,
                  color: Theme.of(context).textTheme.bodyMedium?.color,
                ),
              ),
            ],
            const SizedBox(height: KycWizardUi.innerGap),
          ],
          child,
        ],
      ),
    );
  }
}
