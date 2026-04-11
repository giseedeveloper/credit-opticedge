import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../api/api_client.dart';
import '../../config/constants.dart';

class KycDraftState {
  final String? customerId;
  final int currentStep;
  final bool isSubmitting;
  final String? error;
  final bool stepSaved;

  // Step 1 — Device
  final String deviceSpecs;
  final String imeiNumber;
  final String imei2;
  final String serialNumber;
  final String cashPrice;
  final String depositAmount;
  final String preferredRepayment;
  final File? imeiPhoto;
  final File? deviceBoxPhoto;
  final File? devicePhoto;

  // Step 2 — Identity
  final String firstName;
  final String middleName;
  final String lastName;
  final String gender;
  final String dateOfBirth;
  final String nidaNumber;
  final String idType;
  final File? idFrontPhoto;
  final File? idBackPhoto;
  final File? headshotPhoto;
  final File? clientFoPhoto;

  // Step 3 — Contact
  final String phone;
  final String altPhone;
  final String email;
  final String branchId;
  final String address;
  final String landmark;
  final String region;
  final String district;

  // Step 4 — Income
  final String occupation;
  final String employer;
  final String workLocation;
  final String monthlyIncome;
  final String monthlyExpenses;
  final String incomePaymentCycle;
  final String durationAtWork;
  final File? businessPhoto;

  // Step 5 — NOK
  final String nokName;
  final String nokPhone;
  final String nokRelationship;
  final String nok2Name;
  final String nok2Phone;
  final String nok2Relationship;

  // Step 6 — Consent
  final bool termsAccepted;
  final bool dataConsentAccepted;
  final bool callConsentAccepted;

  // Step 7 — Submit
  final String foNotes;
  final String applicationSource;

  const KycDraftState({
    this.customerId,
    this.currentStep = 1,
    this.isSubmitting = false,
    this.error,
    this.stepSaved = false,
    this.deviceSpecs = '',
    this.imeiNumber = '',
    this.imei2 = '',
    this.serialNumber = '',
    this.cashPrice = '',
    this.depositAmount = '',
    this.preferredRepayment = 'weekly',
    this.imeiPhoto,
    this.deviceBoxPhoto,
    this.devicePhoto,
    this.firstName = '',
    this.middleName = '',
    this.lastName = '',
    this.gender = 'male',
    this.dateOfBirth = '',
    this.nidaNumber = '',
    this.idType = 'nida',
    this.idFrontPhoto,
    this.idBackPhoto,
    this.headshotPhoto,
    this.clientFoPhoto,
    this.phone = '',
    this.altPhone = '',
    this.email = '',
    this.branchId = '',
    this.address = '',
    this.landmark = '',
    this.region = '',
    this.district = '',
    this.occupation = '',
    this.employer = '',
    this.workLocation = '',
    this.monthlyIncome = '',
    this.monthlyExpenses = '',
    this.incomePaymentCycle = 'monthly',
    this.durationAtWork = '',
    this.businessPhoto,
    this.nokName = '',
    this.nokPhone = '',
    this.nokRelationship = '',
    this.nok2Name = '',
    this.nok2Phone = '',
    this.nok2Relationship = '',
    this.termsAccepted = false,
    this.dataConsentAccepted = false,
    this.callConsentAccepted = false,
    this.foNotes = '',
    this.applicationSource = 'walk_in',
  });

