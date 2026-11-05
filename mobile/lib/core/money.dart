import 'package:intl/intl.dart';

final NumberFormat _currency = NumberFormat.currency(
  locale: 'en_US',
  symbol: r'$',
  decimalDigits: 2,
);

/// "$141,600.00"
String money(num value) => _currency.format(value);

/// "$141.6K" / "$2.3M" for large figures on stat tiles, the full amount below $10k.
String compactMoney(num value) {
  final magnitude = value.abs();
  if (magnitude < 10000) return money(value);
  final (double scaled, String suffix) = magnitude >= 1000000
      ? (magnitude / 1000000, 'M')
      : (magnitude / 1000, 'K');
  var digits = scaled.toStringAsFixed(1);
  if (digits.endsWith('.0')) digits = digits.substring(0, digits.length - 2);
  return '${value < 0 ? '−' : ''}\$$digits$suffix';
}

double round2(double value) => (value * 100).roundToDouble() / 100;

/// Parses what a person types into an amount field ("1,250", "$80", " 45.5 ").
double? parseAmount(String input) {
  final cleaned = input.replaceAll(RegExp(r'[^0-9.\-]'), '');
  if (cleaned.isEmpty) return null;
  return double.tryParse(cleaned);
}

/// Quantities print without a pointless ".0".
String quantityText(double quantity) => quantity == quantity.roundToDouble()
    ? quantity.toStringAsFixed(0)
    : quantity.toString();
