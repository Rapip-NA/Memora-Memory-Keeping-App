import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:memora_app/config/app_config.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/widgets/neo_widgets.dart';

class RegisterScreen extends StatefulWidget {
  final bool isDark;
  final VoidCallback onToggleTheme;

  const RegisterScreen({
    super.key,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  bool _isLoading = false;
  String? _errorMessage;
  String _baseUrl = AppConfig.baseUrl;

  // ── Classroom state ──
  List<Map<String, dynamic>> _classrooms = [];
  bool _isLoadingClassrooms = true;
  int? _selectedClassroomId;
  String? _classroomError;

  @override
  void initState() {
    super.initState();
    _loadBaseUrl();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _loadBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    String saved = prefs.getString('backend_url') ?? AppConfig.baseUrl;
    if (!saved.startsWith('https://') && !saved.startsWith('http://memora')) {
      saved = AppConfig.baseUrl;
      await prefs.setString('backend_url', saved);
    }
    setState(() => _baseUrl = saved);
    await _fetchClassrooms();
  }

  Future<void> _fetchClassrooms() async {
    setState(() => _isLoadingClassrooms = true);
    try {
      final response = await http
          .get(
            Uri.parse('$_baseUrl/api/classrooms'),
            headers: {'Accept': 'application/json'},
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final list = data['data'] as List? ?? [];
        setState(() {
          _classrooms = list.map((e) => e as Map<String, dynamic>).toList();
          _isLoadingClassrooms = false;
        });
      } else {
        setState(() => _isLoadingClassrooms = false);
      }
    } catch (_) {
      setState(() => _isLoadingClassrooms = false);
    }
  }

  Future<void> _handleRegister() async {
    // Validate classroom selection separately (not in Form)
    if (_selectedClassroomId == null) {
      setState(() => _classroomError = 'Kelas wajib dipilih');
      return;
    } else {
      setState(() => _classroomError = null);
    }

    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/api/auth/register'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'name': _nameController.text.trim(),
              'email': _emailController.text.trim(),
              'password': _passwordController.text,
              'password_confirmation': _confirmPasswordController.text,
              'classroom_id': _selectedClassroomId,
            }),
          )
          .timeout(const Duration(seconds: 10));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 && data['status'] == 'success') {
        if (mounted) {
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (ctx) => AlertDialog(
              backgroundColor:
                  widget.isDark ? AppColors.cardDark : Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(24),
                side: const BorderSide(color: AppColors.dark, width: 2),
              ),
              title: Text(
                '🎉 Pendaftaran Berhasil',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.w800,
                  color: widget.isDark ? Colors.white : AppColors.dark,
                ),
              ),
              content: Text(
                data['message'] ??
                    'Akun Anda berhasil didaftarkan. Harap tunggu persetujuan dari admin sebelum Anda dapat masuk.',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.w500,
                  color: widget.isDark
                      ? Colors.grey.shade300
                      : Colors.grey.shade700,
                ),
              ),
              actions: [
                Padding(
                  padding: const EdgeInsets.all(8),
                  child: NeoButton(
                    isDark: widget.isDark,
                    backgroundColor:
                        widget.isDark ? AppColors.orange : AppColors.lime,
                    textColor: AppColors.dark,
                    onTap: () {
                      Navigator.of(ctx).pop();
                      Navigator.of(context).pop();
                    },
                    child: Center(
                      child: Text(
                        'Siap, Kembali ke Login',
                        style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.bold,
                          color: AppColors.dark,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          );
        }
      } else {
        String errMsg = data['message'] ?? 'Pendaftaran gagal.';
        if (data['errors'] != null && data['errors'] is Map) {
          final Map errors = data['errors'];
          final firstErrorList = errors.values.first;
          if (firstErrorList is List && firstErrorList.isNotEmpty) {
            errMsg = firstErrorList.first.toString();
          }
        }
        setState(() => _errorMessage = errMsg);
      }
    } catch (e) {
      setState(() {
        _errorMessage =
            'Tidak dapat terhubung ke server.\nPeriksa koneksi internet Anda dan coba lagi.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
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
        leading: IconButton(
          icon: Icon(Icons.arrow_back_rounded, color: textColor),
          onPressed: () => Navigator.of(context).pop(),
        ),

      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding:
              const EdgeInsets.symmetric(horizontal: 24.0, vertical: 8.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ── Logo / Header ──────────────────────────────────────
              Center(
                child: Column(
                  children: [
                    Transform.rotate(
                      angle: -0.08,
                      child: Container(
                        width: 64,
                        height: 64,
                        decoration: BoxDecoration(
                          color: accentColor,
                          borderRadius: BorderRadius.circular(16),
                          border:
                              Border.all(color: AppColors.dark, width: 2),
                          boxShadow: const [
                            BoxShadow(
                              color: AppColors.dark,
                              offset: Offset(0, 4),
                              blurRadius: 0,
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.person_add_alt_1_rounded,
                          size: 32,
                          color: AppColors.dark,
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Buat Akun',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Bergabunglah bersama komunitas The Archive',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: widget.isDark
                            ? Colors.grey.shade400
                            : Colors.grey.shade600,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 32),

              // ── Error Banner ───────────────────────────────────────
              if (_errorMessage != null) ...[
                NeoBox(
                  isDark: widget.isDark,
                  backgroundColor: const Color(0xFFFEE2E2),
                  borderRadius: 16,
                  borderWidth: 2,
                  shadowOffset: 4,
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline_rounded,
                          color: Colors.redAccent, size: 24),
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

              // ── Form Card ─────────────────────────────────────────
              NeoBox(
                isDark: widget.isDark,
                backgroundColor: cardBg,
                borderRadius: 24,
                borderWidth: 2,
                shadowOffset: 8,
                padding:
                    const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // ── Nama ──────────────────────────────────
                      _fieldLabel('Nama Lengkap', textColor),
                      const SizedBox(height: 8),
                      _buildTextField(
                        controller: _nameController,
                        hintText: 'Budi Santoso',
                        icon: Icons.person_outline_rounded,
                        isDark: widget.isDark,
                        validator: (v) =>
                            (v == null || v.isEmpty) ? 'Nama tidak boleh kosong' : null,
                      ),

                      const SizedBox(height: 20),

                      // ── Email ─────────────────────────────────
                      _fieldLabel('Email', textColor),
                      const SizedBox(height: 8),
                      _buildTextField(
                        controller: _emailController,
                        hintText: 'nama@email.com',
                        icon: Icons.mail_outline_rounded,
                        keyboardType: TextInputType.emailAddress,
                        isDark: widget.isDark,
                        validator: (v) {
                          if (v == null || v.isEmpty) {
                            return 'Email tidak boleh kosong';
                          }
                          if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$')
                              .hasMatch(v)) {
                            return 'Format email tidak valid';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 20),

                      // ── Kata Sandi ────────────────────────────
                      _fieldLabel('Kata Sandi', textColor),
                      const SizedBox(height: 8),
                      _buildTextField(
                        controller: _passwordController,
                        hintText: 'Minimal 8 karakter',
                        icon: Icons.lock_outline_rounded,
                        obscureText: _obscurePassword,
                        isDark: widget.isDark,
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscurePassword
                                ? Icons.visibility_off_outlined
                                : Icons.visibility_outlined,
                            color: _obscurePassword
                                ? Colors.grey
                                : accentColor,
                          ),
                          onPressed: () => setState(
                              () => _obscurePassword = !_obscurePassword),
                        ),
                        validator: (v) {
                          if (v == null || v.isEmpty) {
                            return 'Kata sandi tidak boleh kosong';
                          }
                          if (v.length < 8) {
                            return 'Kata sandi minimal 8 karakter';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 20),

                      // ── Konfirmasi Sandi ──────────────────────
                      _fieldLabel('Konfirmasi Sandi', textColor),
                      const SizedBox(height: 8),
                      _buildTextField(
                        controller: _confirmPasswordController,
                        hintText: 'Ulangi kata sandi',
                        icon: Icons.gpp_good_outlined,
                        obscureText: _obscureConfirmPassword,
                        isDark: widget.isDark,
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureConfirmPassword
                                ? Icons.visibility_off_outlined
                                : Icons.visibility_outlined,
                            color: _obscureConfirmPassword
                                ? Colors.grey
                                : accentColor,
                          ),
                          onPressed: () => setState(() =>
                              _obscureConfirmPassword =
                                  !_obscureConfirmPassword),
                        ),
                        validator: (v) {
                          if (v == null || v.isEmpty) {
                            return 'Konfirmasi kata sandi tidak boleh kosong';
                          }
                          if (v != _passwordController.text) {
                            return 'Konfirmasi kata sandi tidak cocok';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 24),

                      // ── Pilih Kelas ───────────────────────────
                      Row(
                        children: [
                          Text(
                            'Pilih Kelas',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: textColor,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.redAccent.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              'Wajib',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: Colors.redAccent,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Pilih kelas atau angkatan Anda',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: widget.isDark
                              ? Colors.grey.shade500
                              : Colors.grey.shade500,
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Classroom picker area
                      _buildClassroomPicker(accentColor, textColor, cardBg),

                      // Error text for classroom
                      if (_classroomError != null) ...[
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(Icons.error_outline,
                                color: Colors.redAccent, size: 14),
                            const SizedBox(width: 4),
                            Text(
                              _classroomError!,
                              style: GoogleFonts.spaceGrotesk(
                                color: Colors.redAccent,
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ],

                      const SizedBox(height: 32),

                      // ── Daftar Button ─────────────────────────
                      NeoButton(
                        isDark: widget.isDark,
                        backgroundColor: _isLoading
                            ? Colors.grey.shade300
                            : AppColors.dark,
                        textColor: Colors.white,
                        onTap: _isLoading ? null : _handleRegister,
                        padding:
                            const EdgeInsets.symmetric(vertical: 18),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (_isLoading) ...[
                              const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.5,
                                  valueColor:
                                      AlwaysStoppedAnimation<Color>(
                                          Colors.white),
                                ),
                              ),
                              const SizedBox(width: 12),
                            ],
                            Text(
                              _isLoading
                                  ? 'Sedang Mendaftar...'
                                  : 'Daftar Sekarang',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                            if (!_isLoading) ...[
                              const SizedBox(width: 8),
                              const Icon(Icons.rocket_launch_rounded,
                                  color: Colors.white, size: 20),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 24),

              // ── Login redirect ─────────────────────────────────────
              Center(
                child: Wrap(
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    Text(
                      'Sudah punya akun? ',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: widget.isDark
                            ? Colors.grey.shade400
                            : Colors.grey.shade500,
                      ),
                    ),
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: Text(
                        'Masuk di sini',
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

  // ── Classroom Picker ──────────────────────────────────────────────────────
  Widget _buildClassroomPicker(
      Color accentColor, Color textColor, Color cardBg) {
    if (_isLoadingClassrooms) {
      return Container(
        height: 60,
        decoration: BoxDecoration(
          color: widget.isDark ? const Color(0xFF282932) : AppColors.light,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300,
          ),
        ),
        child: Center(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: accentColor,
                ),
              ),
              const SizedBox(width: 10),
              Text(
                'Memuat daftar kelas...',
                style: GoogleFonts.spaceGrotesk(
                  color: widget.isDark
                      ? Colors.grey.shade500
                      : Colors.grey.shade500,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (_classrooms.isEmpty) {
      return GestureDetector(
        onTap: _fetchClassrooms,
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: widget.isDark ? const Color(0xFF282932) : AppColors.light,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.redAccent.withValues(alpha: 0.4)),
          ),
          child: Row(
            children: [
              const Icon(Icons.warning_amber_rounded,
                  color: Colors.orange, size: 18),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Gagal memuat kelas. Tap untuk coba lagi.',
                  style: GoogleFonts.spaceGrotesk(
                    color: Colors.orange,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
              ),
              const Icon(Icons.refresh_rounded, color: Colors.orange, size: 18),
            ],
          ),
        ),
      );
    }

    // Show classrooms as a scrollable pill list
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: _classrooms.map((cls) {
        final id = (cls['id'] as num?)?.toInt();
        final name = cls['name'] as String? ?? 'Kelas';
        final isSelected = _selectedClassroomId != null && _selectedClassroomId == id;

        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () {
            setState(() {
              _selectedClassroomId = id;
              _classroomError = null;
            });
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color: isSelected
                  ? accentColor
                  : (widget.isDark
                      ? const Color(0xFF282932)
                      : Colors.grey.shade100),
              borderRadius: BorderRadius.circular(99),
              border: Border.all(
                color: isSelected
                    ? accentColor
                    : (widget.isDark
                        ? Colors.grey.shade700
                        : Colors.grey.shade300),
                width: isSelected ? 2 : 1.5,
              ),
              boxShadow: isSelected
                  ? [
                      BoxShadow(
                        color: accentColor.withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      )
                    ]
                  : [],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (isSelected) ...[
                  const Icon(Icons.check_circle_rounded,
                      color: AppColors.dark, size: 15),
                  const SizedBox(width: 6),
                ] else ...[
                  Icon(Icons.school_rounded,
                      color: widget.isDark
                          ? Colors.grey.shade500
                          : Colors.grey.shade500,
                      size: 15),
                  const SizedBox(width: 6),
                ],
                Text(
                  name,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: isSelected
                        ? AppColors.dark
                        : (widget.isDark
                            ? Colors.grey.shade300
                            : Colors.grey.shade700),
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  // ── Helper Widgets ─────────────────────────────────────────────────────────
  Widget _fieldLabel(String label, Color textColor) => Text(
        label,
        style: GoogleFonts.spaceGrotesk(
          fontSize: 14,
          fontWeight: FontWeight.w800,
          color: textColor,
        ),
      );

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
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
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
