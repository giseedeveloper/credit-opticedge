import 'package:flutter/material.dart';

import '../../config/constants.dart';
import '../../core/models/kyc_flow_model.dart';

class PhoneNumberField extends StatelessWidget {
  final String label;
  final bool required;
  final TextEditingController controller;
  final String selectedCountry;
  final List<PhoneCountryOption> countries;
  final ValueChanged<String?> onCountryChanged;
  final String hintText;
  final String? helperText;
  final String? Function(String?)? validator;

  const PhoneNumberField({
    super.key,
    required this.label,
    required this.controller,
    required this.selectedCountry,
    required this.countries,
    required this.onCountryChanged,
    this.required = false,
    this.hintText = '712345678',
    this.helperText,
    this.validator,
  });

  @override
  Widget build(BuildContext context) {
    final selectedOption = countries.cast<PhoneCountryOption?>().firstWhere(
          (country) => country?.iso == selectedCountry,
          orElse: () => countries.isNotEmpty ? countries.first : null,
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppConstants.textPrimary,
              ),
            ),
            if (!required) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppConstants.borderLight,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: const Text(
                  'Optional',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: AppConstants.textHint,
                  ),
                ),
              ),
            ],
          ],
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: AppConstants.border),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                width: 126,
                child: DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: selectedOption?.iso,
                  menuMaxHeight: 320,
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: AppConstants.surfaceMuted,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 12,
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                        color: AppConstants.primary,
                        width: 1.2,
                      ),
                    ),
                  ),
                  items: countries
                      .map(
                        (country) => DropdownMenuItem<String>(
                          value: country.iso,
                          child: Text(
                            '${country.flag} ${country.dialCode}',
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: onCountryChanged,
                ),
              ),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
                child: Container(
                  width: 1,
                  height: 28,
                  color: AppConstants.border,
                ),
              ),
              Expanded(
                child: TextFormField(
                  controller: controller,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    fillColor: Colors.transparent,
                    hintText: hintText,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 14,
                    ),
                    suffixIcon: selectedOption == null
                        ? null
                        : Padding(
                            padding: const EdgeInsets.only(right: 10),
                            child: Center(
                              widthFactor: 1,
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 6,
                                ),
                                decoration: BoxDecoration(
                                  color: AppConstants.infoSurface,
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  selectedOption.dialCode,
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                    color: AppConstants.info,
                                  ),
                                ),
                              ),
                            ),
                          ),
                  ),
                  validator: validator ??
                      (required
                          ? (value) {
                              if (value == null || value.trim().isEmpty) {
                                return 'Required';
                              }

                              return null;
                            }
                          : null),
                ),
              ),
            ],
          ),
        ),
        if (helperText != null) ...[
          const SizedBox(height: 6),
          Text(
            helperText!,
            style: const TextStyle(
              fontSize: 11,
              height: 1.4,
              color: AppConstants.textSecondary,
            ),
          ),
        ],
      ],
    );
  }
}
