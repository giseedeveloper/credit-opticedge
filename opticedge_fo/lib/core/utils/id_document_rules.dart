class IdDocumentRules {
  static String labelFor(String idType) {
    switch (idType) {
      case 'voter_card':
        return 'Voter card number';
      case 'passport':
        return 'Passport number';
      case 'driving_license':
        return 'Driving licence number';
      case 'nida':
      default:
        return 'NIDA number';
    }
  }

  static String hintFor(String idType) {
    switch (idType) {
      case 'voter_card':
        return 'Letters, numbers, or dashes (8–24 chars)';
      case 'passport':
        return 'Usually 6–12 characters';
      case 'driving_license':
        return 'As printed on the licence card';
      case 'nida':
      default:
        return '20 digits exactly';
    }
  }

  static int maxLengthFor(String idType) {
    switch (idType) {
      case 'nida':
        return 20;
      case 'passport':
        return 12;
      case 'driving_license':
        return 20;
      case 'voter_card':
      default:
        return 24;
    }
  }

  static String? validate(String idType, String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return 'Required';
    }

    switch (idType) {
      case 'nida':
        if (!RegExp(r'^\d{20}$').hasMatch(trimmed)) {
          return 'NIDA must be exactly 20 digits';
        }
        return null;
      case 'voter_card':
        if (!RegExp(r'^[A-Z0-9][A-Z0-9\-\/]{7,23}$', caseSensitive: false)
            .hasMatch(trimmed)) {
          return 'Enter a valid voter card number';
        }
        return null;
      case 'passport':
        if (!RegExp(r'^[A-Z][A-Z0-9]{5,11}$', caseSensitive: false)
            .hasMatch(trimmed)) {
          return 'Enter a valid passport number';
        }
        return null;
      case 'driving_license':
        if (!RegExp(r'^[A-Z0-9][A-Z0-9\-\/]{4,19}$', caseSensitive: false)
            .hasMatch(trimmed)) {
          return 'Enter a valid licence number';
        }
        return null;
      default:
        if (trimmed.length < 5) {
          return 'ID number is too short';
        }
        return null;
    }
  }
}
