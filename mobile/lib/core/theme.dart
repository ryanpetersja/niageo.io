import 'package:flutter/material.dart';

import '../features/invoices/invoice_models.dart';

/// The web app's palette (resources/css/app.css), so the phone and the browser feel like one product.
class AppColors {
  AppColors._();

  static const Color bg = Color(0xFF0A0E16);
  static const Color surface = Color(0xFF111826);
  static const Color surface2 = Color(0xFF18212F);
  static const Color surface3 = Color(0xFF1F2937);
  static const Color border = Color(0x1F94A3B8);
  static const Color borderStrong = Color(0x3D94A3B8);
  static const Color text = Color(0xFFE6EAF2);
  static const Color textMuted = Color(0xFF9AA6B8);
  static const Color textFaint = Color(0xFF64748B);
  static const Color accent = Color(0xFF6366F1);
  static const Color accentHover = Color(0xFF4F46E5);
  static const Color accentInk = Color(0xFFA5B4FC);
  static const Color accentSoft = Color(0x246366F1);
  static const Color good = Color(0xFF34D399);
  static const Color goodSoft = Color(0x2434D399);
  static const Color warn = Color(0xFFFBBF24);
  static const Color warnSoft = Color(0x24FBBF24);
  static const Color danger = Color(0xFFFB7185);
  static const Color dangerSoft = Color(0x24F43F5E);
  static const Color info = Color(0xFF38BDF8);
  static const Color infoSoft = Color(0x2438BDF8);
}

/// Badge colours for an invoice status.
({Color fg, Color bg}) statusColors(InvoiceStatus status) => switch (status) {
  InvoiceStatus.draft => (fg: AppColors.textMuted, bg: AppColors.surface3),
  InvoiceStatus.sent => (fg: AppColors.info, bg: AppColors.infoSoft),
  InvoiceStatus.overdue => (fg: AppColors.danger, bg: AppColors.dangerSoft),
  InvoiceStatus.paid => (fg: AppColors.good, bg: AppColors.goodSoft),
  InvoiceStatus.cancelled => (fg: AppColors.warn, bg: AppColors.warnSoft),
};

ThemeData buildAppTheme() {
  const scheme = ColorScheme(
    brightness: Brightness.dark,
    primary: AppColors.accent,
    onPrimary: Colors.white,
    primaryContainer: AppColors.accentSoft,
    onPrimaryContainer: AppColors.accentInk,
    secondary: AppColors.accentInk,
    onSecondary: AppColors.bg,
    tertiary: AppColors.info,
    onTertiary: AppColors.bg,
    error: AppColors.danger,
    onError: AppColors.bg,
    errorContainer: AppColors.dangerSoft,
    onErrorContainer: AppColors.danger,
    surface: AppColors.surface,
    onSurface: AppColors.text,
    onSurfaceVariant: AppColors.textMuted,
    surfaceContainerLowest: AppColors.bg,
    surfaceContainerLow: AppColors.surface,
    surfaceContainer: AppColors.surface2,
    surfaceContainerHigh: AppColors.surface2,
    surfaceContainerHighest: AppColors.surface3,
    outline: AppColors.borderStrong,
    outlineVariant: AppColors.border,
    inverseSurface: AppColors.text,
    onInverseSurface: AppColors.bg,
    inversePrimary: AppColors.accentHover,
    shadow: Colors.black,
    scrim: Colors.black,
  );

  final base = ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    brightness: Brightness.dark,
  );
  final textTheme = base.textTheme.apply(
    bodyColor: AppColors.text,
    displayColor: AppColors.text,
  );

  OutlineInputBorder inputBorder(Color color, [double width = 1]) =>
      OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: color, width: width),
      );

  final roundedShape = RoundedRectangleBorder(
    borderRadius: BorderRadius.circular(12),
  );

  return base.copyWith(
    scaffoldBackgroundColor: AppColors.bg,
    canvasColor: AppColors.bg,
    textTheme: textTheme,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.bg,
      foregroundColor: AppColors.text,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: AppColors.text,
      ),
    ),
    cardTheme: CardThemeData(
      color: AppColors.surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppColors.border),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface2,
      border: inputBorder(AppColors.borderStrong),
      enabledBorder: inputBorder(AppColors.borderStrong),
      focusedBorder: inputBorder(AppColors.accent, 1.5),
      errorBorder: inputBorder(AppColors.danger),
      focusedErrorBorder: inputBorder(AppColors.danger, 1.5),
      labelStyle: const TextStyle(color: AppColors.textMuted),
      floatingLabelStyle: const TextStyle(color: AppColors.accentInk),
      hintStyle: const TextStyle(color: AppColors.textFaint),
      prefixIconColor: AppColors.textMuted,
      suffixIconColor: AppColors.textMuted,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: roundedShape,
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: roundedShape,
        foregroundColor: AppColors.text,
        side: const BorderSide(color: AppColors.borderStrong),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: AppColors.accentInk,
        shape: roundedShape,
      ),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: AppColors.surface2,
      selectedColor: AppColors.accentSoft,
      side: const BorderSide(color: AppColors.borderStrong),
      labelStyle: const TextStyle(
        color: AppColors.text,
        fontWeight: FontWeight.w600,
        fontSize: 13,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      showCheckmark: false,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: AppColors.surface,
      indicatorColor: AppColors.accentSoft,
      surfaceTintColor: Colors.transparent,
      height: 68,
      labelTextStyle: WidgetStateProperty.resolveWith(
        (states) => TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: states.contains(WidgetState.selected)
              ? AppColors.accentInk
              : AppColors.textMuted,
        ),
      ),
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(
          color: states.contains(WidgetState.selected)
              ? AppColors.accentInk
              : AppColors.textMuted,
        ),
      ),
    ),
    bottomSheetTheme: const BottomSheetThemeData(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
      showDragHandle: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
    ),
    dialogTheme: const DialogThemeData(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
    ),
    dividerTheme: const DividerThemeData(
      color: AppColors.border,
      space: 1,
      thickness: 1,
    ),
    snackBarTheme: const SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AppColors.surface3,
      contentTextStyle: TextStyle(color: AppColors.text),
    ),
    listTileTheme: const ListTileThemeData(
      iconColor: AppColors.textMuted,
      textColor: AppColors.text,
    ),
    floatingActionButtonTheme: const FloatingActionButtonThemeData(
      backgroundColor: AppColors.accent,
      foregroundColor: Colors.white,
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(
      color: AppColors.accentInk,
    ),
    datePickerTheme: const DatePickerThemeData(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
    ),
  );
}
