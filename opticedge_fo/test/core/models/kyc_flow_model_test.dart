import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/models/kyc_flow_model.dart';

void main() {
  group('DeviceModelOption.fromJson', () {
    test('parses retail_price sent as JSON string (Laravel decimal)', () {
      final model = DeviceModelOption.fromJson({
        'id': 'a',
        'brand_id': 'b',
        'brand_name': 'Apple',
        'name': 'iPhone 15',
        'retail_price': '1299000.00',
        'device_specs': 'Apple - iPhone 15',
        'specifications': {'ram': '6GB'},
      });

      expect(model.retailPrice, 1299000.0);
    });

    test('uses empty specifications when API sends a list', () {
      final model = DeviceModelOption.fromJson({
        'id': 'a',
        'brand_id': 'b',
        'brand_name': 'Apple',
        'name': 'iPhone 15',
        'retail_price': 100,
        'device_specs': 'x',
        'specifications': <dynamic>[],
      });

      expect(model.specifications, isEmpty);
    });
  });

  group('InventoryUnitOption.fromJson', () {
    test('parses recommended_cash_price as string', () {
      final unit = InventoryUnitOption.fromJson({
        'id': 'u1',
        'phone_model_id': 'm1',
        'brand_name': 'Apple',
        'model_name': 'iPhone 15',
        'device_specs': 'x',
        'recommended_cash_price': '999000.50',
        'imei_1': '356789012345678',
        'imei_2': null,
        'serial_number': null,
        'status': 'available',
      });

      expect(unit.recommendedCashPrice, 999000.5);
    });
  });
}
