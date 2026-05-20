import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/widgets/neo_widgets.dart';
import 'package:memora_app/screens/feed_screen.dart';
import 'package:memora_app/screens/register_screen.dart';

class LoginScreen extends StatefulWidget {
  final bool isDark;
  final VoidCallback onToggleTheme;

  const LoginScreen({
    super.key,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  
  bool _obscurePassword = true;
  bool _rememberMe = false;
  bool _isLoading = false;
  String? _errorMessage;

  // Base URL configuration for Laravel backend
  // 10.0.2.2 is the localhost gateway for Android Emulator
  // localhost/127.0.0.1 works for iOS Simulator or real devices on same network
  String _baseUrl = 'http://127.0.0.1:8001'; 
  final _baseUrlController = TextEditingController(text: 'http://127.0.0.1:8001');
  bool _showSettings = false;

  @override
  void initState() {
    super.initState();
    _loadSavedSettings();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _baseUrlController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedSettings() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      String saved = prefs.getString('backend_url') ?? 'http://127.0.0.1:8001';
      if (saved == 'http://10.0.2.2:8000' || saved == 'http://172.18.20.187:8001') {
        saved = 'http://127.0.0.1:8001';
        prefs.setString('backend_url', saved);
      }
      _baseUrl = saved;
      _baseUrlController.text = _baseUrl;
      _emailController.text = prefs.getString('saved_email') ?? '';
      _rememberMe = _emailController.text.isNotEmpty;
    });

    // Optional: Auto login if token exists and is valid
    final token = prefs.getString('auth_token');
    if (token != null) {
      // Direct navigation if they are already logged in
      _navigateToFeed();
    }
  }

