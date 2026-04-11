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
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 146,
              child: DropdownButtonFormField<String>(
                initialValue: selectedOption?.iso,
                menuMaxHeight: 320,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.flag_circle_outlined, size: 18),
                ),
                items: countries
                    .map(
                      (country) => DropdownMenuItem<String>(
                        value: country.iso,
                        child: Text(
                          '${country.flag} ${country.dialCode}',
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    )
                    .toList(),
                onChanged: onCountryChanged,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: TextFormField(
                controller: controller,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                decoration: InputDecoration(
                  hintText: hintText,
                  suffixIcon: selectedOption == null
                      ? null
                      : Padding(
                          padding: const EdgeInsets.only(right: 12),
                          child: Center(
                            widthFactor: 1,
                            child: Text(
                              selectedOption.dialCode,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: AppConstants.textSecondary,
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
