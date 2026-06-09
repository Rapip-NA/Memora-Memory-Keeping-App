import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:memora_app/theme/app_theme.dart';

// ──────────────────────────────────────────────────────
// ADMIN DASHBOARD SCREEN
// ──────────────────────────────────────────────────────
class AdminScreen extends StatefulWidget {
  final bool isDark;
  final String authToken;
  final String baseUrl;

  const AdminScreen({
    super.key,
    required this.isDark,
    required this.authToken,
    required this.baseUrl,
  });

  @override
  State<AdminScreen> createState() => _AdminScreenState();
}

class _AdminScreenState extends State<AdminScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // ── Stats ──
  Map<String, dynamic>? _stats;
  bool _isLoadingStats = true;

  // ── Pending Users ──
  List<dynamic> _pendingUsers = [];
  bool _isLoadingPending = true;
  final Set<int> _processingIds = {};

  // ── All Members ──
  List<dynamic> _allMembers = [];
  bool _isLoadingMembers = false;
  bool _membersLoaded = false;

  // ── Broadcast ──
  final TextEditingController _broadcastTitleCtrl = TextEditingController();
  final TextEditingController _broadcastMsgCtrl = TextEditingController();
  bool _isBroadcasting = false;

  // ── Event Create ──
  final TextEditingController _eventTitleCtrl = TextEditingController();
  final TextEditingController _eventDescCtrl = TextEditingController();
  final TextEditingController _eventLocationCtrl = TextEditingController();
  DateTime? _eventDate;
  bool _isCreatingEvent = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(() {
      if (_tabController.index == 1 && !_membersLoaded) {
        // nothing — pending loads on init
      } else if (_tabController.index == 2 && !_membersLoaded) {
        _fetchAllMembers();
      }
    });
    _fetchStats();
    _fetchPendingUsers();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _broadcastTitleCtrl.dispose();
    _broadcastMsgCtrl.dispose();
    _eventTitleCtrl.dispose();
    _eventDescCtrl.dispose();
    _eventLocationCtrl.dispose();
    super.dispose();
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ${widget.authToken}',
      };

  // ── API Calls ──────────────────────────────────────

  Future<void> _fetchStats() async {
    setState(() => _isLoadingStats = true);
    try {
      final res = await http
          .get(Uri.parse('${widget.baseUrl}/api/admin/stats'),
              headers: _headers)
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _stats = body['data'] as Map<String, dynamic>?;
          _isLoadingStats = false;
        });
      } else {
        setState(() => _isLoadingStats = false);
      }
    } catch (_) {
      setState(() => _isLoadingStats = false);
    }
  }

  Future<void> _fetchPendingUsers() async {
    setState(() => _isLoadingPending = true);
    try {
      final res = await http
          .get(Uri.parse('${widget.baseUrl}/api/admin/pending'),
              headers: _headers)
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _pendingUsers = body['data'] as List? ?? [];
          _isLoadingPending = false;
        });
      } else {
        setState(() => _isLoadingPending = false);
      }
    } catch (_) {
      setState(() => _isLoadingPending = false);
    }
  }

  Future<void> _fetchAllMembers() async {
    setState(() => _isLoadingMembers = true);
    try {
      final res = await http
          .get(Uri.parse('${widget.baseUrl}/api/users'), headers: _headers)
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        setState(() {
          _allMembers = body['data'] as List? ?? [];
          _isLoadingMembers = false;
          _membersLoaded = true;
        });
      } else {
        setState(() => _isLoadingMembers = false);
      }
    } catch (_) {
      setState(() => _isLoadingMembers = false);
    }
  }

  Future<void> _approveUser(int userId) async {
    setState(() => _processingIds.add(userId));
    try {
      final res = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/admin/users/$userId/approve'),
            headers: _headers,
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        HapticFeedback.mediumImpact();
        setState(() {
          _pendingUsers.removeWhere((u) => u['id'] == userId);
          if (_stats != null) {
            _stats!['pending_members'] =
                (_stats!['pending_members'] as int? ?? 1) - 1;
            _stats!['total_members'] =
                (_stats!['total_members'] as int? ?? 0) + 1;
          }
        });
        _showSnack('✅ Anggota berhasil disetujui', isSuccess: true);
      } else {
        _showSnack('Gagal menyetujui anggota');
      }
    } catch (_) {
      _showSnack('Koneksi bermasalah');
    } finally {
      setState(() => _processingIds.remove(userId));
    }
  }

  Future<void> _rejectUser(int userId) async {
    setState(() => _processingIds.add(userId));
    try {
      final res = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/admin/users/$userId/reject'),
            headers: _headers,
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        HapticFeedback.mediumImpact();
        setState(() {
          _pendingUsers.removeWhere((u) => u['id'] == userId);
          if (_stats != null) {
            _stats!['pending_members'] =
                (_stats!['pending_members'] as int? ?? 1) - 1;
          }
        });
        _showSnack('❌ Pendaftaran ditolak', isSuccess: false, isWarning: true);
      } else {
        _showSnack('Gagal menolak pendaftaran');
      }
    } catch (_) {
      _showSnack('Koneksi bermasalah');
    } finally {
      setState(() => _processingIds.remove(userId));
    }
  }

  Future<void> _deleteUser(int userId, String name) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor:
            widget.isDark ? AppColors.cardDark : Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: widget.isDark
                ? Colors.grey.shade800
                : Colors.grey.shade200,
          ),
        ),
        title: Text(
          'Hapus Akun',
          style: GoogleFonts.spaceGrotesk(
            fontWeight: FontWeight.w700,
            color: widget.isDark ? Colors.white : AppColors.dark,
          ),
        ),
        content: Text(
          'Yakin ingin menghapus akun "$name"? Tindakan ini tidak dapat dibatalkan.',
          style: GoogleFonts.spaceGrotesk(
            color: widget.isDark
                ? Colors.grey.shade300
                : Colors.grey.shade700,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('Batal',
                style: GoogleFonts.spaceGrotesk(
                    color: widget.isDark
                        ? Colors.grey.shade400
                        : Colors.grey.shade600)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade700,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Hapus',
                style:
                    GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      final res = await http
          .delete(
            Uri.parse('${widget.baseUrl}/api/admin/users/$userId'),
            headers: _headers,
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        HapticFeedback.mediumImpact();
        setState(() {
          _allMembers.removeWhere((u) => u['id'] == userId);
        });
        _showSnack('🗑️ Akun berhasil dihapus', isSuccess: true);
      } else {
        _showSnack('Gagal menghapus akun');
      }
    } catch (_) {
      _showSnack('Koneksi bermasalah');
    }
  }

  Future<void> _sendBroadcast() async {
    final msg = _broadcastMsgCtrl.text.trim();
    if (msg.isEmpty) {
      _showSnack('Pesan tidak boleh kosong');
      return;
    }
    setState(() => _isBroadcasting = true);
    try {
      final res = await http
          .post(
            Uri.parse(
                '${widget.baseUrl}/api/admin/notifications/broadcast'),
            headers: _headers,
            body: jsonEncode({
              'title': _broadcastTitleCtrl.text.trim().isEmpty
                  ? 'Pengumuman'
                  : _broadcastTitleCtrl.text.trim(),
              'message': msg,
            }),
          )
          .timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final count = body['data']?['sent_to'] ?? 0;
        HapticFeedback.mediumImpact();
        _broadcastTitleCtrl.clear();
        _broadcastMsgCtrl.clear();
        _showSnack('📢 Notifikasi dikirim ke $count anggota',
            isSuccess: true);
      } else {
        _showSnack('Gagal mengirim broadcast');
      }
    } catch (_) {
      _showSnack('Koneksi bermasalah');
    } finally {
      setState(() => _isBroadcasting = false);
    }
  }

  Future<void> _createEvent() async {
    final title = _eventTitleCtrl.text.trim();
    if (title.isEmpty || _eventDate == null) {
      _showSnack('Judul dan tanggal event wajib diisi');
      return;
    }
    setState(() => _isCreatingEvent = true);
    try {
      final res = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/admin/events'),
            headers: _headers,
            body: jsonEncode({
              'title': title,
              'description': _eventDescCtrl.text.trim(),
              'event_date':
                  '${_eventDate!.year.toString().padLeft(4, '0')}-${_eventDate!.month.toString().padLeft(2, '0')}-${_eventDate!.day.toString().padLeft(2, '0')} 00:00:00',
              'location': _eventLocationCtrl.text.trim().isEmpty
                  ? 'TBA'
                  : _eventLocationCtrl.text.trim(),
            }),
          )
          .timeout(const Duration(seconds: 15));
      if (res.statusCode == 201) {
        HapticFeedback.mediumImpact();
        _eventTitleCtrl.clear();
        _eventDescCtrl.clear();
        _eventLocationCtrl.clear();
        setState(() => _eventDate = null);
        _showSnack('🎉 Event berhasil dibuat', isSuccess: true);
      } else {
        _showSnack('Gagal membuat event');
      }
    } catch (_) {
      _showSnack('Koneksi bermasalah');
    } finally {
      setState(() => _isCreatingEvent = false);
    }
  }

  void _showSnack(String msg,
      {bool isSuccess = false, bool isWarning = false}) {
    if (!mounted) return;
    final accent = widget.isDark ? AppColors.orange : AppColors.lime;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg,
            style: GoogleFonts.spaceGrotesk(
              color: AppColors.dark,
              fontWeight: FontWeight.w600,
            )),
        backgroundColor: isSuccess
            ? accent
            : isWarning
                ? Colors.orange.shade400
                : Colors.red.shade400,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: AppColors.dark, width: 1.5),
        ),
      ),
    );
  }

  // ──────────────────────────────────────────────────────
  // BUILD
  // ──────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final accent = widget.isDark ? AppColors.orange : AppColors.lime;
    final bg = widget.isDark ? AppColors.darkBg : AppColors.light;
    final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
    final textColor = widget.isDark ? Colors.white : AppColors.dark;
    final mutedColor = Colors.grey.shade500;

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: widget.isDark ? AppColors.darkBg : Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded,
              color: textColor, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Row(
          children: [
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: accent,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'ADMIN',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.w900,
                  fontSize: 11,
                  color: AppColors.dark,
                  letterSpacing: 1.2,
                ),
              ),
            ),
            const SizedBox(width: 10),
            Text(
              'Dashboard',
              style: GoogleFonts.spaceGrotesk(
                fontWeight: FontWeight.w700,
                fontSize: 18,
                color: textColor,
              ),
            ),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: accent,
          indicatorWeight: 3,
          labelColor: accent,
          unselectedLabelColor: mutedColor,
          labelStyle:
              GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w700, fontSize: 11),
          unselectedLabelStyle:
              GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w500, fontSize: 11),
          tabs: [
            const Tab(icon: Icon(Icons.bar_chart_rounded, size: 20), text: 'Statistik'),
            Tab(
              icon: Stack(
                clipBehavior: Clip.none,
                children: [
                  const Icon(Icons.hourglass_top_rounded, size: 20),
                  if ((_stats?['pending_members'] as int? ?? 0) > 0)
                    Positioned(
                      top: -4,
                      right: -6,
                      child: Container(
                        padding: const EdgeInsets.all(3),
                        decoration: const BoxDecoration(
                          color: Colors.red,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '${_stats?['pending_members'] ?? ''}',
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 8,
                              fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                ],
              ),
              text: 'Pending',
            ),
            const Tab(icon: Icon(Icons.people_rounded, size: 20), text: 'Anggota'),
            const Tab(icon: Icon(Icons.campaign_rounded, size: 20), text: 'Broadcast'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildStatsTab(accent, cardBg, textColor, mutedColor),
          _buildPendingTab(accent, cardBg, textColor, mutedColor),
          _buildMembersTab(accent, cardBg, textColor, mutedColor),
          _buildBroadcastTab(accent, cardBg, textColor, mutedColor),
        ],
      ),
    );
  }

  // ── TAB 1: STATISTIK ─────────────────────────────────
  Widget _buildStatsTab(
      Color accent, Color cardBg, Color textColor, Color mutedColor) {
    if (_isLoadingStats) {
      return Center(
          child: CircularProgressIndicator(color: accent, strokeWidth: 2));
    }
    if (_stats == null) {
      return _buildRetryWidget('Gagal memuat statistik', _fetchStats, accent, textColor);
    }

    final total = _stats!['total_members'] as int? ?? 0;
    final pending = _stats!['pending_members'] as int? ?? 0;
    final posts = _stats!['total_posts'] as int? ?? 0;
    final gallery = _stats!['total_gallery'] as int? ?? 0;
    final events = _stats!['total_events'] as int? ?? 0;
    final completion = _stats!['profile_completion'] as Map? ?? {};
    final completePct = (completion['percentage'] as num? ?? 0).toDouble();
    final cityDist = _stats!['city_distribution'] as List? ?? [];
    final jobDist = _stats!['job_distribution'] as List? ?? [];

    return RefreshIndicator(
      color: accent,
      onRefresh: _fetchStats,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Summary cards
            GridView.count(
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.5,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                _statCard('Anggota Aktif', '$total', Icons.people_rounded,
                    accent, cardBg, textColor),
                _statCard('Pending', '$pending', Icons.hourglass_top_rounded,
                    Colors.orange, cardBg, textColor),
                _statCard('Total Post', '$posts',
                    Icons.article_rounded, Colors.blue.shade400, cardBg, textColor),
                _statCard('Gallery', '$gallery',
                    Icons.photo_library_rounded, Colors.purple.shade400, cardBg, textColor),
                _statCard('Events', '$events',
                    Icons.event_rounded, Colors.green.shade500, cardBg, textColor),
                _statCard('Profil Lengkap', '${completePct.toStringAsFixed(0)}%',
                    Icons.verified_rounded, Colors.teal.shade400, cardBg, textColor),
              ],
            ),
            const SizedBox(height: 20),

            // Profile completion bar
            _sectionHeader('Kelengkapan Profil', textColor),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: _cardDeco(cardBg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Lengkap: ${completion['complete'] ?? 0}',
                          style: GoogleFonts.spaceGrotesk(
                              color: textColor,
                              fontWeight: FontWeight.w600,
                              fontSize: 13)),
                      Text('Belum: ${completion['incomplete'] ?? 0}',
                          style: GoogleFonts.spaceGrotesk(
                              color: mutedColor, fontSize: 13)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(99),
                    child: LinearProgressIndicator(
                      value: completePct / 100,
                      minHeight: 10,
                      backgroundColor: widget.isDark
                          ? Colors.grey.shade800
                          : Colors.grey.shade200,
                      valueColor: AlwaysStoppedAnimation<Color>(accent),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text('${completePct.toStringAsFixed(1)}% anggota memiliki profil lengkap',
                      style: GoogleFonts.spaceGrotesk(
                          color: mutedColor, fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // City distribution
            if (cityDist.isNotEmpty) ...[
              _sectionHeader('Top Kota', textColor),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDeco(cardBg),
                child: Column(
                  children: cityDist.take(6).map((item) {
                    final city = item['city'] as String? ?? '-';
                    final count = item['count'] as int? ?? 0;
                    final maxCount =
                        (cityDist.first['count'] as int? ?? 1).toDouble();
                    return _distributionRow(
                        city, count, maxCount, accent, textColor, mutedColor);
                  }).toList(),
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Job distribution
            if (jobDist.isNotEmpty) ...[
              _sectionHeader('Top Pekerjaan', textColor),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDeco(cardBg),
                child: Column(
                  children: jobDist.take(6).map((item) {
                    final job = item['job'] as String? ?? '-';
                    final count = item['count'] as int? ?? 0;
                    final maxCount =
                        (jobDist.first['count'] as int? ?? 1).toDouble();
                    return _distributionRow(
                        job, count, maxCount, accent, textColor, mutedColor);
                  }).toList(),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ],
        ),
      ),
    );
  }

  Widget _statCard(String label, String value, IconData icon, Color color,
      Color cardBg, Color textColor) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: _cardDeco(cardBg),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: color, size: 22),
              Text(
                value,
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: textColor,
                ),
              ),
            ],
          ),
          Text(
            label,
            style: GoogleFonts.spaceGrotesk(
              fontSize: 12,
              color: Colors.grey.shade500,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _distributionRow(String label, int count, double maxCount,
      Color accent, Color textColor, Color mutedColor) {
    final ratio = maxCount > 0 ? count / maxCount : 0.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  label,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: textColor,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                '$count orang',
                style: GoogleFonts.spaceGrotesk(
                    fontSize: 12, color: mutedColor),
              ),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: LinearProgressIndicator(
              value: ratio,
              minHeight: 6,
              backgroundColor: widget.isDark
                  ? Colors.grey.shade800
                  : Colors.grey.shade200,
              valueColor: AlwaysStoppedAnimation<Color>(accent),
            ),
          ),
        ],
      ),
    );
  }

  // ── TAB 2: PENDING ───────────────────────────────────
  Widget _buildPendingTab(
      Color accent, Color cardBg, Color textColor, Color mutedColor) {
    if (_isLoadingPending) {
      return Center(
          child: CircularProgressIndicator(color: accent, strokeWidth: 2));
    }
    if (_pendingUsers.isEmpty) {
      return _buildEmptyState(
          '🎉 Tidak ada pendaftaran baru',
          'Semua anggota sudah diproses.',
          accent,
          textColor,
          mutedColor);
    }

    return RefreshIndicator(
      color: accent,
      onRefresh: _fetchPendingUsers,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _pendingUsers.length,
        itemBuilder: (context, i) {
          final user = _pendingUsers[i];
          final uid = user['id'] as int;
          final isProcessing = _processingIds.contains(uid);
          return _pendingCard(user, uid, isProcessing, accent, cardBg,
              textColor, mutedColor);
        },
      ),
    );
  }

  Widget _pendingCard(Map user, int uid, bool isProcessing, Color accent,
      Color cardBg, Color textColor, Color mutedColor) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: _cardDeco(cardBg),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Avatar
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                  border: Border.all(color: accent, width: 2),
                ),
                child: Center(
                  child: Text(
                    (user['name'] as String? ?? 'U')
                        .trim()
                        .split(' ')
                        .take(2)
                        .map((w) => w.isNotEmpty ? w[0].toUpperCase() : '')
                        .join(),
                    style: GoogleFonts.spaceGrotesk(
                      fontWeight: FontWeight.w800,
                      color: accent,
                      fontSize: 16,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user['name'] ?? '-',
                      style: GoogleFonts.spaceGrotesk(
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                        color: textColor,
                      ),
                    ),
                    Text(
                      user['email'] ?? '',
                      style: GoogleFonts.spaceGrotesk(
                          fontSize: 12, color: mutedColor),
                    ),
                  ],
                ),
              ),
              // Date
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.orange.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Pending',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Colors.orange.shade400,
                  ),
                ),
              ),
            ],
          ),
          if ((user['city'] ?? '').isNotEmpty ||
              (user['job'] ?? '').isNotEmpty) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                if ((user['city'] ?? '').isNotEmpty)
                  _infoBadge(Icons.location_on_rounded, user['city'],
                      mutedColor),
                if ((user['job'] ?? '').isNotEmpty)
                  _infoBadge(
                      Icons.work_rounded, user['job'], mutedColor),
              ],
            ),
          ],
          const SizedBox(height: 6),
          Text(
            '📅 Daftar: ${user['created_at'] ?? ''}',
            style:
                GoogleFonts.spaceGrotesk(fontSize: 11, color: mutedColor),
          ),
          const SizedBox(height: 14),
          if (isProcessing)
            Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child:
                    CircularProgressIndicator(color: accent, strokeWidth: 2),
              ),
            )
          else
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    icon:
                        const Icon(Icons.close_rounded, size: 18),
                    label: Text('Tolak',
                        style: GoogleFonts.spaceGrotesk(
                            fontWeight: FontWeight.w700, fontSize: 13)),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red.shade400,
                      side: BorderSide(color: Colors.red.shade400),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => _rejectUser(uid),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.check_rounded, size: 18),
                    label: Text('Setujui',
                        style: GoogleFonts.spaceGrotesk(
                            fontWeight: FontWeight.w700, fontSize: 13)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: accent,
                      foregroundColor: AppColors.dark,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () => _approveUser(uid),
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }

  Widget _infoBadge(IconData icon, String? label, Color mutedColor) {
    if (label == null || label.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: mutedColor),
          const SizedBox(width: 3),
          Text(label,
              style: GoogleFonts.spaceGrotesk(
                  fontSize: 12, color: mutedColor)),
        ],
      ),
    );
  }

  // ── TAB 3: ANGGOTA ───────────────────────────────────
  Widget _buildMembersTab(
      Color accent, Color cardBg, Color textColor, Color mutedColor) {
    if (_isLoadingMembers) {
      return Center(
          child: CircularProgressIndicator(color: accent, strokeWidth: 2));
    }
    if (!_membersLoaded) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.people_outline_rounded,
                size: 56, color: mutedColor),
            const SizedBox(height: 12),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: accent,
                foregroundColor: AppColors.dark,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: _fetchAllMembers,
              child: Text('Muat Daftar Anggota',
                  style: GoogleFonts.spaceGrotesk(
                      fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      );
    }
    if (_allMembers.isEmpty) {
      return _buildEmptyState(
          'Belum ada anggota', '', accent, textColor, mutedColor);
    }

    return RefreshIndicator(
      color: accent,
      onRefresh: _fetchAllMembers,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _allMembers.length,
        itemBuilder: (ctx, i) {
          final user = _allMembers[i];
          final uid = user['id'] as int? ?? 0;
          final name = user['name'] as String? ?? 'User';
          final email = user['email'] as String? ?? '';
          final city = user['city'] as String? ?? '';
          final job = user['job'] as String? ?? '';
          final initials = name
              .trim()
              .split(' ')
              .take(2)
              .map((w) => w.isNotEmpty ? w[0].toUpperCase() : '')
              .join();

          return Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: _cardDeco(cardBg),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: Text(initials,
                        style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.w800,
                          color: accent,
                          fontSize: 15,
                        )),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name,
                          style: GoogleFonts.spaceGrotesk(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              color: textColor)),
                      if (email.isNotEmpty)
                        Text(email,
                            style: GoogleFonts.spaceGrotesk(
                                fontSize: 11, color: mutedColor)),
                      if (city.isNotEmpty || job.isNotEmpty)
                        Text(
                          [if (job.isNotEmpty) job, if (city.isNotEmpty) city]
                              .join(' · '),
                          style: GoogleFonts.spaceGrotesk(
                              fontSize: 11,
                              color: mutedColor,
                              fontWeight: FontWeight.w500),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.delete_outline_rounded,
                      color: Colors.red.shade400, size: 22),
                  tooltip: 'Hapus Akun',
                  onPressed: () => _deleteUser(uid, name),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  // ── TAB 4: BROADCAST ─────────────────────────────────
  Widget _buildBroadcastTab(
      Color accent, Color cardBg, Color textColor, Color mutedColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Broadcast notification section
          _sectionHeader('📢 Broadcast Notifikasi', textColor),
          const SizedBox(height: 4),
          Text(
            'Kirim pengumuman ke semua anggota aktif sekaligus.',
            style: GoogleFonts.spaceGrotesk(
                color: mutedColor, fontSize: 13),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: _cardDeco(cardBg),
            child: Column(
              children: [
                _buildTextField(
                  controller: _broadcastTitleCtrl,
                  label: 'Judul (opsional)',
                  hint: 'cth: Pengumuman Penting',
                  icon: Icons.title_rounded,
                  accent: accent,
                  cardBg: cardBg,
                  textColor: textColor,
                  mutedColor: mutedColor,
                ),
                const SizedBox(height: 14),
                _buildTextField(
                  controller: _broadcastMsgCtrl,
                  label: 'Pesan',
                  hint: 'Tulis pesan pengumuman di sini...',
                  icon: Icons.message_rounded,
                  maxLines: 5,
                  accent: accent,
                  cardBg: cardBg,
                  textColor: textColor,
                  mutedColor: mutedColor,
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: _isBroadcasting
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: AppColors.dark))
                        : const Icon(Icons.send_rounded, size: 18),
                    label: Text(
                      _isBroadcasting ? 'Mengirim...' : 'Kirim Broadcast',
                      style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.w700, fontSize: 14),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: accent,
                      foregroundColor: AppColors.dark,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _isBroadcasting ? null : _sendBroadcast,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 28),

          // Create Event section
          _sectionHeader('🎉 Buat Event Baru', textColor),
          const SizedBox(height: 4),
          Text(
            'Event yang dibuat akan muncul di tab Events untuk semua anggota.',
            style: GoogleFonts.spaceGrotesk(
                color: mutedColor, fontSize: 13),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: _cardDeco(cardBg),
            child: Column(
              children: [
                _buildTextField(
                  controller: _eventTitleCtrl,
                  label: 'Judul Event',
                  hint: 'cth: Reuni Angkatan 2020',
                  icon: Icons.event_rounded,
                  accent: accent,
                  cardBg: cardBg,
                  textColor: textColor,
                  mutedColor: mutedColor,
                ),
                const SizedBox(height: 14),
                _buildTextField(
                  controller: _eventDescCtrl,
                  label: 'Deskripsi (opsional)',
                  hint: 'Detail tentang event...',
                  icon: Icons.description_rounded,
                  maxLines: 3,
                  accent: accent,
                  cardBg: cardBg,
                  textColor: textColor,
                  mutedColor: mutedColor,
                ),
                const SizedBox(height: 14),
                _buildTextField(
                  controller: _eventLocationCtrl,
                  label: 'Lokasi',
                  hint: 'cth: Aula Serbaguna, Jakarta',
                  icon: Icons.location_on_rounded,
                  accent: accent,
                  cardBg: cardBg,
                  textColor: textColor,
                  mutedColor: mutedColor,
                ),
                const SizedBox(height: 14),
                // Date Picker
                GestureDetector(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _eventDate ?? DateTime.now(),
                      firstDate: DateTime.now(),
                      lastDate:
                          DateTime.now().add(const Duration(days: 365 * 3)),
                      builder: (ctx, child) => Theme(
                        data: Theme.of(ctx).copyWith(
                          colorScheme: ColorScheme.dark(
                            primary: accent,
                            onPrimary: AppColors.dark,
                            surface: cardBg,
                          ),
                        ),
                        child: child!,
                      ),
                    );
                    if (picked != null) setState(() => _eventDate = picked);
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 14),
                    decoration: BoxDecoration(
                      color: widget.isDark
                          ? Colors.grey.shade900
                          : Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: _eventDate != null
                            ? accent
                            : (widget.isDark
                                ? Colors.grey.shade700
                                : Colors.grey.shade300),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.calendar_today_rounded,
                            color: _eventDate != null ? accent : mutedColor,
                            size: 18),
                        const SizedBox(width: 10),
                        Text(
                          _eventDate == null
                              ? 'Pilih Tanggal Event'
                              : '${_eventDate!.day.toString().padLeft(2, '0')} / '
                                  '${_eventDate!.month.toString().padLeft(2, '0')} / '
                                  '${_eventDate!.year}',
                          style: GoogleFonts.spaceGrotesk(
                            color: _eventDate != null ? textColor : mutedColor,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: _isCreatingEvent
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: AppColors.dark))
                        : const Icon(Icons.add_circle_rounded, size: 18),
                    label: Text(
                      _isCreatingEvent ? 'Membuat...' : 'Buat Event',
                      style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.w700, fontSize: 14),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: accent,
                      foregroundColor: AppColors.dark,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: _isCreatingEvent ? null : _createEvent,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    required Color accent,
    required Color cardBg,
    required Color textColor,
    required Color mutedColor,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: GoogleFonts.spaceGrotesk(
                fontWeight: FontWeight.w600,
                fontSize: 13,
                color: textColor)),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          style: GoogleFonts.spaceGrotesk(color: textColor, fontSize: 14),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle:
                GoogleFonts.spaceGrotesk(color: mutedColor, fontSize: 14),
            prefixIcon: Icon(icon, color: accent, size: 20),
            filled: true,
            fillColor: widget.isDark
                ? Colors.grey.shade900
                : Colors.grey.shade50,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                  color: widget.isDark
                      ? Colors.grey.shade700
                      : Colors.grey.shade300),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                  color: widget.isDark
                      ? Colors.grey.shade700
                      : Colors.grey.shade300),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: accent, width: 2),
            ),
          ),
        ),
      ],
    );
  }

  // ── HELPERS ──────────────────────────────────────────

  Widget _sectionHeader(String title, Color textColor) {
    return Text(
      title,
      style: GoogleFonts.spaceGrotesk(
        fontWeight: FontWeight.w800,
        fontSize: 16,
        color: textColor,
      ),
    );
  }

  BoxDecoration _cardDeco(Color cardBg) {
    return BoxDecoration(
      color: cardBg,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(
        color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        width: 1.5,
      ),
      boxShadow: [
        BoxShadow(
          color: AppColors.dark.withValues(alpha: 0.04),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ],
    );
  }

  Widget _buildEmptyState(String title, String subtitle, Color accent,
      Color textColor, Color mutedColor) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(title,
                style: GoogleFonts.spaceGrotesk(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: textColor)),
            if (subtitle.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(subtitle,
                  style: GoogleFonts.spaceGrotesk(
                      fontSize: 13, color: mutedColor),
                  textAlign: TextAlign.center),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildRetryWidget(
      String msg, VoidCallback onRetry, Color accent, Color textColor) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(msg,
              style:
                  GoogleFonts.spaceGrotesk(color: textColor, fontSize: 14)),
          const SizedBox(height: 16),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: accent,
              foregroundColor: AppColors.dark,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: onRetry,
            child: Text('Coba Lagi',
                style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}
