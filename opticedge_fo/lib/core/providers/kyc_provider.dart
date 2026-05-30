import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../config/constants.dart';
import '../api/api_client.dart';
import '../models/customer_model.dart';
import '../services/face_verification_service.dart';
import '../models/dashboard_model.dart';
import '../models/kyc_flow_model.dart';
import '../utils/kyc_upload_limits.dart';

const _unset = Object();
const _pendingSubmissionStorageKey = 'kyc_pending_submission_v1';

bool _isRecoverableNetworkError(Object error) {
  if (error is DioException) {
    return error.type == DioExceptionType.connectionError ||
        error.type == DioExceptionType.sendTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionTimeout;
  }
  return false;
}

String _repaymentValueForApi(String raw) {
  switch (raw) {
    case 'bi-weekly':
      return 'biweekly';
    case 'weekly':
    case 'monthly':
      return raw;
    default:
      return 'weekly';
  }
}

bool _faceMatchAcceptableFromVerification(Map<String, dynamic>? verification) {
  if (verification == null) {
    return false;
  }
  final raw = verification['face_match'];
  if (raw is! Map) {
    return false;
  }
  final status = raw['status']?.toString();

  return status == 'passed' || status == 'manual_verified';
}

double? _faceMatchScoreFromVerification(Map<String, dynamic>? verification) {
  if (verification == null) {
    return null;
  }
  final raw = verification['face_match'];
  if (raw is! Map) {
    return null;
  }
  final score = raw['score'];
  if (score is num) {
    return score.toDouble();
  }

  return double.tryParse(score?.toString() ?? '');
}

String _incomeCycleValueForApi(String raw) {
  switch (raw) {
    case 'bi-weekly':
    case 'biweekly':
      return 'biweekly';
    case 'weekly':
    case 'monthly':
    case 'irregular':
      return raw;
    case 'daily':
      return 'irregular';
    default:
      return 'monthly';
  }
}

class KycDraftState {
  final String? customerId;
  final int currentStep;
  final bool isSubmitting;
  final String? error;
  final bool stepSaved;

  /// Furthest step the user may open in the wizard (synced with server resume + local progress).
  final int maxReachableStep;

  /// Last submit that failed with a retryable network error (step 1–7).
  final int? pendingRetryStep;

  // Step 1 — Device
  final String brandId;
  final String phoneModelId;
  final String inventoryUnitId;
  final String inventorySearch;
  final String deviceSpecs;
  final String imeiNumber;
  final String imei2;
  final String cashPrice;
  final String depositAmount;
  final String preferredRepayment;
  final String loanInterestRate;
  final String loanInterestType;
  final String loanDurationWeeks;
  final String loanGracePeriodDays;
  final bool includeScreenProtector;
  final bool includePhoneCover;
  final String storeOfferNotes;
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

  // Face verification status (from backend)
  final bool faceMatchPassed;
  final double? faceMatchScore;

  // Step 3 — Contact
  final String phone;
  final String phoneCountry;
  final String altPhone;
  final String altPhoneCountry;
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
  final bool isPep;
  final File? businessPhoto;

  // Step 5 — NOK
  final String nokName;
  final String nokPhone;
  final String nokPhoneCountry;
  final String nokRelationship;
  final String nok2Name;
  final String nok2Phone;
  final String nok2PhoneCountry;
  final String nok2Relationship;

  // Step 6 — Consent
  final bool termsAccepted;
  final bool dataConsentAccepted;
  final bool callConsentAccepted;

  // Step 7 — Payment, agreement & submit
  final String paymentPhone;
  final String paymentPhoneCountry;
  final String agreementDecision;
  final String loanTermMonths;
  final String downpaymentAmount;
  final String customerSignatureData;
  final String foSignatureData;
  final File? etrReceiptPhoto;
  final File? assetHandoverList;
  final String assetHandoverNotes;
  final String foNotes;
  final String applicationSource;
  final KycPaymentContext? paymentContext;
  final KycAgreementContext? agreementContext;
  final KycReleaseContext? releaseContext;

  const KycDraftState({
    this.customerId,
    this.currentStep = 1,
    this.isSubmitting = false,
    this.error,
    this.stepSaved = false,
    this.maxReachableStep = 1,
    this.pendingRetryStep,
    this.brandId = '',
    this.phoneModelId = '',
    this.inventoryUnitId = '',
    this.inventorySearch = '',
    this.deviceSpecs = '',
    this.imeiNumber = '',
    this.imei2 = '',
    this.cashPrice = '',
    this.depositAmount = '',
    this.preferredRepayment = 'weekly',
    this.loanInterestRate = '',
    this.loanInterestType = 'flat',
    this.loanDurationWeeks = '',
    this.loanGracePeriodDays = '',
    this.includeScreenProtector = false,
    this.includePhoneCover = false,
    this.storeOfferNotes = '',
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
    this.faceMatchPassed = false,
    this.faceMatchScore,
    this.phone = '',
    this.phoneCountry = 'TZ',
    this.altPhone = '',
    this.altPhoneCountry = 'TZ',
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
    this.isPep = false,
    this.businessPhoto,
    this.nokName = '',
    this.nokPhone = '',
    this.nokPhoneCountry = 'TZ',
    this.nokRelationship = '',
    this.nok2Name = '',
    this.nok2Phone = '',
    this.nok2PhoneCountry = 'TZ',
    this.nok2Relationship = '',
    this.termsAccepted = false,
    this.dataConsentAccepted = false,
    this.callConsentAccepted = false,
    this.paymentPhone = '',
    this.paymentPhoneCountry = 'TZ',
    this.agreementDecision = '',
    this.loanTermMonths = '',
    this.downpaymentAmount = '',
    this.customerSignatureData = '',
    this.foSignatureData = '',
    this.etrReceiptPhoto,
    this.assetHandoverList,
    this.assetHandoverNotes = '',
    this.foNotes = '',
    this.applicationSource = 'walk_in',
    this.paymentContext,
    this.agreementContext,
    this.releaseContext,
  });

  bool get hasDraft => customerId != null && customerId!.isNotEmpty;

