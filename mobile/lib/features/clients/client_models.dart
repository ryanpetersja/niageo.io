import '../../core/json.dart';
import '../invoices/invoice_models.dart';

class Contact {
  const Contact({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.isPrimary = false,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final bool isPrimary;

  factory Contact.fromJson(Map<String, dynamic> json) => Contact(
    id: asInt(json['id']),
    name: asString(json['name']) ?? '',
    email: asString(json['email']),
    phone: asString(json['phone']),
    isPrimary: asBool(json['is_primary']),
  );
}

class PresetItem {
  const PresetItem({
    required this.description,
    required this.quantity,
    required this.unitPrice,
  });

  final String description;
  final double quantity;
  final double unitPrice;

  factory PresetItem.fromJson(Map<String, dynamic> json) => PresetItem(
    description: asString(json['description']) ?? '',
    quantity: asDouble(json['quantity'], 1),
    unitPrice: asDouble(json['unit_price']),
  );
}

/// A saved bundle of line items for a client (e.g. the quarterly hosting package).
class PricingPreset {
  const PricingPreset({
    required this.id,
    required this.name,
    required this.total,
    this.items = const [],
  });

  final int id;
  final String name;
  final double total;
  final List<PresetItem> items;

  factory PricingPreset.fromJson(Map<String, dynamic> json) => PricingPreset(
    id: asInt(json['id']),
    name: asString(json['name']) ?? '',
    total: asDouble(json['total']),
    items: asList(json['items']).map(PresetItem.fromJson).toList(),
  );
}

class Client {
  const Client({
    required this.id,
    required this.companyName,
    this.billingTerms = 'net_30',
    this.billingTermsLabel = 'Net 30',
    this.dueDays = 30,
    this.billingEmail,
    this.notes,
    this.isActive = true,
    this.outstandingBalance = 0,
    this.contacts = const [],
    this.pricingPresets = const [],
    this.recentInvoices = const [],
  });

  final int id;
  final String companyName;
  final String billingTerms;
  final String billingTermsLabel;
  final int dueDays;
  final String? billingEmail;
  final String? notes;
  final bool isActive;
  final double outstandingBalance;
  final List<Contact> contacts;
  final List<PricingPreset> pricingPresets;
  final List<Invoice> recentInvoices;

  Contact? get primaryContact =>
      contacts.where((c) => c.isPrimary).firstOrNull ?? contacts.firstOrNull;

  factory Client.fromJson(Map<String, dynamic> json) => Client(
    id: asInt(json['id']),
    companyName: asString(json['company_name']) ?? '',
    billingTerms: asString(json['billing_terms']) ?? 'net_30',
    billingTermsLabel: asString(json['billing_terms_label']) ?? 'Net 30',
    dueDays: asInt(json['due_days'], 30),
    billingEmail: asString(json['billing_email']),
    notes: asString(json['notes']),
    isActive: asBool(json['is_active'], true),
    outstandingBalance: asDouble(json['outstanding_balance']),
    contacts: asList(json['contacts']).map(Contact.fromJson).toList(),
    pricingPresets: asList(json['pricing_presets'])
        .map(PricingPreset.fromJson)
        .toList(),
    recentInvoices: asList(json['recent_invoices'])
        .map(Invoice.fromJson)
        .toList(),
  );

  InvoiceClient toInvoiceClient() => InvoiceClient(
    id: id,
    companyName: companyName,
    billingEmail: billingEmail,
    billingTerms: billingTerms,
    dueDays: dueDays,
    contacts: contacts,
  );
}

const billingTermsOptions = <String, String>{
  'due_on_receipt': 'Due on receipt',
  'net_15': 'Net 15',
  'net_30': 'Net 30',
  'net_60': 'Net 60',
};
