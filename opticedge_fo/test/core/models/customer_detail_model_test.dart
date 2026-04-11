import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/models/customer_model.dart';

void main() {
  test('CustomerDetail parses payment, agreement, and release context', () {
    final detail = CustomerDetail.fromJson({
      'id': '15',
      'full_name': 'Amina Juma',
      'first_name': 'Amina',
      'last_name': 'Juma',
      'phone': '+255712345678',
      'registered_at': '2026-04-11 09:10:11',
      'device': {
        'specs': 'Samsung Galaxy A15 4G 128GB',
        'accessories': [
          {'name': 'Screen Protector', 'offer_type': 'free'},
        ],
      },
      'payment': {
        'status': 'completed',
        'payment_status': 'COMPLETED',
        'amount': 120000,
        'phone': '+255712345678',
        'reference': 'SEL-1001',
        'is_completed': true,
      },
      'agreement': {
        'accepted': true,
        'presented_at': '2026-04-11 09:20:00',
        'active_document': {
          'id': '3',
          'title': 'Device Agreement',
          'url': 'https://example.test/agreement.pdf',
          'mime_type': 'application/pdf',
        },
        'customer_signature_url': 'https://example.test/customer-sign.png',
      },
      'release': {
        'status': 'pending',
        'can_release_asset': true,
        'inventory_unit_id': 'INV-10',
      },
      'phone_metadata': {
        'phone': {
          'iso': 'TZ',
          'e164': '+255712345678',
        },
      },
      'photos': {
        'headshot': 'https://example.test/headshot.jpg',
        'customer_signature': 'https://example.test/customer-sign.png',
      },
      'can_release_asset': true,
    });

    expect(detail.fullName, 'Amina Juma');
    expect(detail.payment?.isCompleted, isTrue);
    expect(detail.payment?.reference, 'SEL-1001');
    expect(detail.agreement?.accepted, isTrue);
    expect(detail.agreement?.activeDocument?.title, 'Device Agreement');
    expect(detail.release?.canReleaseAsset, isTrue);
    expect(detail.phoneMetadata['phone']['iso'], 'TZ');
    expect(detail.photos['customer_signature'],
        'https://example.test/customer-sign.png');
    expect(detail.canReleaseAsset, isTrue);
  });
}