  KycDraftState copyWith({
    Object? customerId = _unset,
    int? currentStep,
    bool? isSubmitting,
    Object? error = _unset,
    bool? stepSaved,
    int? maxReachableStep,
    Object? pendingRetryStep = _unset,
    String? brandId,
    String? phoneModelId,
    String? inventoryUnitId,
    String? inventorySearch,
    String? deviceSpecs,
    String? imeiNumber,
    String? imei2,
    String? cashPrice,
    String? depositAmount,
    String? preferredRepayment,
    String? loanInterestRate,
    String? loanInterestType,
    String? loanDurationWeeks,
    String? loanGracePeriodDays,
    bool? includeScreenProtector,
    bool? includePhoneCover,
    String? storeOfferNotes,
    Object? imeiPhoto = _unset,
    Object? deviceBoxPhoto = _unset,
    Object? devicePhoto = _unset,
    String? firstName,
    String? middleName,
    String? lastName,
    String? gender,
    String? dateOfBirth,
    String? nidaNumber,
    String? idType,
    Object? idFrontPhoto = _unset,
    Object? idBackPhoto = _unset,
    Object? headshotPhoto = _unset,
    Object? clientFoPhoto = _unset,
    bool? faceMatchPassed,
    double? faceMatchScore,
    String? phone,
    String? phoneCountry,
    String? altPhone,
    String? altPhoneCountry,
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
    bool? isPep,
    Object? businessPhoto = _unset,
    String? nokName,
    String? nokPhone,
    String? nokPhoneCountry,
    String? nokRelationship,
    String? nok2Name,
    String? nok2Phone,
    String? nok2PhoneCountry,
    String? nok2Relationship,
    bool? termsAccepted,
    bool? dataConsentAccepted,
    bool? callConsentAccepted,
    String? paymentPhone,
    String? paymentPhoneCountry,
    String? agreementDecision,
    String? loanTermMonths,
    String? downpaymentAmount,
    String? customerSignatureData,
    String? foSignatureData,
    Object? etrReceiptPhoto = _unset,
    Object? assetHandoverList = _unset,
    String? assetHandoverNotes,
    String? foNotes,
    String? applicationSource,
    Object? paymentContext = _unset,
    Object? agreementContext = _unset,
    Object? releaseContext = _unset,
  }) {
    return KycDraftState(
      customerId: identical(customerId, _unset)
          ? this.customerId
          : customerId as String?,
      currentStep: currentStep ?? this.currentStep,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: identical(error, _unset) ? this.error : error as String?,
      stepSaved: stepSaved ?? this.stepSaved,
      maxReachableStep: maxReachableStep ?? this.maxReachableStep,
      pendingRetryStep: identical(pendingRetryStep, _unset)
          ? this.pendingRetryStep
          : pendingRetryStep as int?,
      brandId: brandId ?? this.brandId,
      phoneModelId: phoneModelId ?? this.phoneModelId,
      inventoryUnitId: inventoryUnitId ?? this.inventoryUnitId,
      inventorySearch: inventorySearch ?? this.inventorySearch,
      deviceSpecs: deviceSpecs ?? this.deviceSpecs,
      imeiNumber: imeiNumber ?? this.imeiNumber,
      imei2: imei2 ?? this.imei2,
      cashPrice: cashPrice ?? this.cashPrice,
      depositAmount: depositAmount ?? this.depositAmount,
      preferredRepayment: preferredRepayment ?? this.preferredRepayment,
      loanInterestRate: loanInterestRate ?? this.loanInterestRate,
      loanInterestType: loanInterestType ?? this.loanInterestType,
      loanDurationWeeks: loanDurationWeeks ?? this.loanDurationWeeks,
      loanGracePeriodDays: loanGracePeriodDays ?? this.loanGracePeriodDays,
      includeScreenProtector:
          includeScreenProtector ?? this.includeScreenProtector,
      includePhoneCover: includePhoneCover ?? this.includePhoneCover,
      storeOfferNotes: storeOfferNotes ?? this.storeOfferNotes,
      imeiPhoto:
          identical(imeiPhoto, _unset) ? this.imeiPhoto : imeiPhoto as File?,
      deviceBoxPhoto: identical(deviceBoxPhoto, _unset)
          ? this.deviceBoxPhoto
          : deviceBoxPhoto as File?,
      devicePhoto: identical(devicePhoto, _unset)
          ? this.devicePhoto
          : devicePhoto as File?,
      firstName: firstName ?? this.firstName,
      middleName: middleName ?? this.middleName,
      lastName: lastName ?? this.lastName,
      gender: gender ?? this.gender,
      dateOfBirth: dateOfBirth ?? this.dateOfBirth,
      nidaNumber: nidaNumber ?? this.nidaNumber,
      idType: idType ?? this.idType,
      idFrontPhoto: identical(idFrontPhoto, _unset)
          ? this.idFrontPhoto
          : idFrontPhoto as File?,
      idBackPhoto: identical(idBackPhoto, _unset)
          ? this.idBackPhoto
          : idBackPhoto as File?,
      headshotPhoto: identical(headshotPhoto, _unset)
          ? this.headshotPhoto
          : headshotPhoto as File?,
      clientFoPhoto: identical(clientFoPhoto, _unset)
          ? this.clientFoPhoto
          : clientFoPhoto as File?,
      faceMatchPassed: faceMatchPassed ?? this.faceMatchPassed,
      faceMatchScore: faceMatchScore ?? this.faceMatchScore,
      phone: phone ?? this.phone,
      phoneCountry: phoneCountry ?? this.phoneCountry,
      altPhone: altPhone ?? this.altPhone,
      altPhoneCountry: altPhoneCountry ?? this.altPhoneCountry,
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
      isPep: isPep ?? this.isPep,
      businessPhoto: identical(businessPhoto, _unset)
          ? this.businessPhoto
          : businessPhoto as File?,
      nokName: nokName ?? this.nokName,
      nokPhone: nokPhone ?? this.nokPhone,
      nokPhoneCountry: nokPhoneCountry ?? this.nokPhoneCountry,
      nokRelationship: nokRelationship ?? this.nokRelationship,
      nok2Name: nok2Name ?? this.nok2Name,
      nok2Phone: nok2Phone ?? this.nok2Phone,
      nok2PhoneCountry: nok2PhoneCountry ?? this.nok2PhoneCountry,
      nok2Relationship: nok2Relationship ?? this.nok2Relationship,
      termsAccepted: termsAccepted ?? this.termsAccepted,
      dataConsentAccepted: dataConsentAccepted ?? this.dataConsentAccepted,
      callConsentAccepted: callConsentAccepted ?? this.callConsentAccepted,
      paymentPhone: paymentPhone ?? this.paymentPhone,
      paymentPhoneCountry: paymentPhoneCountry ?? this.paymentPhoneCountry,
      agreementDecision: agreementDecision ?? this.agreementDecision,
      loanTermMonths: loanTermMonths ?? this.loanTermMonths,
      downpaymentAmount: downpaymentAmount ?? this.downpaymentAmount,
      customerSignatureData:
          customerSignatureData ?? this.customerSignatureData,
      foSignatureData: foSignatureData ?? this.foSignatureData,
      etrReceiptPhoto: identical(etrReceiptPhoto, _unset)
          ? this.etrReceiptPhoto
          : etrReceiptPhoto as File?,
      assetHandoverList: identical(assetHandoverList, _unset)
          ? this.assetHandoverList
          : assetHandoverList as File?,
      assetHandoverNotes: assetHandoverNotes ?? this.assetHandoverNotes,
      foNotes: foNotes ?? this.foNotes,
      applicationSource: applicationSource ?? this.applicationSource,
      paymentContext: identical(paymentContext, _unset)
          ? this.paymentContext
          : paymentContext as KycPaymentContext?,
      agreementContext: identical(agreementContext, _unset)
          ? this.agreementContext
          : agreementContext as KycAgreementContext?,
      releaseContext: identical(releaseContext, _unset)
          ? this.releaseContext
          : releaseContext as KycReleaseContext?,
    );
  }
}

class KycNotifier extends StateNotifier<KycDraftState> {
  KycNotifier() : super(const KycDraftState()) {
    _restorePendingSubmission();
  }

  int? _queuedSubmissionStep;
  Map<String, dynamic>? _queuedSubmissionPayload;

  void update(KycDraftState Function(KycDraftState current) updater) {
    state = updater(state);
  }

