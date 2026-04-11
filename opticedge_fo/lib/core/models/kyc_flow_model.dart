class PhoneCountryOption {
  final String iso;
  final String name;
  final String dialCode;
  final String flag;

  const PhoneCountryOption({
    required this.iso,
    required this.name,
    required this.dialCode,
    required this.flag,
  });

  String get label => '$flag $name ($dialCode)';

  factory PhoneCountryOption.fromJson(Map<String, dynamic> json) =>
      PhoneCountryOption(
        iso: json['iso']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        dialCode: json['dial_code']?.toString() ?? '',
        flag: json['flag']?.toString() ?? '🌍',
      );
}

class DeviceBrandOption {
  final String id;
  final String name;

  const DeviceBrandOption({
    required this.id,
    required this.name,
  });

  factory DeviceBrandOption.fromJson(Map<String, dynamic> json) =>
      DeviceBrandOption(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
      );
}

class DeviceModelOption {
  final String id;
  final String brandId;
  final String brandName;
  final String name;
  final num? retailPrice;
  final String deviceSpecs;
  final Map<String, dynamic> specifications;

  const DeviceModelOption({
    required this.id,
    required this.brandId,
    required this.brandName,
    required this.name,
    required this.retailPrice,
    required this.deviceSpecs,
    this.specifications = const {},
  });

  factory DeviceModelOption.fromJson(Map<String, dynamic> json) =>
      DeviceModelOption(
        id: json['id']?.toString() ?? '',
        brandId: json['brand_id']?.toString() ?? '',
        brandName: json['brand_name']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        retailPrice: json['retail_price'] as num?,
        deviceSpecs: json['device_specs']?.toString() ?? '',
        specifications:
            json['specifications'] as Map<String, dynamic>? ?? const {},
      );
}

class InventoryUnitOption {
  final String id;
  final String phoneModelId;
  final String brandName;
  final String modelName;
  final String deviceSpecs;
  final num? recommendedCashPrice;
  final String imei1;
  final String? imei2;
  final String? serialNumber;
  final String status;

  const InventoryUnitOption({
    required this.id,
    required this.phoneModelId,
    required this.brandName,
    required this.modelName,
    required this.deviceSpecs,
    required this.recommendedCashPrice,
    required this.imei1,
    this.imei2,
    this.serialNumber,
    required this.status,
  });

  String get title =>
      [brandName, modelName].where((part) => part.isNotEmpty).join(' ');

  String get subtitle {
    final parts = <String>[imei1];
    if (serialNumber != null && serialNumber!.isNotEmpty) {
      parts.add(serialNumber!);
    }
    if (status.isNotEmpty) {
      parts.add(status.replaceAll('_', ' ').toUpperCase());
    }
    return parts.join(' • ');
  }

  factory InventoryUnitOption.fromJson(Map<String, dynamic> json) =>
      InventoryUnitOption(
        id: json['id']?.toString() ?? '',
        phoneModelId: json['phone_model_id']?.toString() ?? '',
        brandName: json['brand_name']?.toString() ?? '',
        modelName: json['model_name']?.toString() ?? '',
        deviceSpecs: json['device_specs']?.toString() ?? '',
        recommendedCashPrice: json['recommended_cash_price'] as num?,
        imei1: json['imei_1']?.toString() ?? '',
        imei2: json['imei_2']?.toString(),
        serialNumber: json['serial_number']?.toString(),
        status: json['status']?.toString() ?? '',
      );
}

class KycDocumentOption {
  final String id;
  final String title;
  final String url;
  final String? mimeType;
  final String? uploadedAt;
  final String? originalName;

  const KycDocumentOption({
    required this.id,
    required this.title,
    required this.url,
    this.mimeType,
    this.uploadedAt,
    this.originalName,
  });

  factory KycDocumentOption.fromJson(Map<String, dynamic> json) =>
      KycDocumentOption(
        id: json['id']?.toString() ?? '',
        title: json['title']?.toString() ?? '',
        url: json['url']?.toString() ?? '',
        mimeType: json['mime_type']?.toString(),
        uploadedAt: json['uploaded_at']?.toString(),
        originalName: json['original_name']?.toString(),
      );
}

class KycPaymentContext {
  final String status;
  final String paymentStatus;
  final String? result;
  final String? resultCode;
  final num? amount;
  final String? phone;
  final String? reference;
  final String? orderId;
  final String? transId;
  final String? paidAt;
  final String? updatedAt;
  final bool isCompleted;

  const KycPaymentContext({
    this.status = 'not_started',
    this.paymentStatus = '',
    this.result,
    this.resultCode,
    this.amount,
    this.phone,
    this.reference,
    this.orderId,
    this.transId,
    this.paidAt,
    this.updatedAt,
    this.isCompleted = false,
  });

  bool get needsAction =>
      status == 'pending' || status == 'initiated' || status == 'order_created';

  factory KycPaymentContext.fromJson(Map<String, dynamic> json) =>
      KycPaymentContext(
        status: json['status']?.toString() ?? 'not_started',
        paymentStatus: json['payment_status']?.toString() ?? '',
        result: json['result']?.toString(),
        resultCode: json['resultcode']?.toString(),
        amount: json['amount'] as num?,
        phone: json['phone']?.toString(),
        reference: json['reference']?.toString(),
        orderId: json['order_id']?.toString(),
        transId: json['transid']?.toString(),
        paidAt: json['paid_at']?.toString(),
        updatedAt: json['updated_at']?.toString(),
        isCompleted: json['is_completed'] == true,
      );
}

class KycAgreementContext {
  final KycDocumentOption? activeDocument;
  final bool accepted;
  final String? presentedAt;
  final String? decisionAt;
  final String? customerSignatureUrl;
  final String? foSignatureUrl;
  final String? handoverListUrl;
  final String? handoverNotes;

  const KycAgreementContext({
    this.activeDocument,
    this.accepted = false,
    this.presentedAt,
    this.decisionAt,
    this.customerSignatureUrl,
    this.foSignatureUrl,
    this.handoverListUrl,
    this.handoverNotes,
  });

  factory KycAgreementContext.fromJson(Map<String, dynamic> json) =>
      KycAgreementContext(
        activeDocument: json['active_document'] is Map<String, dynamic>
            ? KycDocumentOption.fromJson(
                json['active_document'] as Map<String, dynamic>)
            : null,
        accepted: json['accepted'] == true,
        presentedAt: json['presented_at']?.toString(),
        decisionAt: json['decision_at']?.toString(),
        customerSignatureUrl: json['customer_signature_url']?.toString(),
        foSignatureUrl: json['fo_signature_url']?.toString(),
        handoverListUrl: json['handover_list_url']?.toString(),
        handoverNotes: json['handover_notes']?.toString(),
      );
}

class KycReleaseContext {
  final String status;
  final String? releasedAt;
  final String? releasedBy;
  final bool canReleaseAsset;
  final String? inventoryUnitId;
  final String? inventoryUnitStatus;

  const KycReleaseContext({
    this.status = 'pending',
    this.releasedAt,
    this.releasedBy,
    this.canReleaseAsset = false,
    this.inventoryUnitId,
    this.inventoryUnitStatus,
  });

  factory KycReleaseContext.fromJson(Map<String, dynamic> json) =>
      KycReleaseContext(
        status: json['status']?.toString() ?? 'pending',
        releasedAt: json['released_at']?.toString(),
        releasedBy: json['released_by']?.toString(),
        canReleaseAsset: json['can_release_asset'] == true,
        inventoryUnitId: json['inventory_unit_id']?.toString(),
        inventoryUnitStatus: json['inventory_unit_status']?.toString(),
      );
}