  void _navigateToFeed() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (context) => FeedScreen(
          isDark: widget.isDark,
          onToggleTheme: widget.onToggleTheme,
        ),
      ),
    );
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final email = _emailController.text.trim();
    final password = _passwordController.text;

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/auth/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      ).timeout(const Duration(seconds: 10));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['status'] == 'success') {
        final prefs = await SharedPreferences.getInstance();
        
        // Save auth data
        final token = data['data']['token'];
        final user = data['data']['user'];
        
        await prefs.setString('auth_token', token);
        await prefs.setString('user_name', user['name'] ?? '');
        await prefs.setString('user_email', user['email'] ?? '');
        await prefs.setString('backend_url', _baseUrl);

        if (_rememberMe) {
          await prefs.setString('saved_email', email);
        } else {
          await prefs.remove('saved_email');
        }

        if (mounted) {
          _navigateToFeed();
        }
      } else {
        // Handle error responses from Laravel
        setState(() {
          _errorMessage = data['message'] ?? 'Gagal masuk. Silakan periksa kembali email & password Anda.';
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Tidak dapat terhubung ke server Laravel.\nPeriksa koneksi jaringan Anda atau sesuaikan URL server di ikon Pengaturan ⚙️.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
    final bgColor = widget.isDark ? AppColors.darkBg : AppColors.light;
    final textColor = widget.isDark ? Colors.white : AppColors.dark;
    final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          // Theme toggler
          IconButton(
            icon: Icon(
              widget.isDark ? Icons.wb_sunny_rounded : Icons.dark_mode_rounded,
              color: textColor,
            ),
            onPressed: widget.onToggleTheme,
          ),
          // Backend URL Configurer
          IconButton(
            icon: Icon(
              Icons.settings_outlined,
              color: textColor,
            ),
            onPressed: () {
              setState(() {
                _showSettings = !_showSettings;
              });
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 8.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Rotating Dots Background design (Decorative)
              if (_showSettings) ...[
                _buildSettingsPanel(cardBg, textColor),
                const SizedBox(height: 16),
              ],

              const SizedBox(height: 16),

              // Logo & App Name
              Center(
                child: Column(
                  children: [
                    // Neobrutalist Rotated Logo Box
                    Transform.rotate(
                      angle: 0.08,
                      child: Container(
                        width: 64,
                        height: 64,
                        decoration: BoxDecoration(
                          color: accentColor,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.dark, width: 2),
                          boxShadow: const [
                            BoxShadow(
                              color: AppColors.dark,
                              offset: Offset(0, 4),
                              blurRadius: 0,
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.inventory_2_rounded,
                          size: 32,
                          color: AppColors.dark,
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Selamat Datang',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Masuk untuk melanjutkan ke The Archive',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 32),

              // Red Error Neobrutalist Banner
              if (_errorMessage != null) ...[
                NeoBox(
                  isDark: widget.isDark,
                  backgroundColor: const Color(0xFFFEE2E2), // soft red
                  borderRadius: 16,
                  borderWidth: 2,
                  shadowOffset: 4,
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline_rounded, color: Colors.redAccent, size: 24),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _errorMessage!,
                          style: GoogleFonts.spaceGrotesk(
                            color: Colors.red.shade900,
                            fontWeight: FontWeight.w600,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
              ],

              // Login Form Card
              NeoBox(
                isDark: widget.isDark,
                backgroundColor: cardBg,
                borderRadius: 24,
                borderWidth: 2,
                shadowOffset: 8,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Email Label
                      Text(
                        'Email',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: textColor,
                        ),
                      ),
                      const SizedBox(height: 8),
                      // Email Input Field
                      _buildTextField(
                        controller: _emailController,
                        hintText: 'nama@email.com',
                        icon: Icons.mail_outline_rounded,
                        keyboardType: TextInputType.emailAddress,
                        isDark: widget.isDark,
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Email tidak boleh kosong';
                          }
                          if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                            return 'Format email tidak valid';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 20),

                      // Password Label
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Kata Sandi',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: textColor,
                            ),
                          ),
                          GestureDetector(
                            onTap: () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  backgroundColor: AppColors.dark,
                                  content: Text(
                                    'Silakan hubungi admin web untuk mereset kata sandi Anda.',
                                    style: GoogleFonts.spaceGrotesk(color: Colors.white),
                                  ),
                                ),
                              );
                            },
                            child: Text(
                              'Lupa sandi?',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade500,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      // Password Input Field
                      _buildTextField(
                        controller: _passwordController,
                        hintText: '••••••••',
                        icon: Icons.lock_outline_rounded,
                        obscureText: _obscurePassword,
                        isDark: widget.isDark,
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                            color: _obscurePassword ? Colors.grey : accentColor,
                          ),
                          onPressed: () {
                            setState(() {
                              _obscurePassword = !_obscurePassword;
                            });
                          },
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Kata sandi tidak boleh kosong';
                          }
                          if (value.length < 6) {
                            return 'Kata sandi minimal 6 karakter';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 16),

                      // Remember Me Checkbox
                      Row(
                        children: [
                          GestureDetector(
                            onTap: () {
                              setState(() {
                                _rememberMe = !_rememberMe;
                              });
                            },
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 100),
                              width: 24,
                              height: 24,
                              decoration: BoxDecoration(
                                color: _rememberMe ? accentColor : cardBg,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: AppColors.dark, width: 2),
                              ),
                              child: _rememberMe
                                  ? const Icon(
                                      Icons.check_rounded,
                                      size: 18,
                                      color: AppColors.dark,
                                    )
                                  : null,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Text(
                            'Ingat Saya',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: widget.isDark ? Colors.grey.shade300 : Colors.grey.shade700,
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 32),

                      // Submit Button
                      NeoButton(
                        isDark: widget.isDark,
                        backgroundColor: _isLoading ? Colors.grey.shade300 : AppColors.dark,
                        textColor: Colors.white,
                        onTap: _isLoading ? null : _handleLogin,
                        padding: const EdgeInsets.symmetric(vertical: 18),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (_isLoading) ...[
                              const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.5,
                                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                ),
                              ),
                              const SizedBox(width: 12),
                            ],
                            Text(
                              _isLoading ? 'Sedang Masuk...' : 'Masuk Sekarang',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                            if (!_isLoading) ...[
                              const SizedBox(width: 8),
                              const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 20),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 24),

              // Bottom register redirect
              Center(
                child: Wrap(
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    Text(
                      'Belum punya akun? ',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade500,
                      ),
                    ),
                    GestureDetector(
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (context) => RegisterScreen(
                              isDark: widget.isDark,
                              onToggleTheme: widget.onToggleTheme,
                            ),
                          ),
                        );
                      },
                      child: Text(
                        'Daftar di sini',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: textColor,
                          decoration: TextDecoration.underline,
                          decorationColor: accentColor,
                          decorationThickness: 2,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSettingsPanel(Color cardBg, Color textColor) {
    return NeoBox(
      isDark: widget.isDark,
      backgroundColor: cardBg,
      borderRadius: 16,
      borderWidth: 2,
      shadowOffset: 4,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            '⚙️ Pengaturan Server API',
            style: GoogleFonts.spaceGrotesk(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: textColor,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Sesuaikan URL server lokal Laravel Anda agar Flutter dapat berkomunikasi.',
            style: GoogleFonts.spaceGrotesk(
              fontSize: 11,
              color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
            ),
          ),
          const SizedBox(height: 8),
          _buildTextField(
            controller: _baseUrlController,
            hintText: 'http://127.0.0.1:8001',
            icon: Icons.link_rounded,
            isDark: widget.isDark,
          ),
          const SizedBox(height: 12),
          NeoButton(
            isDark: widget.isDark,
            backgroundColor: widget.isDark ? Colors.white : AppColors.dark,
            textColor: widget.isDark ? AppColors.dark : Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 8),
            onTap: () async {
              setState(() {
                _baseUrl = _baseUrlController.text.trim();
                _showSettings = false;
              });
              final prefs = await SharedPreferences.getInstance();
              await prefs.setString('backend_url', _baseUrl);
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    backgroundColor: AppColors.lime,
                    content: Text(
                      'Server backend berhasil disimpan ke: $_baseUrl',
                      style: GoogleFonts.spaceGrotesk(color: AppColors.dark, fontWeight: FontWeight.bold),
                    ),
                  ),
                );
              }
            },
            child: Center(
              child: Text(
                'Simpan Konfigurasi',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                  color: widget.isDark ? AppColors.dark : Colors.white,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hintText,
    required IconData icon,
    bool obscureText = false,
    TextInputType keyboardType = TextInputType.text,
    required bool isDark,
    Widget? suffixIcon,
    String? Function(String?)? validator,
  }) {
    final borderColor = isDark ? Colors.white : AppColors.dark;

    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      style: GoogleFonts.spaceGrotesk(
        color: isDark ? Colors.white : AppColors.dark,
        fontWeight: FontWeight.w600,
      ),
      validator: validator,
      decoration: InputDecoration(
        hintText: hintText,
        hintStyle: GoogleFonts.spaceGrotesk(
          color: Colors.grey.shade400,
          fontWeight: FontWeight.w500,
        ),
        prefixIcon: Icon(icon, color: Colors.grey.shade500),
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: isDark ? const Color(0xFF282932) : AppColors.light,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderColor, width: 2),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Colors.transparent, width: 2),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderColor, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Colors.redAccent, width: 2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderColor, width: 2),
        ),
        errorStyle: GoogleFonts.spaceGrotesk(
          fontWeight: FontWeight.bold,
          color: Colors.redAccent,
        ),
      ),
    );
  }
}
