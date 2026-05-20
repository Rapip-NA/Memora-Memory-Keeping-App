import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/screens/login_screen.dart';


void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final prefs = await SharedPreferences.getInstance();
  final savedTheme = prefs.getString('theme');

  // Default: follow system, but respect saved preference
  final isDark = savedTheme == null
      ? WidgetsBinding
              .instance.platformDispatcher.platformBrightness ==
          Brightness.dark
      : savedTheme == 'dark';

  runApp(MemoraApp(initialDark: isDark));
}

class MemoraApp extends StatefulWidget {
  final bool initialDark;
  const MemoraApp({super.key, required this.initialDark});

  @override
  State<MemoraApp> createState() => _MemoraAppState();
}

class _MemoraAppState extends State<MemoraApp> {
  late bool _isDark;

  @override
  void initState() {
    super.initState();
    _isDark = widget.initialDark;
  }

  Future<void> _toggleTheme() async {
    setState(() => _isDark = !_isDark);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('theme', _isDark ? 'dark' : 'light');
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'The Archive – Memora',
      debugShowCheckedModeBanner: false,
      themeMode: _isDark ? ThemeMode.dark : ThemeMode.light,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      home: LoginScreen(
        isDark: _isDark,
        onToggleTheme: _toggleTheme,
      ),
    );
  }
}

