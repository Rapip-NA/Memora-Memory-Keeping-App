import 'dart:math';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/widgets/neo_widgets.dart';

class LandingScreen extends StatefulWidget {
  final bool isDark;
  final VoidCallback onToggleTheme;

  const LandingScreen({
    super.key,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<LandingScreen> createState() => _LandingScreenState();
}

class _LandingScreenState extends State<LandingScreen>
    with TickerProviderStateMixin {
  late AnimationController _floatController;
  late Animation<double> _floatAnimation;
  late AnimationController _spinController;
  late Animation<double> _spinAnimation;
  late ScrollController _scrollController;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();

    _floatController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    )..repeat(reverse: true);

    _floatAnimation = Tween<double>(begin: 0, end: -15).animate(
      CurvedAnimation(parent: _floatController, curve: Curves.easeInOut),
    );

    _spinController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 10),
    )..repeat();

    _spinAnimation =
        Tween<double>(begin: 0, end: 2 * pi).animate(_spinController);
  }

  @override
  void dispose() {
    _floatController.dispose();
    _spinController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Color get accentColor =>
      widget.isDark ? AppColors.orange : AppColors.lime;
  Color get bgColor => widget.isDark ? AppColors.darkBg : Colors.white;
  Color get textColor => widget.isDark ? Colors.white : AppColors.dark;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: bgColor,
      body: CustomScrollView(
        controller: _scrollController,
        slivers: [
          _buildNavBar(),
          SliverToBoxAdapter(child: _buildHeroSection(context)),
          SliverToBoxAdapter(child: _buildFeaturesSection(context)),
          SliverToBoxAdapter(child: _buildAdvantagesSection(context)),
          SliverToBoxAdapter(child: _buildCtaSection(context)),
          SliverToBoxAdapter(child: _buildFooter(context)),
        ],
      ),
    );
  }

  // ──────────────────────────────────────────────
  // NAV BAR
  // ──────────────────────────────────────────────
  Widget _buildNavBar() {
    return SliverAppBar(
      pinned: true,
      backgroundColor: bgColor,
      elevation: 0,
      toolbarHeight: 72,
      automaticallyImplyLeading: false,
      bottom: PreferredSize(
        preferredSize: const Size.fromHeight(1),
        child: Container(
          height: 1,
          color: widget.isDark
              ? Colors.grey.shade800
              : Colors.grey.shade100,
        ),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          color: bgColor,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          child: Row(
            children: [
              // Logo
              Icon(Icons.inventory_2_rounded,
                  color: accentColor, size: 28),
              const SizedBox(width: 8),
              Text(
                'The Archive',
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: textColor,
                ),
              ),
              const Spacer(),
              // Nav links (only on wider screens)
              if (MediaQuery.of(context).size.width > 600) ...[
                const Spacer(),
                ..._navLinks(),
                const SizedBox(width: 16),
              ] else
                const Spacer(),
              // Theme toggle
              GestureDetector(
                onTap: widget.onToggleTheme,
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 300),
                  child: Icon(
                    widget.isDark ? Icons.wb_sunny_rounded : Icons.dark_mode_rounded,
                    key: ValueKey(widget.isDark),
                    color: textColor,
                    size: 24,
                  ),
                ),
              ),
              if (MediaQuery.of(context).size.width > 400) ...[
                const SizedBox(width: 20),
                NeoButton(
                  isDark: widget.isDark,
                  backgroundColor: bgColor,
                  textColor: textColor,
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                  child: Text(
                    'Masuk / Daftar',
                    style: GoogleFonts.spaceGrotesk(
                      fontWeight: FontWeight.w700,
                      color: textColor,
                      fontSize: 14,
                    ),
                  ),
                ),
              ] else ...[
                 const SizedBox(width: 12),
                 Icon(Icons.menu, color: textColor, size: 28),
              ]
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _navLinks() {
    final links = ['Beranda', 'Fitur Utama', 'Keunggulan'];
    return links.map((label) {
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12),
        child: Text(
          label,
          style: GoogleFonts.spaceGrotesk(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: widget.isDark ? Colors.grey.shade300 : AppColors.dark,
          ),
        ),
      );
    }).toList();
  }

  // ──────────────────────────────────────────────
  // HERO
  // ──────────────────────────────────────────────
  Widget _buildHeroSection(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    final isWide = w > 800;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 60),
      child: isWide
          ? Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Expanded(flex: 5, child: _heroText(context)),
                const SizedBox(width: 40),
                Expanded(flex: 4, child: _heroVisual()),
              ],
            )
          : Column(
              children: [
                _heroText(context),
                const SizedBox(height: 40),
                SizedBox(height: 360, child: _heroVisual()),
              ],
            ),
    );
  }

  Widget _heroText(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Badge
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: widget.isDark
                ? AppColors.orangeOpacity20
                : AppColors.limeOpacity20,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: accentColor.withValues(alpha: 0.5),
            ),
          ),
          child: Text(
            'Platform Sosial Eksklusif v2.0',
            style: GoogleFonts.spaceGrotesk(
              color: accentColor,
              fontWeight: FontWeight.w700,
              fontSize: 13,
            ),
          ),
        ),
        const SizedBox(height: 24),
        // Headline
        Text(
          'Abadikan Momen,\nRawat Kenangan\nSelamanya',
          style: GoogleFonts.spaceGrotesk(
            fontSize: 48,
            fontWeight: FontWeight.w800,
            color: textColor,
            height: 1.15,
            letterSpacing: -1,
          ),
        ),
        const SizedBox(height: 24),
        // Subtitle
        Text(
          'The Archive adalah platform sosial eksklusif untuk komunitas Anda. Bagikan cerita, simpan foto resolusi tinggi, rencanakan acara, dan tetap terhubung tanpa batas ruang dan waktu.',
          style: GoogleFonts.spaceGrotesk(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
            height: 1.6,
          ),
        ),
        const SizedBox(height: 36),
        // Buttons
        Wrap(
          spacing: 16,
          runSpacing: 14,
          children: [
            NeoButton(
              isDark: widget.isDark,
              backgroundColor:
                  widget.isDark ? Colors.white : AppColors.dark,
              textColor: widget.isDark ? AppColors.dark : Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 18),
              child: Text(
                'Buat Akun Gratis',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.w800,
                  fontSize: 16,
                  color: widget.isDark ? AppColors.dark : Colors.white,
                ),
              ),
            ),
            NeoButton(
              isDark: widget.isDark,
              backgroundColor: bgColor,
              textColor: textColor,
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 18),
              child: Text(
                'Jelajahi Fitur',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.w800,
                  fontSize: 16,
                  color: textColor,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _heroVisual() {
    return AnimatedBuilder(
      animation: _floatAnimation,
      builder: (context, child) {
        return Transform.translate(
          offset: Offset(0, _floatAnimation.value),
          child: child,
        );
      },
      child: SizedBox(
        height: 400,
        child: Stack(
          alignment: Alignment.center,
          children: [
            // Rotating dashed border
            Transform.rotate(
              angle: -0.1,
              child: Container(
                width: 260,
                height: 260,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(48),
                  border: Border.all(
                    color: accentColor,
                    width: 2,
                    strokeAlign: BorderSide.strokeAlignCenter,
                  ),
                ),
              ),
            ),
            // Solid border (rotated 12deg)
            Transform.rotate(
              angle: 0.21,
              child: Container(
                width: 224,
                height: 224,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(48),
                  border: Border.all(
                    color: textColor,
                    width: 2,
                  ),
                ),
              ),
            ),
            // Main icon
            Icon(
              Icons.widgets_rounded,
              size: 110,
              color: textColor,
            ),

            // Floating feature icons
            Positioned(
              top: 40,
              right: 60,
              child: _floatingIcon(
                icon: Icons.photo_album_rounded,
                bg: textColor,
                iconColor: bgColor,
                size: 52,
                radius: 16,
              ),
            ),
            Positioned(
              top: 20,
              right: 5,
              child: _floatingIcon(
                icon: Icons.event_rounded,
                bg: accentColor,
                iconColor: AppColors.dark,
                size: 52,
                radius: 999,
              ),
            ),
            Positioned(
              right: 5,
              top: 90,
              child: _floatingIcon(
                icon: Icons.poll_rounded,
                bg: textColor,
                iconColor: bgColor,
                size: 44,
                radius: 999,
              ),
            ),
            Positioned(
              bottom: 80,
              right: 40,
              child: Transform.rotate(
                angle: 0.21,
                child: _floatingIcon(
                  icon: Icons.chat_bubble_rounded,
                  bg: accentColor,
                  iconColor: AppColors.dark,
                  size: 56,
                  radius: 16,
                ),
              ),
            ),
            // Spinning star
            Positioned(
              bottom: 60,
              left: 40,
              child: AnimatedBuilder(
                animation: _spinAnimation,
                builder: (context, _) {
                  return Transform.rotate(
                    angle: _spinAnimation.value,
                    child: Icon(
                      Icons.star_rounded,
                      size: 36,
                      color: textColor,
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _floatingIcon({
    required IconData icon,
    required Color bg,
    required Color iconColor,
    required double size,
    required double radius,
  }) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(radius),
        boxShadow: const [
          BoxShadow(color: Colors.black26, blurRadius: 8, offset: Offset(0, 4)),
        ],
      ),
      child: Icon(icon, color: iconColor, size: size * 0.44),
    );
  }

  // ──────────────────────────────────────────────
  // FEATURES
  // ──────────────────────────────────────────────
  Widget _buildFeaturesSection(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    final isWide = w > 700;

    final featureCards = [
      _FeatureCardData(
        titleLine1: 'Galeri Cloud &',
        titleLine2: 'Penyimpanan Media',
        description:
            'Unggah foto momen berharga tanpa batasan kualitas. Terintegrasi dengan Cloudflare R2 untuk menjamin data Anda tidak akan pernah hilang.',
        icon: Icons.cloud_upload_rounded,
        isDarkCard: false,
      ),
      _FeatureCardData(
        titleLine1: 'Sosial Feed &',
        titleLine2: 'Interaksi Aktif',
        description:
            'Bagikan cerita harian, buat polling interaktif, dan berikan komentar atau like secara real-time pada postingan pengguna lain.',
        icon: Icons.newspaper_rounded,
        isDarkCard: true,
      ),
      _FeatureCardData(
        titleLine1: 'Manajemen Acara &',
        titleLine2: 'RSVP Sistem',
        description:
            'Rencanakan reuni atau pertemuan dengan mudah. Fitur kehadiran (RSVP) bawaan membantu melacak peserta acara secara otomatis.',
        icon: Icons.calendar_month_rounded,
        isDarkCard: true,
      ),
      _FeatureCardData(
        titleLine1: 'Pesan Instan &',
        titleLine2: 'Notifikasi Cerdas',
        description:
            'Berkomunikasi secara privat (Chat Panel), dan dapatkan pemberitahuan seketika ada interaksi baru di akun Anda.',
        icon: Icons.chat_rounded,
        isDarkCard: false,
      ),
    ];

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 64),
      decoration: BoxDecoration(
        border: Border(
          top: BorderSide(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade100,
            width: 1,
          ),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: accentColor,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        'EKOSISTEM APLIKASI',
                        style: GoogleFonts.spaceGrotesk(
                          color: widget.isDark ? AppColors.dark : Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                          letterSpacing: 1.2,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    RichText(
                      text: TextSpan(
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 40,
                          fontWeight: FontWeight.w800,
                          color: textColor,
                          height: 1.2,
                        ),
                        children: [
                          const TextSpan(text: 'Semua yang Anda butuhkan\ndalam '),
                          WidgetSpan(
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: textColor,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                'satu platform.',
                                style: GoogleFonts.spaceGrotesk(
                                  fontSize: 40,
                                  fontWeight: FontWeight.w800,
                                  color: bgColor,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'The Archive tidak hanya sekadar sosial media. Ini adalah arsip digital yang hidup dan interaktif.',
            style: GoogleFonts.spaceGrotesk(
              fontSize: 16,
              fontWeight: FontWeight.w500,
              color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
            ),
          ),
          const SizedBox(height: 48),

          // Cards grid
          if (isWide)
            Row(
              children: [
                Expanded(child: _featureCard(featureCards[0])),
                const SizedBox(width: 24),
                Expanded(child: _featureCard(featureCards[1])),
              ],
            )
          else
            Column(children: [
              _featureCard(featureCards[0]),
              const SizedBox(height: 24),
              _featureCard(featureCards[1]),
            ]),
          const SizedBox(height: 24),
          if (isWide)
            Row(
              children: [
                Expanded(child: _featureCard(featureCards[2])),
                const SizedBox(width: 24),
                Expanded(child: _featureCard(featureCards[3])),
              ],
            )
          else
            Column(children: [
              _featureCard(featureCards[2]),
              const SizedBox(height: 24),
              _featureCard(featureCards[3]),
            ]),
        ],
      ),
    );
  }

  Widget _featureCard(_FeatureCardData data) {
    final useDarkCard = data.isDarkCard;
    final cardBg = useDarkCard
        ? (widget.isDark ? Colors.white : AppColors.dark)
        : (widget.isDark ? AppColors.cardDark : AppColors.light);
    final tagBg = useDarkCard
        ? (widget.isDark ? AppColors.darkBg : Colors.white)
        : accentColor;
    final tagText = useDarkCard
        ? (widget.isDark ? Colors.white : AppColors.dark)
        : (widget.isDark ? AppColors.dark : Colors.white);
    final descColor = useDarkCard
        ? (widget.isDark ? Colors.grey.shade600 : Colors.grey.shade300)
        : (widget.isDark ? Colors.grey.shade300 : AppColors.dark);
    final iconColor = useDarkCard
        ? (widget.isDark ? Colors.grey.shade200 : Colors.grey.shade800)
        : (widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200);

    return _HoverCard(
      isDark: widget.isDark,
      child: Container(
        height: 320,
        padding: const EdgeInsets.all(36),
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(40),
          border: Border.all(
            color: widget.isDark ? Colors.white : AppColors.dark,
            width: 2,
          ),
        ),
        child: Stack(
          children: [
            // Content
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: tagBg,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    data.titleLine1,
                    style: GoogleFonts.spaceGrotesk(
                      color: tagText,
                      fontWeight: FontWeight.w800,
                      fontSize: 20,
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: tagBg,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    data.titleLine2,
                    style: GoogleFonts.spaceGrotesk(
                      color: tagText,
                      fontWeight: FontWeight.w800,
                      fontSize: 20,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  data.description,
                  style: GoogleFonts.spaceGrotesk(
                    color: descColor,
                    fontWeight: FontWeight.w500,
                    fontSize: 14,
                    height: 1.6,
                  ),
                  maxLines: 4,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
            // Background icon
            Positioned(
              bottom: -20,
              right: -20,
              child: Icon(
                data.icon,
                size: 150,
                color: iconColor,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ──────────────────────────────────────────────
  // ADVANTAGES
  // ──────────────────────────────────────────────
  Widget _buildAdvantagesSection(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    final isWide = w > 700;

    final advantages = [
      _AdvantageData(
        icon: Icons.shield_rounded,
        iconBg: accentColor,
        title: 'Privasi & Keamanan',
        description:
            'Kami melindungi data pengguna dengan sistem keamanan tinggi. Postingan, foto, dan obrolan grup hanya dapat diakses oleh mereka yang memiliki autentikasi akun resmi.',
      ),
      _AdvantageData(
        icon: Icons.rocket_launch_rounded,
        iconBg: Colors.white,
        title: 'Performa Super Cepat',
        description:
            'Dibangun dengan framework Laravel terbaru dan pemrosesan background job, menjamin performa ngebut tanpa waktu tunggu yang lama.',
      ),
      _AdvantageData(
        icon: Icons.brightness_4_rounded,
        iconBg: accentColor,
        title: 'Mode Tema Adaptif',
        description:
            'Sistem mendukung fungsionalitas UI yang mewah. Bebas berganti antara estetika Light Mode (Hijau Putih) atau Dark Mode (Oranye Hitam) kapan saja.',
      ),
    ];

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 64),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: accentColor,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  'Kelebihan Aplikasi',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 32,
                    fontWeight: FontWeight.w800,
                    color: widget.isDark ? AppColors.dark : Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 20),
              Flexible(
                child: Text(
                  'Mengapa komunitas memilih menggunakan sistem kami.',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                    color: widget.isDark
                        ? Colors.grey.shade400
                        : Colors.grey.shade600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 32),
          NeoBox(
            isDark: widget.isDark,
            backgroundColor:
                widget.isDark ? AppColors.cardDark : AppColors.dark,
            borderRadius: 40,
            padding: const EdgeInsets.all(48),
            child: isWide
                ? IntrinsicHeight(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Expanded(child: _advantageItem(advantages[0])),
                        VerticalDivider(
                            color: Colors.grey.shade700, width: 48),
                        Expanded(child: _advantageItem(advantages[1])),
                        VerticalDivider(
                            color: Colors.grey.shade700, width: 48),
                        Expanded(child: _advantageItem(advantages[2])),
                      ],
                    ),
                  )
                : Column(
                    children: [
                      _advantageItem(advantages[0]),
                      Divider(color: Colors.grey.shade700, height: 48),
                      _advantageItem(advantages[1]),
                      Divider(color: Colors.grey.shade700, height: 48),
                      _advantageItem(advantages[2]),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _advantageItem(_AdvantageData data) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            color: data.iconBg,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Icon(data.icon, color: AppColors.dark, size: 28),
        ),
        const SizedBox(height: 16),
        Text(
          data.title,
          style: GoogleFonts.spaceGrotesk(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 10),
        Text(
          data.description,
          style: GoogleFonts.spaceGrotesk(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.grey.shade400,
            height: 1.6,
          ),
        ),
      ],
    );
  }

  // ──────────────────────────────────────────────
  // CTA
  // ──────────────────────────────────────────────
  Widget _buildCtaSection(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      child: NeoBox(
        isDark: widget.isDark,
        backgroundColor: widget.isDark ? AppColors.cardDark : Colors.white,
        borderRadius: 40,
        padding: const EdgeInsets.all(52),
        child: Row(
          children: [
            // Text content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Siap Membangun Arsip\nKomunitas Anda?',
                    style: GoogleFonts.spaceGrotesk(
                      fontSize: 36,
                      fontWeight: FontWeight.w800,
                      color: textColor,
                      height: 1.2,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Gabung hari ini dan nikmati platform interaktif bebas iklan yang dirancang khusus untuk menyimpan setiap kenangan berharga dengan sempurna.',
                    style: GoogleFonts.spaceGrotesk(
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                      color: widget.isDark
                          ? Colors.grey.shade300
                          : Colors.grey.shade700,
                      height: 1.6,
                    ),
                  ),
                  const SizedBox(height: 32),
                  NeoButton(
                    isDark: widget.isDark,
                    backgroundColor: accentColor,
                    textColor: AppColors.dark,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 28, vertical: 18),
                    child: Text(
                      'Buat Akun Sekarang',
                      style: GoogleFonts.spaceGrotesk(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        color: AppColors.dark,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 32),
            // Dashboard mockup (only on wide)
            if (MediaQuery.of(context).size.width > 750)
              AnimatedBuilder(
                animation: _floatAnimation,
                builder: (context, child) => Transform.translate(
                  offset: Offset(0, _floatAnimation.value * 0.5),
                  child: child,
                ),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    // Window mockup
                    Container(
                      width: 260,
                      height: 200,
                      decoration: BoxDecoration(
                        color: bgColor,
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: textColor, width: 2),
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black26,
                            blurRadius: 20,
                            offset: Offset(0, 10),
                          )
                        ],
                      ),
                      child: Column(
                        children: [
                          // Title bar
                          Container(
                            height: 44,
                            decoration: BoxDecoration(
                              border: Border(
                                bottom: BorderSide(color: textColor, width: 2),
                              ),
                            ),
                            padding:
                                const EdgeInsets.symmetric(horizontal: 12),
                            child: Row(
                              children: [
                                _dot(Colors.redAccent),
                                const SizedBox(width: 6),
                                _dot(Colors.amber),
                                const SizedBox(width: 6),
                                _dot(Colors.green),
                              ],
                            ),
                          ),
                          Expanded(
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Row(
                                children: [
                                  Expanded(
                                    flex: 1,
                                    child: Container(
                                      decoration: BoxDecoration(
                                        color: widget.isDark
                                            ? Colors.grey.shade800
                                            : Colors.grey.shade100,
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    flex: 2,
                                    child: Column(
                                      children: [
                                        Expanded(
                                          child: Container(
                                            decoration: BoxDecoration(
                                              color: widget.isDark
                                                  ? Colors.grey.shade800
                                                  : Colors.grey.shade100,
                                              borderRadius:
                                                  BorderRadius.circular(12),
                                            ),
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Expanded(
                                          child: Container(
                                            decoration: BoxDecoration(
                                              color: accentColor
                                                  .withValues(alpha: 0.2),
                                              borderRadius:
                                                  BorderRadius.circular(12),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Heart badge
                    Positioned(
                      bottom: -20,
                      left: -20,
                      child: Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          color: textColor,
                          shape: BoxShape.circle,
                          border: Border.all(color: textColor, width: 2),
                          boxShadow: [
                            BoxShadow(
                              color: widget.isDark
                                  ? AppColors.orange
                                  : AppColors.dark,
                              offset: const Offset(0, 4),
                            )
                          ],
                        ),
                        child: Icon(Icons.favorite_rounded,
                            color: bgColor, size: 24),
                      ),
                    ),
                    // Star badge
                    Positioned(
                      top: -20,
                      right: -20,
                      child: Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          color: accentColor,
                          shape: BoxShape.circle,
                          border: Border.all(color: textColor, width: 2),
                          boxShadow: [
                            BoxShadow(
                              color: widget.isDark
                                  ? AppColors.orange
                                  : AppColors.dark,
                              offset: const Offset(0, 4),
                            )
                          ],
                        ),
                        child:
                            const Icon(Icons.star_rounded, color: AppColors.dark, size: 24),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _dot(Color color) => Container(
        width: 12,
        height: 12,
        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
      );

  // ──────────────────────────────────────────────
  // FOOTER
  // ──────────────────────────────────────────────
  Widget _buildFooter(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 32),
      child: Container(
        decoration: BoxDecoration(
          color:
              widget.isDark ? AppColors.cardDark : AppColors.dark,
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(40),
            topRight: Radius.circular(40),
          ),
          border: Border(
            top: BorderSide(
                color: widget.isDark ? Colors.white : AppColors.dark,
                width: 2),
            left: BorderSide(
                color: widget.isDark ? Colors.white : AppColors.dark,
                width: 2),
            right: BorderSide(
                color: widget.isDark ? Colors.white : AppColors.dark,
                width: 2),
          ),
        ),
        padding: const EdgeInsets.all(48),
        child: Column(
          children: [
            // Top row
            Wrap(
              spacing: 24,
              runSpacing: 24,
              alignment: WrapAlignment.spaceBetween,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                // Logo
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: widget.isDark ? AppColors.darkBg : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: widget.isDark ? Colors.white : AppColors.dark,
                      width: 2,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.inventory_2_rounded,
                          color: accentColor, size: 22),
                      const SizedBox(width: 8),
                      Text(
                        'The Archive',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: widget.isDark ? Colors.white : AppColors.dark,
                        ),
                      ),
                    ],
                  ),
                ),
                // Nav links
                Wrap(
                  spacing: 24,
                  children: ['Beranda', 'Fitur Aplikasi', 'Kelebihan', 'Masuk']
                      .map((l) => Text(
                            l,
                            style: GoogleFonts.spaceGrotesk(
                              color: Colors.white,
                              fontWeight: FontWeight.w500,
                              fontSize: 15,
                            ),
                          ))
                      .toList(),
                ),
                // Social icons
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _socialIcon(Icons.camera_alt_rounded),
                    const SizedBox(width: 12),
                    _socialIcon(Icons.alternate_email_rounded),
                  ],
                ),
              ],
            ),

            const SizedBox(height: 40),
            Divider(color: Colors.grey.shade800, height: 1),
            const SizedBox(height: 24),

            // Bottom row
            Wrap(
              spacing: 24,
              runSpacing: 12,
              alignment: WrapAlignment.spaceBetween,
              children: [
                Text(
                  '© ${DateTime.now().year} The Archive. All Rights Reserved. Built with Laravel.',
                  style: GoogleFonts.spaceGrotesk(
                    color: Colors.grey.shade500,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Kebijakan Privasi',
                      style: GoogleFonts.spaceGrotesk(
                          color: Colors.grey.shade500, fontSize: 13),
                    ),
                    const SizedBox(width: 20),
                    Text(
                      'Syarat & Ketentuan',
                      style: GoogleFonts.spaceGrotesk(
                          color: Colors.grey.shade500, fontSize: 13),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _socialIcon(IconData icon) {
    return Container(
      width: 44,
      height: 44,
      decoration: const BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
      ),
      child: Icon(icon, color: AppColors.dark, size: 20),
    );
  }
}

// ──────────────────────────────────────────────
// HOVER CARD WRAPPER
// ──────────────────────────────────────────────
class _HoverCard extends StatefulWidget {
  final Widget child;
  final bool isDark;
  const _HoverCard({required this.child, required this.isDark});

  @override
  State<_HoverCard> createState() => _HoverCardState();
}

class _HoverCardState extends State<_HoverCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _elevate;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 200));
    _elevate = Tween<double>(begin: 8, end: 12).animate(
        CurvedAnimation(parent: _ctrl, curve: Curves.easeOut));
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final shadowColor = widget.isDark ? AppColors.orange : AppColors.dark;

    return MouseRegion(
      onEnter: (_) => _ctrl.forward(),
      onExit: (_) => _ctrl.reverse(),
      child: AnimatedBuilder(
        animation: _ctrl,
        builder: (context, child) {
          return Transform.translate(
            offset: Offset(0, -(_elevate.value - 8)),
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(40),
                boxShadow: [
                  BoxShadow(
                    color: shadowColor,
                    offset: Offset(0, _elevate.value),
                    blurRadius: 0,
                  ),
                ],
              ),
              child: child,
            ),
          );
        },
        child: widget.child,
      ),
    );
  }
}

// ──────────────────────────────────────────────
// DATA MODELS
// ──────────────────────────────────────────────
class _FeatureCardData {
  final String titleLine1, titleLine2, description;
  final IconData icon;
  final bool isDarkCard;

  const _FeatureCardData({
    required this.titleLine1,
    required this.titleLine2,
    required this.description,
    required this.icon,
    required this.isDarkCard,
  });
}

class _AdvantageData {
  final IconData icon;
  final Color iconBg;
  final String title, description;

  const _AdvantageData({
    required this.icon,
    required this.iconBg,
    required this.title,
    required this.description,
  });
}
