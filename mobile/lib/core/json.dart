/// Tolerant JSON readers: the API sends numbers as numbers, but decimals may
/// arrive as strings from some drivers, and missing keys should not crash a screen.
library;

double asDouble(Object? value, [double fallback = 0]) => switch (value) {
  num n => n.toDouble(),
  String s => double.tryParse(s) ?? fallback,
  _ => fallback,
};

int asInt(Object? value, [int fallback = 0]) => switch (value) {
  int i => i,
  num n => n.toInt(),
  String s => int.tryParse(s) ?? fallback,
  _ => fallback,
};

String? asString(Object? value) => value?.toString();

bool asBool(Object? value, [bool fallback = false]) => switch (value) {
  bool b => b,
  num n => n != 0,
  String s => s == '1' || s.toLowerCase() == 'true',
  _ => fallback,
};

List<Map<String, dynamic>> asList(Object? value) => value is List
    ? value.whereType<Map>().map((m) => Map<String, dynamic>.from(m)).toList()
    : const [];

Map<String, dynamic>? asMap(Object? value) =>
    value is Map ? Map<String, dynamic>.from(value) : null;