  void setPhoto(String slot, File? file) {
    switch (slot) {
      case 'imei':
        state = state.copyWith(imeiPhoto: file);
        break;
      case 'device_box':
        state = state.copyWith(deviceBoxPhoto: file);
        break;
      case 'device':
        state = state.copyWith(devicePhoto: file);
        break;
      case 'id_front':
        state = state.copyWith(idFrontPhoto: file);
        break;
      case 'id_back':
        state = state.copyWith(idBackPhoto: file);
        break;
      case 'headshot':
        state = state.copyWith(headshotPhoto: file);
        break;
      case 'client_fo':
        state = state.copyWith(clientFoPhoto: file);
        break;
      case 'business':
        state = state.copyWith(businessPhoto: file);
        break;
      case 'etr_receipt':
        state = state.copyWith(etrReceiptPhoto: file);
        break;
      case 'handover':
        state = state.copyWith(assetHandoverList: file);
        break;
    }
  }

  void setSignature(String slot, String dataUrl) {
    if (slot == 'customer') {
      state = state.copyWith(customerSignatureData: dataUrl);
      return;
    }

    if (slot == 'fo') {
      state = state.copyWith(foSignatureData: dataUrl);
    }
  }

  void clearSignature(String slot) {
    if (slot == 'customer') {
      state = state.copyWith(customerSignatureData: '');
      return;
    }

    if (slot == 'fo') {
      state = state.copyWith(foSignatureData: '');
    }
  }

  /// Updates the visible wizard step when navigating back (no API call).
  void setActiveStep(int step) {
    final s = step.clamp(1, 7);
    state = state.copyWith(currentStep: s, stepSaved: false, error: null);
  }

  /// Replays the last step submission after a recoverable network failure.
  Future<bool> retryLastFailedSubmission() async {
    final step = state.pendingRetryStep;
    if (step == null) {
      return false;
    }
    if (_queuedSubmissionStep != null) {
      await processPendingSubmissionQueue();
      return state.pendingRetryStep == null;
    }
    switch (step) {
      case 1:
        return submitStep1();
      case 2:
        return submitStep2();
      case 3:
        return submitStep3();
      case 4:
        return submitStep4();
      case 5:
        return submitStep5();
      case 6:
        return submitStep6();
      case 7:
        return (await submitStep7()) != null;
      default:
        return false;
    }
  }

  void selectBrand(String brandId) {
    final trimmed = brandId.trim();
    state = state.copyWith(
      brandId: trimmed,
      phoneModelId: '',
      inventoryUnitId: '',
      inventorySearch: '',
      deviceSpecs: '',
      cashPrice: '',
      loanInterestRate: '',
      loanInterestType: 'flat',
      loanDurationWeeks: '',
      loanGracePeriodDays: '',
    );
  }

  void selectModel(DeviceModelOption? model) {
    if (model == null) {
      state = state.copyWith(
        phoneModelId: '',
        inventoryUnitId: '',
        deviceSpecs: '',
        cashPrice: '',
        loanInterestRate: '',
        loanInterestType: 'flat',
        loanDurationWeeks: '',
        loanGracePeriodDays: '',
      );
      return;
    }

    state = _applyRecommendedTerms(
      state.copyWith(
        brandId: model.brandId,
        phoneModelId: model.id,
        inventoryUnitId: '',
        deviceSpecs: model.deviceSpecs,
        cashPrice: model.retailPrice?.toString() ?? '',
      ),
      interestRate: model.recommendedInterestRate,
      interestType: model.recommendedInterestType,
      durationWeeks: model.recommendedDurationWeeks,
      gracePeriodDays: model.recommendedGracePeriodDays,
    );
  }

  void selectInventoryUnit(InventoryUnitOption? unit) {
    if (unit == null) {
      state = state.copyWith(
        inventoryUnitId: '',
      );
      return;
    }

    state = _applyRecommendedTerms(
      state.copyWith(
        inventoryUnitId: unit.id,
        deviceSpecs: unit.deviceSpecs,
        cashPrice: unit.recommendedCashPrice?.toString() ?? state.cashPrice,
        imeiNumber: unit.imei1,
        imei2: unit.imei2 ?? '',
      ),
      interestRate: unit.recommendedInterestRate,
      interestType: unit.recommendedInterestType,
      durationWeeks: unit.recommendedDurationWeeks,
      gracePeriodDays: unit.recommendedGracePeriodDays,
    );
  }

  KycDraftState _applyRecommendedTerms(
    KycDraftState current, {
    num? interestRate,
    String? interestType,
    int? durationWeeks,
    int? gracePeriodDays,
  }) {
    return current.copyWith(
      loanInterestRate: interestRate != null
          ? interestRate.toString()
          : current.loanInterestRate,
      loanInterestType: (interestType != null && interestType.isNotEmpty)
          ? interestType
          : current.loanInterestType,
      loanDurationWeeks: durationWeeks?.toString() ?? current.loanDurationWeeks,
      loanGracePeriodDays:
          gracePeriodDays?.toString() ?? current.loanGracePeriodDays,
    );
  }

  Future<void> loadFinalContext() async {
    if (!state.hasDraft) {
      return;
    }

    try {
      final res = await ApiClient.instance
          .get('/kyc/application/${state.customerId}/status');
      final data = res.data['data'] as Map<String, dynamic>;
      final payment = data['payment'] is Map<String, dynamic>
          ? KycPaymentContext.fromJson(data['payment'] as Map<String, dynamic>)
          : null;
      final agreement = data['agreement'] is Map<String, dynamic>
          ? KycAgreementContext.fromJson(
              data['agreement'] as Map<String, dynamic>)
          : null;
      final release = data['release'] is Map<String, dynamic>
          ? KycReleaseContext.fromJson(data['release'] as Map<String, dynamic>)
          : null;

      state = state.copyWith(
        paymentContext: payment,
        agreementContext: agreement,
        releaseContext: release,
        paymentPhone: payment?.phone?.isNotEmpty == true
            ? _normalizeDisplayPhone(payment!.phone!)
            : (state.paymentPhone.isNotEmpty
                ? state.paymentPhone
                : state.phone),
      );
    } catch (_) {
      // We keep the wizard usable even if the context refresh fails.
    }
  }

  Future<bool> requestPaymentPrompt() async {
    if (!state.hasDraft) {
      return false;
    }

    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);

