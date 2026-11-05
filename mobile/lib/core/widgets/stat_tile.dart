import 'package:flutter/material.dart';

import '../theme.dart';

/// A headline figure: label on top, big number, small caption.
class StatTile extends StatelessWidget {
  const StatTile({
    super.key,
    required this.label,
    required this.value,
    this.caption,
    this.color = AppColors.text,
    this.onTap,
  });

  final String label;
  final String value;
  final String? caption;
  final Color color;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                label.toUpperCase(),
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 0.6,
                  color: AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 8),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  value,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: color,
                    height: 1.1,
                  ),
                ),
              ),
              if (caption != null) ...[
                const SizedBox(height: 6),
                Text(
                  caption!,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textFaint,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