  KycDraftState copyWith({
    String? customerId,
    int? currentStep,
    bool? isSubmitting,
    String? error,
    bool? stepSaved,
    String? deviceSpecs,
    String? imeiNumber,
    String? imei2,
    String? serialNumber,
    String? cashPrice,
    String? depositAmount,
    String? preferredRepayment,
    File? imeiPhoto,
    File? deviceBoxPhoto,
    File? devicePhoto,
    String? firstName,
    String? middleName,
    String? lastName,
    String? gender,
    String? dateOfBirth,
    String? nidaNumber,
    String? idType,
    File? idFrontPhoto,
    File? idBackPhoto,
    File? headshotPhoto,
    File? clientFoPhoto,
    String? phone,
    String? altPhone,
    String? email,
    String? branchId,
    String? address,
    String? landmark,
    String? region,
    String? district,
    String? occupation,
    String? employer,
    String? workLocation,
    String? monthlyIncome,
    String? monthlyExpenses,
    String? incomePaymentCycle,
    String? durationAtWork,
    File? businessPhoto,
    String? nokName,
    String? nokPhone,
    String? nokRelationship,
    String? nok2Name,
    String? nok2Phone,
    String? nok2Relationship,
    bool? termsAccepted,
    bool? dataConsentAccepted,
    bool? callConsentAccepted,
    String? foNotes,
    String? applicationSource,
  }) =>
      KycDraftState(
        customerId: customerId ?? this.customerId,
        currentStep: currentStep ?? this.currentStep,
        isSubmitting: isSubmitting ?? this.isSubmitting,
        error: error,
        stepSaved: stepSaved ?? this.stepSaved,
        deviceSpecs: deviceSpecs ?? this.deviceSpecs,
        imeiNumber: imeiNumber ?? this.imeiNumber,
        imei2: imei2 ?? this.imei2,
        serialNumber: serialNumber ?? this.serialNumber,
        cashPrice: cashPrice ?? this.cashPrice,
        depositAmount: depositAmount ?? this.depositAmount,
        preferredRepayment: preferredRepayment ?? this.preferredRepayment,
        imeiPhoto: imeiPhoto ?? this.imeiPhoto,
        deviceBoxPhoto: deviceBoxPhoto ?? this.deviceBoxPhoto,
        devicePhoto: devicePhoto ?? this.devicePhoto,
        firstName: firstName ?? this.firstName,
        middleName: middleName ?? this.middleName,
        lastName: lastName ?? this.lastName,
        gender: gender ?? this.gender,
        dateOfBirth: dateOfBirth ?? this.dateOfBirth,
        nidaNumber: nidaNumber ?? this.nidaNumber,
        idType: idType ?? this.idType,
        idFrontPhoto: idFrontPhoto ?? this.idFrontPhoto,
        idBackPhoto: idBackPhoto ?? this.idBackPhoto,
        headshotPhoto: headshotPhoto ?? this.headshotPhoto,
        clientFoPhoto: clientFoPhoto ?? this.clientFoPhoto,
        phone: phone ?? this.phone,
        altPhone: altPhone ?? this.altPhone,
        email: email ?? this.email,
        branchId: branchId ?? this.branchId,
        address: address ?? this.address,
        landmark: landmark ?? this.landmark,
        region: region ?? this.region,
        district: district ?? this.district,
        occupation: occupation ?? this.occupation,
        employer: employer ?? this.employer,
        workLocation: workLocation ?? this.workLocation,
        monthlyIncome: monthlyIncome ?? this.monthlyIncome,
        monthlyExpenses: monthlyExpenses ?? this.monthlyExpenses,
        incomePaymentCycle: incomePaymentCycle ?? this.incomePaymentCycle,
        durationAtWork: durationAtWork ?? this.durationAtWork,
        businessPhoto: businessPhoto ?? this.businessPhoto,
        nokName: nokName ?? this.nokName,
        nokPhone: nokPhone ?? this.nokPhone,
        nokRelationship: nokRelationship ?? this.nokRelationship,
        nok2Name: nok2Name ?? this.nok2Name,
        nok2Phone: nok2Phone ?? this.nok2Phone,
        nok2Relationship: nok2Relationship ?? this.nok2Relationship,
        termsAccepted: termsAccepted ?? this.termsAccepted,
        dataConsentAccepted: dataConsentAccepted ?? this.dataConsentAccepted,
        callConsentAccepted: callConsentAccepted ?? this.callConsentAccepted,
        foNotes: foNotes ?? this.foNotes,
        applicationSource: applicationSource ?? this.applicationSource,
      );
}

class KycNotifier extends StateNotifier<KycDraftState> {
  KycNotifier() : super(const KycDraftState());

  void update(KycDraftState Function(KycDraftState) updater) {
    state = updater(state);
  }

