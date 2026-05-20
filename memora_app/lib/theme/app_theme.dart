import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const lime = Color(0xFF84CC16);
  static const orange = Color(0xFFF97316);
  static const dark = Color(0xFF191A23);
  static const light = Color(0xFFF3F3F3);
  static const darkBg = Color(0xFF12131A);
  static const cardDark = Color(0xFF1D1E26);

  static const limeOpacity20 = Color(0x3384CC16);
  static const orangeOpacity20 = Color(0x33F97316);
}

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      brightness: Brightness.light,
      primaryColor: AppColors.lime,
      scaffoldBackgroundColor: Colors.white,
      fontFamily: GoogleFonts.spaceGrotesk().fontFamily,
      textTheme: GoogleFonts.spaceGroteskTextTheme(ThemeData.light().textTheme),
      colorScheme: const ColorScheme.light(
        primary: AppColors.lime,
        secondary: AppColors.dark,
        surface: Colors.white,
        error: Colors.redAccent,
      ),
    );
  }

  static ThemeData get darkTheme {
    return ThemeData(
      brightness: Brightness.dark,
      primaryColor: AppColors.orange,
      scaffoldBackgroundColor: AppColors.darkBg,
      fontFamily: GoogleFonts.spaceGrotesk().fontFamily,
      textTheme: GoogleFonts.spaceGroteskTextTheme(ThemeData.dark().textTheme),
      colorScheme: const ColorScheme.dark(
        primary: AppColors.orange,
        secondary: Colors.white,
        surface: AppColors.cardDark,
        error: Colors.redAccent,
      ),
    );
  }
}
