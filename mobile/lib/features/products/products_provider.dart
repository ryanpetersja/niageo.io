import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/json.dart';
import 'product_models.dart';

/// The product catalogue, for adding line items with one tap. Kept for the session.
final productsProvider = FutureProvider<List<Product>>((ref) async {
  final json = await ref.watch(apiClientProvider).getObject('/products');
  return asList(json['data']).map(Product.fromJson).toList();
});