    try {
      final res = await ApiClient.instance
          .post('/kyc/application/${state.customerId}/payment/request', data: {
        'payment_phone':
            state.paymentPhone.isNotEmpty ? state.paymentPhone : state.phone,
        'payment_phone_country': state.paymentPhoneCountry,
      });
      final data = res.data['data'] as Map<String, dynamic>;
      state = state.copyWith(
        isSubmitting: false,
        paymentContext:
            KycPaymentContext.fromJson(data['payment'] as Map<String, dynamic>),
        agreementContext: KycAgreementContext.fromJson(
            data['agreement'] as Map<String, dynamic>),
        releaseContext:
            KycReleaseContext.fromJson(data['release'] as Map<String, dynamic>),
      );
      return true;
    } catch (error) {
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
      );
      return false;
    }
  }

  Future<bool> refreshPaymentStatus({bool showLoading = true}) async {
    if (!state.hasDraft) {
      return false;
    }

    if (showLoading) {
      state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);
    } else {
      state = state.copyWith(error: null);
    }

    try {
      final res = await ApiClient.instance
          .get('/kyc/application/${state.customerId}/payment/status');
      final data = res.data['data'] as Map<String, dynamic>;
      state = state.copyWith(
        isSubmitting: showLoading ? false : state.isSubmitting,
        paymentContext:
            KycPaymentContext.fromJson(data['payment'] as Map<String, dynamic>),
        agreementContext: KycAgreementContext.fromJson(
            data['agreement'] as Map<String, dynamic>),
        releaseContext:
            KycReleaseContext.fromJson(data['release'] as Map<String, dynamic>),
      );
      return true;
    } catch (error) {
      state = state.copyWith(
        isSubmitting: showLoading ? false : state.isSubmitting,
        error: ApiClient.instance.parseError(error),
      );
      return false;
    }
  }

  Future<bool> submitStep1() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    final uploadErr = KycUploadLimits.validateMany([
      (state.imeiPhoto, 'IMEI photo'),
      (state.deviceBoxPhoto, 'Device box photo'),
      (state.devicePhoto, 'Device photo'),
    ]);
    if (uploadErr != null) {
      state = state.copyWith(isSubmitting: false, error: uploadErr);
      return false;
    }

    try {
      final accessories = <Map<String, dynamic>>[];
      if (state.includeScreenProtector) {
        accessories.add({
          'code': 'screen_protector',
          'name': 'Screen Protector',
          'quantity': 1,
          'offer_type': 'free',
        });
      }
      if (state.includePhoneCover) {
        accessories.add({
          'code': 'phone_cover',
          'name': 'Phone Cover',
          'quantity': 1,
          'offer_type': 'free',
        });
      }

      final form = FormData.fromMap({
        if (state.customerId != null && state.customerId!.isNotEmpty)
          'customer_id': state.customerId!,
        if (state.brandId.isNotEmpty) 'brand_id': state.brandId,
        if (state.phoneModelId.isNotEmpty) 'phone_model_id': state.phoneModelId,
        if (state.inventoryUnitId.isNotEmpty)
          'inventory_unit_id': state.inventoryUnitId,
        if (state.deviceSpecs.isNotEmpty) 'device_specs': state.deviceSpecs,
        if (state.imeiNumber.isNotEmpty) 'imei_number': state.imeiNumber,
        if (state.imei2.isNotEmpty) 'imei_2': state.imei2,
        if (state.cashPrice.isNotEmpty) 'cash_price': state.cashPrice,
        'deposit_amount': state.depositAmount,
        'preferred_repayment': _repaymentForApi(state.preferredRepayment),
        if (state.loanInterestRate.isNotEmpty)
          'loan_interest_rate': state.loanInterestRate,
        if (state.loanInterestType.isNotEmpty)
          'loan_interest_type': state.loanInterestType,
        if (state.loanDurationWeeks.isNotEmpty)
          'loan_duration_weeks': state.loanDurationWeeks,
        if (state.loanGracePeriodDays.isNotEmpty)
          'loan_grace_period_days': state.loanGracePeriodDays,
        if (accessories.isNotEmpty) 'accessories': accessories,
        if (state.storeOfferNotes.isNotEmpty)
          'store_offer_notes': state.storeOfferNotes,
        if (state.imeiPhoto != null)
          'imei_photo': await MultipartFile.fromFile(
            state.imeiPhoto!.path,
            filename: 'imei.jpg',
          ),
        if (state.deviceBoxPhoto != null)
          'device_box_photo': await MultipartFile.fromFile(
            state.deviceBoxPhoto!.path,
            filename: 'box.jpg',
          ),
        if (state.devicePhoto != null)
          'device_photo': await MultipartFile.fromFile(
            state.devicePhoto!.path,
            filename: 'device.jpg',
          ),
      });

      final res =
          await ApiClient.instance.postForm('/kyc/application/stage1', form);
      final customerId = res.data['data']['customer_id']?.toString() ?? '';
      state = state.copyWith(
        customerId: customerId,
        isSubmitting: false,
        stepSaved: true,
        currentStep: 2,
        maxReachableStep: math.max(prevMaxReachable, 2),
        pendingRetryStep: null,
      );
      await _saveDraft();
      return true;
    } catch (error) {
      if (_isRecoverableNetworkError(error)) {
        await _queueStep1MultipartRetry();
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 1 : null,
      );
      return false;
    }
  }

  Future<bool> submitStep2() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    final usesFaceScanner =
        state.customerId != null && state.customerId!.isNotEmpty;
    if (usesFaceScanner && !state.faceMatchPassed) {
      state = state.copyWith(
        isSubmitting: false,
        error:
            'Thibitisha uso kwanza (skani) hadi uthibitisho uwe passed/kijani.',
      );

      return false;
    }

    final skipHeadshotFile = usesFaceScanner && state.faceMatchPassed;
    final uploadErr = KycUploadLimits.validateMany([
      (state.idFrontPhoto, 'ID front photo'),
      (state.idBackPhoto, 'ID back photo'),
      if (!skipHeadshotFile) (state.headshotPhoto, 'Headshot'),
      (state.clientFoPhoto, 'Client photo'),
    ]);
    if (uploadErr != null) {
      state = state.copyWith(isSubmitting: false, error: uploadErr);
      return false;
    }

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
            filename: 'id_front.jpg',
          ),
        if (state.idBackPhoto != null)
          'id_back_photo': await MultipartFile.fromFile(
            state.idBackPhoto!.path,
            filename: 'id_back.jpg',
          ),
        if (state.headshotPhoto != null)
          'headshot_photo': await MultipartFile.fromFile(
            state.headshotPhoto!.path,
            filename: 'headshot.jpg',
          ),
        if (state.clientFoPhoto != null)
          'client_fo_photo': await MultipartFile.fromFile(
            state.clientFoPhoto!.path,
            filename: 'client_fo.jpg',
          ),
      });
      await ApiClient.instance.postForm('/kyc/application/$id/step2', form);
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        currentStep: 3,
        maxReachableStep: math.max(prevMaxReachable, 3),
        pendingRetryStep: null,
      );
      await _saveDraft();
      return true;
    } catch (error) {
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 2 : null,
      );
      if (_isRecoverableNetworkError(error) && state.customerId != null) {
        await _queueStep2MultipartRetry(state.customerId!);
      }
      return false;
    }
  }

  Future<bool> submitStep3() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    try {
      final id = state.customerId!;
      final payload = {
        'phone': state.phone,
        'phone_country': state.phoneCountry,
        if (state.altPhone.isNotEmpty) 'alt_phone': state.altPhone,
        'alt_phone_country': state.altPhoneCountry,
        if (state.email.isNotEmpty) 'email': state.email,
        'branch_id': state.branchId,
        if (state.landmark.isNotEmpty) 'landmark': state.landmark,
        if (state.region.isNotEmpty) 'region': state.region,
        if (state.district.isNotEmpty) 'district': state.district,
      };
      await ApiClient.instance
          .post('/kyc/application/$id/step3', data: payload);
      await _clearPendingSubmission();
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        currentStep: 4,
        paymentPhone: state.phone,
        maxReachableStep: math.max(prevMaxReachable, 4),
        pendingRetryStep: null,
      );
      await _saveDraft();
      return true;
    } catch (error) {
      if (_isRecoverableNetworkError(error)) {
        await _queuePendingSubmission(3, {
          'customer_id': state.customerId,
          'data': {
            'phone': state.phone,
            'phone_country': state.phoneCountry,
            if (state.altPhone.isNotEmpty) 'alt_phone': state.altPhone,
            'alt_phone_country': state.altPhoneCountry,
            if (state.email.isNotEmpty) 'email': state.email,
            'branch_id': state.branchId,
            if (state.landmark.isNotEmpty) 'landmark': state.landmark,
            if (state.region.isNotEmpty) 'region': state.region,
            if (state.district.isNotEmpty) 'district': state.district,
          },
        });
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 3 : null,
      );
      return false;
    }
  }

  Future<bool> submitStep4() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    final uploadErr =
        KycUploadLimits.validateMany([(state.businessPhoto, 'Business photo')]);
    if (uploadErr != null) {
      state = state.copyWith(isSubmitting: false, error: uploadErr);
      return false;
    }

    try {
      final id = state.customerId!;
      final form = FormData.fromMap({
        if (state.occupation.isNotEmpty) 'occupation': state.occupation,
        'monthly_income': state.monthlyIncome,
        'income_payment_cycle':
            _incomeCycleValueForApi(state.incomePaymentCycle),
        'is_pep': state.isPep ? '1' : '0',
        if (state.durationAtWork.isNotEmpty)
          'duration_at_work': state.durationAtWork,
        if (state.businessPhoto != null)
          'business_photo': await MultipartFile.fromFile(
            state.businessPhoto!.path,
            filename: 'business.jpg',
          ),
      });
      await ApiClient.instance.postForm('/kyc/application/$id/step4', form);
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        currentStep: 5,
        maxReachableStep: math.max(prevMaxReachable, 5),
        pendingRetryStep: null,
      );
      await _saveDraft();
      return true;
    } catch (error) {
      if (_isRecoverableNetworkError(error) && state.customerId != null) {
        await _queueStep4MultipartRetry(state.customerId!);
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 4 : null,
      );
      return false;
    }
  }

  Future<bool> submitStep5() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    try {
      final id = state.customerId!;
      final payload = {
        'nok_name': state.nokName,
        'nok_phone': state.nokPhone,
        'nok_phone_country': state.nokPhoneCountry,
        'nok_relationship': state.nokRelationship,
        if (state.nok2Name.isNotEmpty) 'nok2_name': state.nok2Name,
        if (state.nok2Phone.isNotEmpty) 'nok2_phone': state.nok2Phone,
        'nok2_phone_country': state.nok2PhoneCountry,
        if (state.nok2Relationship.isNotEmpty)
          'nok2_relationship': state.nok2Relationship,
      };
      await ApiClient.instance
          .post('/kyc/application/$id/step5', data: payload);
      await _clearPendingSubmission();
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        currentStep: 6,
        maxReachableStep: math.max(prevMaxReachable, 6),
        pendingRetryStep: null,
      );
      await _saveDraft();
      return true;
    } catch (error) {
      if (_isRecoverableNetworkError(error)) {
        await _queuePendingSubmission(5, {
          'customer_id': state.customerId,
          'data': {
            'nok_name': state.nokName,
            'nok_phone': state.nokPhone,
            'nok_phone_country': state.nokPhoneCountry,
            'nok_relationship': state.nokRelationship,
            if (state.nok2Name.isNotEmpty) 'nok2_name': state.nok2Name,
            if (state.nok2Phone.isNotEmpty) 'nok2_phone': state.nok2Phone,
            'nok2_phone_country': state.nok2PhoneCountry,
            if (state.nok2Relationship.isNotEmpty)
              'nok2_relationship': state.nok2Relationship,
          },
        });
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 5 : null,
      );
      return false;
    }
  }

  Future<bool> submitStep6() async {
    final prevMaxReachable = state.maxReachableStep;
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    try {
      final id = state.customerId!;
      final payload = {
        'terms_accepted': state.termsAccepted ? '1' : '0',
        'data_consent_accepted': state.dataConsentAccepted ? '1' : '0',
        'call_consent_accepted': state.callConsentAccepted ? '1' : '0',
      };
      await ApiClient.instance
          .post('/kyc/application/$id/step6', data: payload);
      await _clearPendingSubmission();
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        currentStep: 7,
        paymentPhone: state.phone,
        maxReachableStep: math.max(prevMaxReachable, 7),
        pendingRetryStep: null,
      );
      await loadFinalContext();
      await _saveDraft();
      return true;
    } catch (error) {
      if (_isRecoverableNetworkError(error)) {
        await _queuePendingSubmission(6, {
          'customer_id': state.customerId,
          'data': {
            'terms_accepted': state.termsAccepted ? '1' : '0',
            'data_consent_accepted': state.dataConsentAccepted ? '1' : '0',
            'call_consent_accepted': state.callConsentAccepted ? '1' : '0',
          },
        });
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 6 : null,
      );
      return false;
    }
  }

  Future<Map<String, dynamic>?> submitStep7() async {
    state = state.copyWith(
      isSubmitting: true,
      error: null,
      stepSaved: false,
      pendingRetryStep: null,
    );

    final hasHandover = state.assetHandoverList != null ||
        (state.agreementContext?.handoverListUrl?.isNotEmpty ?? false);
    if (!hasHandover) {
      state = state.copyWith(
        isSubmitting: false,
        error:
            'Attach the signed handover checklist (photo or scan) before submitting.',
      );
      return null;
    }

    final uploadErr = KycUploadLimits.validateMany([
      (state.etrReceiptPhoto, 'ETR receipt photo'),
      if (state.assetHandoverList != null)
        (state.assetHandoverList, 'Asset handover document'),
    ]);
    if (uploadErr != null) {
      state = state.copyWith(isSubmitting: false, error: uploadErr);
      return null;
    }

    try {
      final id = state.customerId!;
      final form = FormData.fromMap({
        if (state.foNotes.isNotEmpty) 'fo_notes': state.foNotes,
        'application_source': state.applicationSource,
        'agreement_decision': state.agreementDecision,
        if (state.paymentPhone.isNotEmpty) 'payment_phone': state.paymentPhone,
        if (state.loanTermMonths.isNotEmpty)
          'loan_term_months': state.loanTermMonths,
        if (state.downpaymentAmount.isNotEmpty)
          'downpayment_amount': state.downpaymentAmount,
        'customer_signature': state.customerSignatureData,
        'fo_signature': state.foSignatureData,
        if (state.etrReceiptPhoto != null)
          'etr_receipt_photo': await MultipartFile.fromFile(
            state.etrReceiptPhoto!.path,
            filename: 'etr.${state.etrReceiptPhoto!.path.split('.').last}',
          ),
        if (state.assetHandoverList != null)
          'asset_handover_list': await MultipartFile.fromFile(
            state.assetHandoverList!.path,
            filename:
                'handover.${state.assetHandoverList!.path.split('.').last}',
          ),
        if (state.assetHandoverNotes.isNotEmpty)
          'asset_handover_notes': state.assetHandoverNotes,
      });

      final res = await ApiClient.instance.postForm(
        '/kyc/application/$id/stage3',
        form,
      );
      state = state.copyWith(
        isSubmitting: false,
        stepSaved: true,
        maxReachableStep: 7,
        pendingRetryStep: null,
        paymentContext: res.data['data']['payment'] is Map<String, dynamic>
            ? KycPaymentContext.fromJson(
                res.data['data']['payment'] as Map<String, dynamic>)
            : state.paymentContext,
        agreementContext: res.data['data']['agreement'] is Map<String, dynamic>
            ? KycAgreementContext.fromJson(
                res.data['data']['agreement'] as Map<String, dynamic>)
            : state.agreementContext,
        releaseContext: res.data['data']['release'] is Map<String, dynamic>
            ? KycReleaseContext.fromJson(
                res.data['data']['release'] as Map<String, dynamic>)
            : state.releaseContext,
      );
      await _clearDraft();
      return res.data['data'] as Map<String, dynamic>;
    } catch (error) {
      if (_isRecoverableNetworkError(error) && state.customerId != null) {
        await _queueStep7MultipartRetry(state.customerId!);
      }
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
        pendingRetryStep: _isRecoverableNetworkError(error) ? 7 : null,
      );
      return null;
    }
  }

  /// Persists server-side "listed draft" flag (Drafts tab + dashboard). Call from Step 7 only.
  Future<bool> markSavedAsDraft() async {
    final id = state.customerId;
    if (id == null || id.isEmpty) {
      return false;
    }

    try {
      await ApiClient.instance.post('/kyc/application/$id/save-draft');
      await _saveDraft();
      return true;
    } catch (error) {
      state = state.copyWith(error: ApiClient.instance.parseError(error));
      return false;
    }
  }

  Future<void> processPendingSubmissionQueue() async {
    if (_queuedSubmissionStep == null || _queuedSubmissionPayload == null) {
      return;
    }

    final step = _queuedSubmissionStep!;
    final payload = _queuedSubmissionPayload!;
    final customerId = payload['customer_id']?.toString();
    if (step != 1 && (customerId == null || customerId.isEmpty)) {
      await _clearPendingSubmission();
      return;
    }

    final resolvedCustomerId = customerId ?? '';

    try {
      state = state.copyWith(isSubmitting: true, error: null);
      switch (step) {
        case 2:
          await _postQueuedStep2Multipart(resolvedCustomerId, payload);
          break;
        case 3:
          await ApiClient.instance.post(
            '/kyc/application/$resolvedCustomerId/step3',
            data: payload['data'],
          );
          break;
        case 5:
          await ApiClient.instance.post(
            '/kyc/application/$resolvedCustomerId/step5',
            data: payload['data'],
          );
          break;
        case 6:
          await ApiClient.instance.post(
            '/kyc/application/$resolvedCustomerId/step6',
            data: payload['data'],
          );
          break;
        case 1:
          await _postQueuedStep1Multipart(payload);
          break;
        case 4:
          await _postQueuedStep4Multipart(resolvedCustomerId, payload);
          break;
        case 7:
          await _postQueuedStep7Multipart(resolvedCustomerId, payload);
          break;
        default:
          await _clearPendingSubmission();
          return;
      }

      await _clearPendingSubmission();
      state = state.copyWith(
        isSubmitting: false,
        pendingRetryStep: null,
      );
    } catch (error) {
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
      );
    }
  }

  Future<void> _restorePendingSubmission() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_pendingSubmissionStorageKey);
    if (raw == null || raw.isEmpty) {
      return;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) {
        await prefs.remove(_pendingSubmissionStorageKey);
        return;
      }
      final map = Map<String, dynamic>.from(decoded);
      final step = map['step'];
      final payload = map['payload'];
      if (step is int && payload is Map) {
        _queuedSubmissionStep = step;
        _queuedSubmissionPayload = Map<String, dynamic>.from(payload);
        state = state.copyWith(pendingRetryStep: step);
      } else {
        await prefs.remove(_pendingSubmissionStorageKey);
      }
    } catch (_) {
      await prefs.remove(_pendingSubmissionStorageKey);
    }
  }

  Future<void> _queuePendingSubmission(
      int step, Map<String, dynamic> payload) async {
    _queuedSubmissionStep = step;
    _queuedSubmissionPayload = payload;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _pendingSubmissionStorageKey,
      jsonEncode({
        'step': step,
        'payload': payload,
      }),
    );
  }

  Future<void> _clearPendingSubmission() async {
    _queuedSubmissionStep = null;
    _queuedSubmissionPayload = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_pendingSubmissionStorageKey);
  }

  Future<void> _queueStep2MultipartRetry(String customerId) async {
    await _queuePendingSubmission(2, {
      'customer_id': customerId,
      'fields': {
        'first_name': state.firstName,
        if (state.middleName.isNotEmpty) 'middle_name': state.middleName,
        'last_name': state.lastName,
        'gender': state.gender,
        if (state.dateOfBirth.isNotEmpty) 'date_of_birth': state.dateOfBirth,
        'nida_number': state.nidaNumber,
        'id_type': state.idType,
      },
      'files': {
        if (state.idFrontPhoto != null)
          'id_front_photo': state.idFrontPhoto!.path,
        if (state.idBackPhoto != null)
          'id_back_photo': state.idBackPhoto!.path,
        if (state.headshotPhoto != null)
          'headshot_photo': state.headshotPhoto!.path,
        if (state.clientFoPhoto != null)
          'client_fo_photo': state.clientFoPhoto!.path,
      },
    });
  }

  Future<void> _queueStep1MultipartRetry() async {
    final accessories = <Map<String, dynamic>>[];
    if (state.includeScreenProtector) {
      accessories.add({
        'code': 'screen_protector',
        'name': 'Screen Protector',
        'quantity': 1,
        'offer_type': 'free',
      });
    }
    if (state.includePhoneCover) {
      accessories.add({
        'code': 'phone_cover',
        'name': 'Phone Cover',
        'quantity': 1,
        'offer_type': 'free',
      });
    }

    await _queuePendingSubmission(1, {
      'customer_id': state.customerId,
      'endpoint': '/kyc/application/step1',
      'fields': {
        if (state.customerId != null && state.customerId!.isNotEmpty)
          'customer_id': state.customerId!,
        if (state.brandId.isNotEmpty) 'brand_id': state.brandId,
        if (state.phoneModelId.isNotEmpty) 'phone_model_id': state.phoneModelId,
        if (state.inventoryUnitId.isNotEmpty)
          'inventory_unit_id': state.inventoryUnitId,
        if (state.deviceSpecs.isNotEmpty) 'device_specs': state.deviceSpecs,
        if (state.imeiNumber.isNotEmpty) 'imei_number': state.imeiNumber,
        if (state.imei2.isNotEmpty) 'imei_2': state.imei2,
        if (state.cashPrice.isNotEmpty) 'cash_price': state.cashPrice,
        'deposit_amount': state.depositAmount,
        'preferred_repayment': _repaymentForApi(state.preferredRepayment),
        if (state.loanInterestRate.isNotEmpty)
          'loan_interest_rate': state.loanInterestRate,
        if (state.loanInterestType.isNotEmpty)
          'loan_interest_type': state.loanInterestType,
        if (state.loanDurationWeeks.isNotEmpty)
          'loan_duration_weeks': state.loanDurationWeeks,
        if (state.loanGracePeriodDays.isNotEmpty)
          'loan_grace_period_days': state.loanGracePeriodDays,
        if (accessories.isNotEmpty) 'accessories': accessories,
        if (state.storeOfferNotes.isNotEmpty)
          'store_offer_notes': state.storeOfferNotes,
      },
      'files': {
        if (state.imeiPhoto != null) 'imei_photo': state.imeiPhoto!.path,
        if (state.deviceBoxPhoto != null)
          'device_box_photo': state.deviceBoxPhoto!.path,
        if (state.devicePhoto != null) 'device_photo': state.devicePhoto!.path,
      },
    });
  }

  Future<void> _queueStep4MultipartRetry(String customerId) async {
    await _queuePendingSubmission(4, {
      'customer_id': customerId,
      'endpoint': '/kyc/application/$customerId/step4',
      'fields': {
        if (state.occupation.isNotEmpty) 'occupation': state.occupation,
        'monthly_income': state.monthlyIncome,
        'income_payment_cycle':
            _incomeCycleValueForApi(state.incomePaymentCycle),
        'is_pep': state.isPep ? '1' : '0',
        if (state.durationAtWork.isNotEmpty)
          'duration_at_work': state.durationAtWork,
      },
      'files': {
        if (state.businessPhoto != null)
          'business_photo': state.businessPhoto!.path,
      },
    });
  }

  Future<void> _queueStep7MultipartRetry(String customerId) async {
    await _queuePendingSubmission(7, {
      'customer_id': customerId,
      'endpoint': '/kyc/application/$customerId/step7',
      'fields': {
        if (state.foNotes.isNotEmpty) 'fo_notes': state.foNotes,
        'application_source': state.applicationSource,
        'agreement_decision': state.agreementDecision,
        if (state.paymentPhone.isNotEmpty) 'payment_phone': state.paymentPhone,
        if (state.loanTermMonths.isNotEmpty)
          'loan_term_months': state.loanTermMonths,
        if (state.downpaymentAmount.isNotEmpty)
          'downpayment_amount': state.downpaymentAmount,
        'customer_signature': state.customerSignatureData,
        'fo_signature': state.foSignatureData,
        if (state.assetHandoverNotes.isNotEmpty)
          'asset_handover_notes': state.assetHandoverNotes,
      },
      'files': {
        if (state.etrReceiptPhoto != null)
          'etr_receipt_photo': state.etrReceiptPhoto!.path,
        if (state.assetHandoverList != null)
          'asset_handover_list': state.assetHandoverList!.path,
      },
    });
  }

  Future<void> _postQueuedMultipart(
    String endpoint,
    Map<String, dynamic> payload,
  ) async {
    final fields = payload['fields'];
    final files = payload['files'];
    if (fields is! Map) {
      throw StateError('Invalid queued multipart payload');
    }

    final map = <String, dynamic>{
      for (final entry in Map<String, dynamic>.from(fields).entries)
        entry.key: entry.value,
    };

    if (files is Map) {
      for (final entry in Map<String, dynamic>.from(files).entries) {
        final path = entry.value?.toString();
        if (path == null || path.isEmpty) {
          continue;
        }
        final file = File(path);
        if (!await file.exists()) {
          continue;
        }
        map[entry.key] = await MultipartFile.fromFile(
          path,
          filename: '${entry.key}.jpg',
        );
      }
    }

    await ApiClient.instance.postForm(endpoint, FormData.fromMap(map));
  }

  Future<void> _postQueuedStep1Multipart(Map<String, dynamic> payload) async {
    final endpoint = payload['endpoint']?.toString() ?? '/kyc/application/step1';
    await _postQueuedMultipart(endpoint, payload);
  }

  Future<void> _postQueuedStep4Multipart(
    String customerId,
    Map<String, dynamic> payload,
  ) async {
    final endpoint =
        payload['endpoint']?.toString() ?? '/kyc/application/$customerId/step4';
    await _postQueuedMultipart(endpoint, payload);
  }

  Future<void> _postQueuedStep7Multipart(
    String customerId,
    Map<String, dynamic> payload,
  ) async {
    final endpoint =
        payload['endpoint']?.toString() ?? '/kyc/application/$customerId/step7';
    await _postQueuedMultipart(endpoint, payload);
  }

  Future<void> _postQueuedStep2Multipart(
    String customerId,
    Map<String, dynamic> payload,
  ) async {
    await _postQueuedMultipart('/kyc/application/$customerId/step2', payload);
  }

  Future<void> _saveDraft() async {
    final prefs = await SharedPreferences.getInstance();
    final key = '${AppConstants.draftPrefix}${state.customerId}';
    await prefs.setString(
      key,
      jsonEncode({
        'customer_id': state.customerId,
        'step': state.currentStep,
      }),
    );
  }

  Future<void> _clearDraft() async {
    if (state.customerId == null) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('${AppConstants.draftPrefix}${state.customerId}');
  }

  void reset() {
    unawaited(_clearPendingSubmission());
    state = const KycDraftState();
  }

  Future<bool> loadExistingDraft(String customerId) async {
    state = state.copyWith(isSubmitting: true, error: null, stepSaved: false);

    try {
      final response =
          await ApiClient.instance.get('/kyc/customers/$customerId');
      final detail = CustomerDetail.fromJson(
        response.data['data'] as Map<String, dynamic>,
      );
      final resumeStep = switch (detail.resumeStage) {
        1 => 1,
        2 => 2,
        3 => 7,
        _ => detail.resumeStep.clamp(1, 7),
      };

      state = KycDraftState(
        customerId: detail.id,
        currentStep: resumeStep,
        brandId: detail.device['brand_id']?.toString() ?? '',
        phoneModelId: detail.device['phone_model_id']?.toString() ?? '',
        inventoryUnitId: detail.device['inventory_unit_id']?.toString() ?? '',
        deviceSpecs: detail.device['specs']?.toString() ?? '',
        imeiNumber: detail.device['imei_1']?.toString() ?? '',
        imei2: detail.device['imei_2']?.toString() ?? '',
        cashPrice: detail.device['cash_price']?.toString() ?? '',
        depositAmount: detail.device['deposit_amount']?.toString() ?? '',
        preferredRepayment:
            _repaymentFromApi(detail.device['preferred_repayment']?.toString()),
        loanInterestRate: detail.device['loan_interest_rate']?.toString() ?? '',
        loanInterestType:
            detail.device['loan_interest_type']?.toString() ?? 'flat',
        loanDurationWeeks:
            detail.device['loan_duration_weeks']?.toString() ?? '',
        loanGracePeriodDays:
            detail.device['loan_grace_period_days']?.toString() ?? '',
        includeScreenProtector:
            _hasAccessory(detail.device, 'screen_protector'),
        includePhoneCover: _hasAccessory(detail.device, 'phone_cover'),
        storeOfferNotes: detail.device['store_offer_notes']?.toString() ?? '',
        firstName: detail.firstName,
        middleName: detail.middleName ?? '',
        lastName: detail.lastName,
        gender: detail.gender ?? 'male',
        dateOfBirth: detail.dateOfBirth ?? '',
        nidaNumber: detail.nidaNumber ?? '',
        idType: detail.idType ?? 'nida',
        phone: detail.phone,
        phoneCountry:
            detail.phoneMetadata['phone']?['country_iso']?.toString() ?? 'TZ',
        altPhone: detail.altPhone ?? '',
        altPhoneCountry:
            detail.phoneMetadata['alt_phone']?['country_iso']?.toString() ??
                'TZ',
        email: detail.email ?? '',
        branchId: detail.branch?['id']?.toString() ?? '',
        address: detail.address ?? '',
        landmark: detail.landmark ?? '',
        region: detail.region ?? '',
        district: detail.district ?? '',
        occupation: detail.income['occupation']?.toString() ?? '',
        employer: detail.income['employer']?.toString() ?? '',
        workLocation: detail.income['work_location']?.toString() ?? '',
        monthlyIncome: detail.income['monthly_income']?.toString() ?? '',
        monthlyExpenses: detail.income['monthly_expenses']?.toString() ?? '',
        incomePaymentCycle: _incomeCycleFromApi(
          detail.income['income_payment_cycle']?.toString(),
        ),
        durationAtWork: detail.income['duration_at_work']?.toString() ?? '',
        nokName: detail.nok['nok_name']?.toString() ?? '',
        nokPhone: detail.nok['nok_phone']?.toString() ?? '',
        nokPhoneCountry:
            detail.phoneMetadata['nok_phone']?['country_iso']?.toString() ??
                'TZ',
        nokRelationship: detail.nok['nok_relationship']?.toString() ?? '',
        nok2Name: detail.nok['nok2_name']?.toString() ?? '',
        nok2Phone: detail.nok['nok2_phone']?.toString() ?? '',
        nok2PhoneCountry:
            detail.phoneMetadata['nok2_phone']?['country_iso']?.toString() ??
                'TZ',
        nok2Relationship: detail.nok['nok2_relationship']?.toString() ?? '',
        termsAccepted: detail.consent['terms_accepted'] == true,
        dataConsentAccepted: detail.consent['data_consent_accepted'] == true,
        callConsentAccepted: detail.consent['call_consent_accepted'] == true,
        paymentPhone: detail.payment?.phone ?? detail.phone,
        paymentPhoneCountry:
            detail.phoneMetadata['payment_phone']?['country_iso']?.toString() ??
                detail.phoneMetadata['phone']?['country_iso']?.toString() ??
                'TZ',
        agreementDecision: detail.agreement?.accepted == true ? 'yes' : '',
        foNotes: detail.foNotes ?? '',
        applicationSource: detail.applicationSource ?? 'walk_in',
        assetHandoverNotes: detail.agreement?.handoverNotes ?? '',
        paymentContext: detail.payment,
        agreementContext: detail.agreement,
        releaseContext: detail.release,
        isSubmitting: false,
        maxReachableStep: resumeStep,
        pendingRetryStep: null,
        faceMatchPassed: _faceMatchAcceptableFromVerification(
          detail.verification,
        ),
        faceMatchScore: _faceMatchScoreFromVerification(detail.verification),
      );

      await processPendingSubmissionQueue();

      return true;
    } catch (error) {
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(error),
      );
      return false;
    }
  }

  String _repaymentForApi(String raw) {
    switch (raw) {
      case 'bi-weekly':
        return 'biweekly';
      case 'weekly':
      case 'monthly':
        return raw;
      default:
        return 'weekly';
    }
  }

  String _normalizeDisplayPhone(String raw) {
    if (raw.startsWith('255') && raw.length >= 12) {
      return '+${raw.substring(0, 3)} ${raw.substring(3, 6)} ${raw.substring(6, 9)} ${raw.substring(9)}';
    }
    return raw;
  }

  String _repaymentFromApi(String? raw) {
    switch (raw) {
      case 'biweekly':
        return 'bi-weekly';
      case 'monthly':
      case 'weekly':
        return raw!;
      default:
        return 'weekly';
    }
  }

  String _incomeCycleFromApi(String? raw) {
    switch (raw) {
      case 'weekly':
      case 'biweekly':
      case 'monthly':
      case 'irregular':
        return raw!;
      default:
        return 'monthly';
    }
  }

  bool _hasAccessory(Map<String, dynamic> device, String code) {
    final accessories = device['accessories'];
    if (accessories is! List) {
      return false;
    }

    return accessories.any((item) {
      if (item is! Map) {
        return false;
      }

      return item['code']?.toString() == code;
    });
  }

  /// Refreshes face match flags from the API (e.g. after scanner closes or manual back-office approval).
  Future<void> syncFaceMatchFromServer(String customerId) async {
    if (customerId.isEmpty) {
      return;
    }

    try {
      final status =
          await FaceVerificationService.instance.getStatus(customerId);
      final fm = status.faceMatch;
      final acceptable = fm != null &&
          (fm.status == 'passed' || fm.status == 'manual_verified');
      state = state.copyWith(
        faceMatchPassed: acceptable,
        faceMatchScore: fm?.score,
      );
    } on DioException {
      // Keep existing draft state on transient network errors.
    }
  }
}