  Future<bool> submitStep1() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final form = FormData.fromMap({
        'device_specs': state.deviceSpecs,
        'imei_number': state.imeiNumber,
        if (state.imei2.isNotEmpty) 'imei_2': state.imei2,
        if (state.serialNumber.isNotEmpty) 'serial_number': state.serialNumber,
        'cash_price': state.cashPrice,
        'deposit_amount': state.depositAmount,
        'preferred_repayment': state.preferredRepayment,
        if (state.imeiPhoto != null)
          'imei_photo': await MultipartFile.fromFile(state.imeiPhoto!.path,
              filename: 'imei.jpg'),
        if (state.deviceBoxPhoto != null)
          'device_box_photo': await MultipartFile.fromFile(
              state.deviceBoxPhoto!.path,
              filename: 'box.jpg'),
        if (state.devicePhoto != null)
          'device_photo': await MultipartFile.fromFile(state.devicePhoto!.path,
              filename: 'device.jpg'),
      });
      final res = await ApiClient.instance.postForm(
          '/kyc/application/step1', form);
      final customerId =
          res.data['data']['customer_id']?.toString() ?? '';
      state = state.copyWith(
          customerId: customerId,
          isSubmitting: false,
          stepSaved: true,
          currentStep: 2);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<bool> submitStep2() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      final form = FormData.fromMap({
        'first_name': state.firstName,
        if (state.middleName.isNotEmpty) 'middle_name': state.middleName,
        'last_name': state.lastName,
        'gender': state.gender,
        if (state.dateOfBirth.isNotEmpty) 'date_of_birth': state.dateOfBirth,
        'nida_number': state.nidaNumber,
        'id_type': state.idType,
        if (state.idFrontPhoto != null)
          'id_front_photo': await MultipartFile.fromFile(
              state.idFrontPhoto!.path,
              filename: 'id_front.jpg'),
        if (state.idBackPhoto != null)
          'id_back_photo': await MultipartFile.fromFile(
              state.idBackPhoto!.path,
              filename: 'id_back.jpg'),
        if (state.headshotPhoto != null)
          'headshot_photo': await MultipartFile.fromFile(
              state.headshotPhoto!.path,
              filename: 'headshot.jpg'),
        if (state.clientFoPhoto != null)
          'client_fo_photo': await MultipartFile.fromFile(
              state.clientFoPhoto!.path,
              filename: 'client_fo.jpg'),
      });
      await ApiClient.instance
          .postForm('/kyc/application/$id/step2', form);
      state = state.copyWith(
          isSubmitting: false, stepSaved: true, currentStep: 3);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<bool> submitStep3() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      await ApiClient.instance.post('/kyc/application/$id/step3', data: {
        'phone': state.phone,
        if (state.altPhone.isNotEmpty) 'alt_phone': state.altPhone,
        if (state.email.isNotEmpty) 'email': state.email,
        'branch_id': state.branchId,
        if (state.address.isNotEmpty) 'address': state.address,
        if (state.landmark.isNotEmpty) 'landmark': state.landmark,
        if (state.region.isNotEmpty) 'region': state.region,
        if (state.district.isNotEmpty) 'district': state.district,
      });
      state = state.copyWith(
          isSubmitting: false, stepSaved: true, currentStep: 4);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<bool> submitStep4() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      final form = FormData.fromMap({
        if (state.occupation.isNotEmpty) 'occupation': state.occupation,
        if (state.employer.isNotEmpty) 'employer': state.employer,
        if (state.workLocation.isNotEmpty) 'work_location': state.workLocation,
        'monthly_income': state.monthlyIncome,
        if (state.monthlyExpenses.isNotEmpty)
          'monthly_expenses': state.monthlyExpenses,
        'income_payment_cycle': state.incomePaymentCycle,
        if (state.durationAtWork.isNotEmpty)
          'duration_at_work': state.durationAtWork,
        if (state.businessPhoto != null)
          'business_photo': await MultipartFile.fromFile(
              state.businessPhoto!.path,
              filename: 'business.jpg'),
      });
      await ApiClient.instance
          .postForm('/kyc/application/$id/step4', form);
      state = state.copyWith(
          isSubmitting: false, stepSaved: true, currentStep: 5);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<bool> submitStep5() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      await ApiClient.instance.post('/kyc/application/$id/step5', data: {
        'nok_name': state.nokName,
        'nok_phone': state.nokPhone,
        'nok_relationship': state.nokRelationship,
        if (state.nok2Name.isNotEmpty) 'nok2_name': state.nok2Name,
        if (state.nok2Phone.isNotEmpty) 'nok2_phone': state.nok2Phone,
        if (state.nok2Relationship.isNotEmpty)
          'nok2_relationship': state.nok2Relationship,
      });
      state = state.copyWith(
          isSubmitting: false, stepSaved: true, currentStep: 6);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<bool> submitStep6() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      await ApiClient.instance.post('/kyc/application/$id/step6', data: {
        'terms_accepted': state.termsAccepted ? '1' : '0',
        'data_consent_accepted': state.dataConsentAccepted ? '1' : '0',
        'call_consent_accepted': state.callConsentAccepted ? '1' : '0',
      });
      state = state.copyWith(
          isSubmitting: false, stepSaved: true, currentStep: 7);
      await _saveDraft();
      return true;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return false;
    }
  }

  Future<Map<String, dynamic>?> submitStep7() async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    try {
      final id = state.customerId!;
      final res =
          await ApiClient.instance.post('/kyc/application/$id/step7', data: {
        if (state.foNotes.isNotEmpty) 'fo_notes': state.foNotes,
        'application_source': state.applicationSource,
      });
      state = state.copyWith(isSubmitting: false, stepSaved: true);
      await _clearDraft();
      return res.data['data'] as Map<String, dynamic>;
    } catch (e) {
      state = state.copyWith(
          isSubmitting: false,
          error: ApiClient.instance.parseError(e));
      return null;
    }
  }

  Future<void> _saveDraft() async {
    final prefs = await SharedPreferences.getInstance();
    final key = '${AppConstants.draftPrefix}${state.customerId}';
    await prefs.setString(key, jsonEncode({'customer_id': state.customerId, 'step': state.currentStep}));
  }

  Future<void> _clearDraft() async {
    if (state.customerId == null) return;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('${AppConstants.draftPrefix}${state.customerId}');
  }

  void reset() {
    state = const KycDraftState();
  }
}

final kycProvider = StateNotifierProvider<KycNotifier, KycDraftState>(
  (ref) => KycNotifier(),
);
