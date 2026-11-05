import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/money.dart';
import '../../../core/theme.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/sheets.dart';
import '../../clients/client_models.dart';
import '../../products/products_provider.dart';
import '../invoice_draft.dart';

/// Add or edit one line item; pops with the [LineItemDraft].
class LineItemSheet extends StatefulWidget {
  const LineItemSheet({super.key, this.initial});

  final LineItemDraft? initial;

  @override
  State<LineItemSheet> createState() => _LineItemSheetState();
}

class _LineItemSheetState extends State<LineItemSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _description;
  late final TextEditingController _quantity;
  late final TextEditingController _price;

  @override
  void initState() {
    super.initState();
    final initial = widget.initial;
    _description = TextEditingController(text: initial?.description ?? '');
    _quantity = TextEditingController(
      text: initial == null ? '1' : quantityText(initial.quantity),
    );
    _price = TextEditingController(
      text: initial == null || initial.unitPrice == 0
          ? ''
          : initial.unitPrice.toStringAsFixed(2),
    );
    for (final c in [_quantity, _price]) {
      c.addListener(() => setState(() {}));
    }
  }

  @override
  void dispose() {
    _description.dispose();
    _quantity.dispose();
    _price.dispose();
    super.dispose();
  }

  double get _lineTotal => round2(
    (parseAmount(_quantity.text) ?? 0) * (parseAmount(_price.text) ?? 0),
  );

  void _save() {
    if (!_formKey.currentState!.validate()) return;
    Navigator.of(context).pop(
      LineItemDraft(
        description: _description.text.trim(),
        quantity: parseAmount(_quantity.text) ?? 1,
        unitPrice: parseAmount(_price.text) ?? 0,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          SheetTitle(
            widget.initial == null ? 'Add line item' : 'Edit line item',
          ),
          TextFormField(
            controller: _description,
            autofocus: true,
            textCapitalization: TextCapitalization.sentences,
            decoration: const InputDecoration(labelText: 'Description'),
            validator: (v) => (v == null || v.trim().isEmpty)
                ? 'Describe what is being billed.'
                : null,
            maxLines: 2,
            minLines: 1,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _quantity,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  decoration: const InputDecoration(labelText: 'Quantity'),
                  validator: (v) => ((parseAmount(v ?? '') ?? 0) > 0)
                      ? null
                      : 'Must be above zero.',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: _price,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  decoration: const InputDecoration(
                    labelText: 'Unit price',
                    prefixText: r'$ ',
                  ),
                  validator: (v) {
                    final price = parseAmount(v ?? '');
                    if (price == null) return 'Enter a price (0 is fine).';
                    if (price < 0) return 'Cannot be negative.';
                    return null;
                  },
                  onFieldSubmitted: (_) => _save(),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              const Text(
                'Line total  ',
                style: TextStyle(color: AppColors.textMuted),
              ),
              Text(
                money(_lineTotal),
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _save,
            child: Text(widget.initial == null ? 'Add item' : 'Save item'),
          ),
        ],
      ),
    );
  }
}

/// Pick a product from the catalogue; pops with a [LineItemDraft] at its default price.
class ProductPickerSheet extends ConsumerWidget {
  const ProductPickerSheet({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final products = ref.watch(productsProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
      children: [
        const SheetTitle('Add from products'),
        SizedBox(
          height: 360,
          child: products.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () => ref.invalidate(productsProvider),
            ),
            data: (items) => items.isEmpty
                ? const Center(
                    child: Text(
                      'No products yet. Add them under Recurring → Products on the web.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: AppColors.textMuted),
                    ),
                  )
                : ListView(
                    children: [
                      for (final product in items)
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            product.name,
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle:
                              product.description == null ||
                                  product.description!.isEmpty
                              ? null
                              : Text(
                                  product.description!,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                          trailing: Text(
                            '${money(product.defaultUnitPrice)}${product.unit == null || product.unit!.isEmpty ? '' : ' / ${product.unit}'}',
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          onTap: () => Navigator.of(context).pop(
                            LineItemDraft(
                              description: (product.description ?? '').isEmpty
                                  ? product.name
                                  : '${product.name} — ${product.description}',
                              quantity: 1,
                              unitPrice: product.defaultUnitPrice,
                            ),
                          ),
                        ),
                    ],
                  ),
          ),
        ),
      ],
    );
  }
}

/// Apply one of the client's pricing presets; pops with its lines.
class PresetPickerSheet extends StatelessWidget {
  const PresetPickerSheet({super.key, required this.presets});

  final List<PricingPreset> presets;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
      children: [
        const SheetTitle(
          'Apply a pricing preset',
          subtitle: 'Replaces the current line items.',
        ),
        for (final preset in presets)
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(
              preset.name,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              '${preset.items.length} line${preset.items.length == 1 ? '' : 's'}',
            ),
            trailing: Text(
              money(preset.total),
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            onTap: () => Navigator.of(context).pop([
              for (final item in preset.items)
                LineItemDraft(
                  description: item.description,
                  quantity: item.quantity,
                  unitPrice: item.unitPrice,
                ),
            ]),
          ),
      ],
    );
  }
}
