import '../../core/json.dart';

class Product {
  const Product({
    required this.id,
    required this.name,
    this.description,
    this.unit,
    required this.defaultUnitPrice,
  });

  final int id;
  final String name;
  final String? description;
  final String? unit;
  final double defaultUnitPrice;

  factory Product.fromJson(Map<String, dynamic> json) => Product(
    id: asInt(json['id']),
    name: asString(json['name']) ?? '',
    description: asString(json['description']),
    unit: asString(json['unit']),
    defaultUnitPrice: asDouble(json['default_unit_price']),
  );
}
