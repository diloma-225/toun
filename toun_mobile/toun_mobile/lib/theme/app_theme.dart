import 'package:flutter/material.dart';

class TounColors {
  TounColors._();

  static const Color teal = Color(0xFF20A1B8);
  static const Color blue = Color(0xFF3F68AE);
  static const Color green = Color(0xFF1C9A82);
  static const Color gray = Color(0xFF77899A);
  static const Color purple = Color(0xFF536FC3);
  static const Color navy = Color(0xFF18213F);

  static const Color background = Color(0xFFEAF2F8);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color border = Color(0xFFDDE8EE);
  static const Color textPrimary = Color(0xFF17345B);
  static const Color textSecondary = Color(0xFF77899A);

  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [teal, blue, navy],
  );

  static const LinearGradient headerGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF0F3F78), Color(0xFF0B5F9E), Color(0xFF0980C2)],
  );

  static const LinearGradient buttonGradient = LinearGradient(
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
    colors: [teal, blue],
  );
}

class AppTheme {
  AppTheme._();

  static ThemeData get light {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: TounColors.background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: TounColors.teal,
        primary: TounColors.teal,
        secondary: TounColors.blue,
      ),
      textTheme: const TextTheme(
        headlineSmall: TextStyle(
          fontWeight: FontWeight.w700,
          color: TounColors.textPrimary,
        ),
        titleLarge: TextStyle(
          fontWeight: FontWeight.w700,
          color: TounColors.textPrimary,
        ),
        titleMedium: TextStyle(
          fontWeight: FontWeight.w600,
          color: TounColors.textPrimary,
        ),
        bodyMedium: TextStyle(
          color: TounColors.textPrimary,
        ),
        bodySmall: TextStyle(
          color: TounColors.textSecondary,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: TounColors.teal,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 15,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: TounColors.background,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: TounColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: TounColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: TounColors.teal, width: 1.6),
        ),
      ),
    );
  }
}