final phoneCountriesProvider =
    FutureProvider<List<PhoneCountryOption>>((ref) async {
  final res = await ApiClient.instance.get('/kyc/application/phone-countries');
  final data = res.data['data'] as List<dynamic>;
  return data
      .map((item) => PhoneCountryOption.fromJson(item as Map<String, dynamic>))
      .toList();
});

final deviceBrandsProvider =
    FutureProvider<List<DeviceBrandOption>>((ref) async {
  final res = await ApiClient.instance.get('/kyc/application/device/brands');
  final data = res.data['data'] as List<dynamic>;
  return data
      .map((item) => DeviceBrandOption.fromJson(item as Map<String, dynamic>))
      .toList();
});

final deviceModelsProvider = FutureProvider.family<List<DeviceModelOption>,
    ({String brandId, String preferredRepayment})>((ref, args) async {
  final id = args.brandId.trim();
  if (id.isEmpty) {
    return [];
  }

  final res = await ApiClient.instance.get(
    '/kyc/application/device/models',
    queryParameters: {
      'brand_id': id,
      if (args.preferredRepayment.trim().isNotEmpty)
        'preferred_repayment': _repaymentValueForApi(args.preferredRepayment),
    },
  );
  final data = res.data['data'] as List<dynamic>;
  return data
      .map((item) => DeviceModelOption.fromJson(item as Map<String, dynamic>))
      .toList();
});

final inventoryUnitsProvider = FutureProvider.family<
    List<InventoryUnitOption>,
    ({
      String phoneModelId,
      String search,
      String preferredRepayment
    })>((ref, args) async {
  final modelId = args.phoneModelId.trim();
  if (modelId.isEmpty) {
    return [];
  }

  final res = await ApiClient.instance.get(
    '/kyc/application/device/inventory',
    queryParameters: {
      'phone_model_id': modelId,
      if (args.search.trim().isNotEmpty) 'search': args.search.trim(),
      if (args.preferredRepayment.trim().isNotEmpty)
        'preferred_repayment': _repaymentValueForApi(args.preferredRepayment),
    },
  );
  final data = res.data['data'] as List<dynamic>;
  return data
      .map((item) => InventoryUnitOption.fromJson(item as Map<String, dynamic>))
      .toList();
});

/// Branches were removed — dealer context replaces branch selection in the wizard.
final branchesProvider = FutureProvider<List<BranchModel>>((ref) async => []);

final kycProvider = StateNotifierProvider<KycNotifier, KycDraftState>(
  (ref) => KycNotifier(),
);
