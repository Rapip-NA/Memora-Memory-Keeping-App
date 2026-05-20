import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/widgets/neo_widgets.dart';
import 'package:memora_app/screens/login_screen.dart';

// ──────────────────────────────────────────────────────
// MODEL
// ──────────────────────────────────────────────────────
class PostModel {
  final int id;
  final String content;
  final String? photoUrl;
  final String? category;
  final int likesCount;
  final bool isLiked;
  final int commentsCount;
  final String authorName;
  final String? authorPhotoUrl;
  final String createdAt;

  PostModel({
    required this.id,
    required this.content,
    this.photoUrl,
    this.category,
    required this.likesCount,
    required this.isLiked,
    required this.commentsCount,
    required this.authorName,
    this.authorPhotoUrl,
    required this.createdAt,
  });

  factory PostModel.fromJson(Map<String, dynamic> json) {
    final author = json['author'] as Map<String, dynamic>? ?? {};
    return PostModel(
      id: json['id'] as int,
      content: json['content'] as String? ?? '',
      photoUrl: json['photo_url'] as String?,
      category: json['category'] as String?,
      likesCount: json['likes_count'] as int? ?? 0,
      isLiked: json['is_liked'] as bool? ?? false,
      commentsCount: json['comments_count'] as int? ?? 0,
      authorName: author['name'] as String? ?? 'User',
      authorPhotoUrl: author['photo_url'] as String?,
      createdAt: json['created_at'] as String? ?? '',
    );
  }

  PostModel copyWith({bool? isLiked, int? likesCount}) {
    return PostModel(
      id: id,
      content: content,
      photoUrl: photoUrl,
      category: category,
      likesCount: likesCount ?? this.likesCount,
      isLiked: isLiked ?? this.isLiked,
      commentsCount: commentsCount,
      authorName: authorName,
      authorPhotoUrl: authorPhotoUrl,
      createdAt: createdAt,
    );
  }
}

// ──────────────────────────────────────────────────────
// SCREEN
// ──────────────────────────────────────────────────────
class FeedScreen extends StatefulWidget {
  final bool isDark;
  final VoidCallback onToggleTheme;

  const FeedScreen({
    super.key,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<FeedScreen> createState() => _FeedScreenState();
}

class _FeedScreenState extends State<FeedScreen>
    with TickerProviderStateMixin {
  // ── Nav ──
  int _currentTab = 0;

  // ── User ──
  String _userName = '';
  String _userEmail = '';
  String? _userPhotoUrl;

  // ── API ──
  String _baseUrl = 'http://127.0.0.1:8001';
  String _authToken = '';

  // ── Feed State ──
  List<PostModel> _posts = [];
  bool _isLoadingPosts = true;
  bool _hasMore = true;
  int _currentPage = 1;
  final ScrollController _feedScrollController = ScrollController();

  // ── Compose ──
  final TextEditingController _composeController = TextEditingController();
  bool _isPosting = false;

  // ── Animation ──
  late AnimationController _fabController;

  @override
  void initState() {
    super.initState();
    _fabController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
    _loadUserData();
    _feedScrollController.addListener(_onFeedScroll);
  }

  @override
  void dispose() {
    _fabController.dispose();
    _feedScrollController.dispose();
    _composeController.dispose();
    super.dispose();
  }

  void _onFeedScroll() {
    if (_feedScrollController.position.pixels >=
        _feedScrollController.position.maxScrollExtent - 200) {
      if (_hasMore && !_isLoadingPosts) {
        _loadMorePosts();
      }
    }
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userName = prefs.getString('user_name') ?? 'User';
      _userEmail = prefs.getString('user_email') ?? '';
      _authToken = prefs.getString('auth_token') ?? '';
      String savedUrl = prefs.getString('backend_url') ?? 'http://127.0.0.1:8001';
      if (savedUrl == 'http://10.0.2.2:8000' || savedUrl == 'http://172.18.20.187:8001') {
        savedUrl = 'http://127.0.0.1:8001';
        prefs.setString('backend_url', savedUrl);
      }
      _baseUrl = savedUrl;
      // Reconstruct photo URL from base URL if stored separately, else null
      _userPhotoUrl = null;
    });
    await _fetchPosts(reset: true);
  }

  Map<String, String> get _authHeaders => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $_authToken',
      };

  Future<void> _fetchPosts({bool reset = false}) async {
    if (reset) {
      setState(() {
        _isLoadingPosts = true;
        _currentPage = 1;
        _hasMore = true;
        _posts = [];
      });
    }
    try {
      final response = await http
          .get(
            Uri.parse('$_baseUrl/api/posts?page=$_currentPage'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List items = data['data'] as List? ?? [];
        final meta = data['meta'] as Map<String, dynamic>?;
        final lastPage = meta?['last_page'] as int? ?? 1;
        final List<PostModel> fetched =
            items.map((e) => PostModel.fromJson(e as Map<String, dynamic>)).toList();
        setState(() {
          if (reset) {
            _posts = fetched;
          } else {
            _posts.addAll(fetched);
          }
          _hasMore = _currentPage < lastPage;
          _isLoadingPosts = false;
        });
      } else {
        setState(() => _isLoadingPosts = false);
      }
    } catch (_) {
      setState(() => _isLoadingPosts = false);
    }
  }

  Future<void> _loadMorePosts() async {
    _currentPage++;
    await _fetchPosts();
  }

  Future<void> _toggleLike(int postId) async {
    final idx = _posts.indexWhere((p) => p.id == postId);
    if (idx == -1) return;

    final post = _posts[idx];
    // Optimistic update
    setState(() {
      _posts[idx] = post.copyWith(
        isLiked: !post.isLiked,
        likesCount: post.isLiked ? post.likesCount - 1 : post.likesCount + 1,
      );
    });

    try {
      await http
          .post(
            Uri.parse('$_baseUrl/api/posts/$postId/like'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));
    } catch (_) {
      // Revert on error
      setState(() {
        _posts[idx] = post;
      });
    }
  }

  Future<void> _submitPost(String content) async {
    if (content.trim().isEmpty) return;
    setState(() => _isPosting = true);

    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/api/posts'),
            headers: _authHeaders,
            body: jsonEncode({
              'content': content.trim(),
              'category': 'Update',
            }),
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 201) {
        _composeController.clear();
        HapticFeedback.mediumImpact();
        if (mounted) {
          final scaffoldMessenger = ScaffoldMessenger.of(context);
          await _fetchPosts(reset: true);
          _feedScrollController.animateTo(
            0,
            duration: const Duration(milliseconds: 400),
            curve: Curves.easeOut,
          );
          scaffoldMessenger.showSnackBar(
            SnackBar(
              backgroundColor: widget.isDark ? AppColors.orange : AppColors.lime,
              content: Text(
                '✅ Post berhasil dibagikan!',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.bold,
                  color: AppColors.dark,
                ),
              ),
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: const BorderSide(color: AppColors.dark, width: 2),
              ),
            ),
          );
        }
      } else {
        final data = jsonDecode(response.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: Colors.red.shade100,
              content: Text(
                data['message'] ?? 'Gagal mengirim post.',
                style: GoogleFonts.spaceGrotesk(color: Colors.red.shade900, fontWeight: FontWeight.w600),
              ),
            ),
          );
        }
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Tidak dapat terhubung ke server.',
              style: GoogleFonts.spaceGrotesk(color: Colors.white),
            ),
            backgroundColor: Colors.red.shade800,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isPosting = false);
    }
  }

  Future<void> _logout() async {
    try {
      await http
          .post(
            Uri.parse('$_baseUrl/api/auth/logout'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));
    } catch (_) {}

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_name');
    await prefs.remove('user_email');

    if (mounted) {
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (context) => LoginScreen(
            isDark: widget.isDark,
            onToggleTheme: widget.onToggleTheme,
          ),
        ),
        (route) => false,
      );
    }
  }

  // ──────────────────────────────────────────────────────
  // BUILD
  // ──────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
    final bgColor = widget.isDark ? AppColors.darkBg : AppColors.light;
    final textColor = widget.isDark ? Colors.white : AppColors.dark;
    final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
    final mutedColor = widget.isDark ? Colors.grey.shade500 : Colors.grey.shade500;

    return Scaffold(
      backgroundColor: bgColor,
      body: SafeArea(
        child: Column(
          children: [
            _buildTopBar(textColor, accentColor, cardBg),
            Expanded(
              child: IndexedStack(
                index: _currentTab,
                children: [
                  _buildFeedTab(textColor, accentColor, cardBg, mutedColor),
                  _buildPlaceholderTab(Icons.search_rounded, 'Explore', textColor, accentColor),
                  _buildPlaceholderTab(Icons.photo_album_rounded, 'Gallery', textColor, accentColor),
                  _buildPlaceholderTab(Icons.calendar_month_rounded, 'Events', textColor, accentColor),
                  _buildProfileTab(textColor, accentColor, cardBg, mutedColor),
                ],
              ),
            ),
            _buildBottomNav(accentColor, textColor, cardBg),
          ],
        ),
      ),
    );
  }

  // ── TOP BAR ──────────────────────────────────────────
  Widget _buildTopBar(Color textColor, Color accentColor, Color cardBg) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      decoration: BoxDecoration(
        color: widget.isDark ? AppColors.darkBg : Colors.white,
        border: Border(
          bottom: BorderSide(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            width: 1,
          ),
        ),
      ),
      child: Row(
        children: [
          // Logo
          Image.asset(
            widget.isDark ? 'lib/img/Memora_Dark 1.png' : 'lib/img/Memora 2.png',
            height: 36,
          ),
          const Spacer(),
          // Theme toggle
          GestureDetector(
            onTap: widget.onToggleTheme,
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 250),
              child: Icon(
                widget.isDark ? Icons.wb_sunny_rounded : Icons.dark_mode_rounded,
                key: ValueKey(widget.isDark),
                color: textColor,
                size: 22,
              ),
            ),
          ),
          const SizedBox(width: 12),
          // Notification
          Stack(
            clipBehavior: Clip.none,
            children: [
              Icon(Icons.notifications_outlined, color: textColor, size: 24),
              Positioned(
                top: -2,
                right: -2,
                child: Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: widget.isDark ? AppColors.darkBg : Colors.white,
                      width: 1.5,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ── FEED TAB ──────────────────────────────────────────
  Widget _buildFeedTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return RefreshIndicator(
      color: accentColor,
      onRefresh: () => _fetchPosts(reset: true),
      child: CustomScrollView(
        controller: _feedScrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: _buildComposeBox(textColor, accentColor, cardBg),
          ),
          if (_isLoadingPosts && _posts.isEmpty)
            SliverToBoxAdapter(
              child: _buildSkeletonList(cardBg),
            )
          else if (_posts.isEmpty)
            SliverToBoxAdapter(
              child: _buildEmptyFeed(textColor, mutedColor, accentColor),
            )
          else
            SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  if (index == _posts.length) {
                    return _hasMore
                        ? Padding(
                            padding: const EdgeInsets.all(24.0),
                            child: Center(
                              child: CircularProgressIndicator(color: accentColor),
                            ),
                          )
                        : Padding(
                            padding: const EdgeInsets.symmetric(vertical: 32),
                            child: Center(
                              child: Text(
                                '— Semua post telah dimuat —',
                                style: GoogleFonts.spaceGrotesk(
                                  color: mutedColor,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                          );
                  }
                  return _buildPostCard(
                    _posts[index],
                    textColor,
                    accentColor,
                    cardBg,
                    mutedColor,
                  );
                },
                childCount: _posts.length + 1,
              ),
            ),
        ],
      ),
    );
  }

  // ── COMPOSE BOX ──────────────────────────────────────
  Widget _buildComposeBox(Color textColor, Color accentColor, Color cardBg) {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(20),
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
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              _buildAvatar(_userPhotoUrl, _userName, 40),
              const SizedBox(width: 12),
              Expanded(
                child: GestureDetector(
                  onTap: () => _showComposeSheet(textColor, accentColor, cardBg),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: widget.isDark ? const Color(0xFF1A1B24) : AppColors.light,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(
                        color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
                        width: 1,
                      ),
                    ),
                    child: Text(
                      'Apa yang sedang terjadi?',
                      style: GoogleFonts.spaceGrotesk(
                        color: Colors.grey.shade400,
                        fontSize: 15,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Divider(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            height: 1,
          ),
          const SizedBox(height: 12),
          // Quick action buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildQuickAction(
                icon: Icons.image_outlined,
                label: 'Foto',
                color: const Color(0xFF1D9BF0),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg),
              ),
              _buildQuickAction(
                icon: Icons.emoji_emotions_outlined,
                label: 'Emoji',
                color: const Color(0xFFF59E0B),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg),
              ),
              _buildQuickAction(
                icon: Icons.calendar_today_outlined,
                label: 'Event',
                color: const Color(0xFF10B981),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg),
              ),
              _buildQuickAction(
                icon: Icons.poll_outlined,
                label: 'Polling',
                color: const Color(0xFF8B5CF6),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildQuickAction({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 6),
          Text(
            label,
            style: GoogleFonts.spaceGrotesk(
              color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }

  void _showComposeSheet(Color textColor, Color accentColor, Color cardBg) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ComposeSheet(
        isDark: widget.isDark,
        textColor: textColor,
        accentColor: accentColor,
        cardBg: cardBg,
        userPhotoUrl: _userPhotoUrl,
        userName: _userName,
        isPosting: _isPosting,
        onSubmit: _submitPost,
      ),
    );
  }

  // ── POST CARD ─────────────────────────────────────────
  Widget _buildPostCard(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.dark.withValues(alpha: 0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                _buildAvatar(post.authorPhotoUrl, post.authorName, 44),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        post.authorName,
                        style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                          color: textColor,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          if (post.category != null) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: accentColor.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(99),
                              ),
                              child: Text(
                                post.category!,
                                style: GoogleFonts.spaceGrotesk(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: accentColor,
                                ),
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text('•', style: TextStyle(color: mutedColor, fontSize: 11)),
                            const SizedBox(width: 6),
                          ],
                          Text(
                            post.createdAt,
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 12,
                              color: mutedColor,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Icon(Icons.more_horiz, color: mutedColor, size: 20),
              ],
            ),
          ),

          // Content
          if (post.content.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(
                post.content,
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 15,
                  color: textColor,
                  height: 1.55,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),

          // Image (if any)
          if (post.photoUrl != null) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(0)),
              child: Image.network(
                post.photoUrl!,
                width: double.infinity,
                fit: BoxFit.cover,
                loadingBuilder: (context, child, progress) {
                  if (progress == null) return child;
                  return Container(
                    height: 200,
                    color: widget.isDark ? Colors.grey.shade900 : Colors.grey.shade100,
                    child: Center(
                      child: CircularProgressIndicator(
                        color: accentColor,
                        strokeWidth: 2,
                        value: progress.expectedTotalBytes != null
                            ? progress.cumulativeBytesLoaded / progress.expectedTotalBytes!
                            : null,
                      ),
                    ),
                  );
                },
                errorBuilder: (context, error, stack) => Container(
                  height: 180,
                  color: widget.isDark ? Colors.grey.shade900 : Colors.grey.shade100,
                  child: Center(
                    child: Icon(Icons.broken_image_outlined, color: mutedColor, size: 48),
                  ),
                ),
              ),
            ),
          ],

          // Actions
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
            child: Row(
              children: [
                _buildActionButton(
                  icon: post.isLiked ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                  label: '${post.likesCount}',
                  color: post.isLiked ? Colors.red : mutedColor,
                  onTap: () => _toggleLike(post.id),
                ),
                const SizedBox(width: 4),
                _buildActionButton(
                  icon: Icons.chat_bubble_outline_rounded,
                  label: '${post.commentsCount}',
                  color: mutedColor,
                  onTap: () => _showCommentsSheet(post, textColor, accentColor, cardBg, mutedColor),
                ),
                const SizedBox(width: 4),
                _buildActionButton(
                  icon: Icons.repeat_rounded,
                  label: '',
                  color: mutedColor,
                  onTap: () {},
                ),
                const Spacer(),
                _buildActionButton(
                  icon: Icons.bookmark_border_rounded,
                  label: '',
                  color: mutedColor,
                  onTap: () {},
                ),
                _buildActionButton(
                  icon: Icons.share_outlined,
                  label: '',
                  color: mutedColor,
                  onTap: () {
                    HapticFeedback.lightImpact();
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(
                          'Link post disalin!',
                          style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.w600),
                        ),
                        backgroundColor: AppColors.dark,
                        behavior: SnackBarBehavior.floating,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        onTap();
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
        child: Row(
          children: [
            Icon(icon, color: color, size: 20),
            if (label.isNotEmpty) ...[
              const SizedBox(width: 4),
              Text(
                label,
                style: GoogleFonts.spaceGrotesk(
                  color: color,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  // ── COMMENTS SHEET ────────────────────────────────────
  void _showCommentsSheet(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _CommentsSheet(
        isDark: widget.isDark,
        post: post,
        baseUrl: _baseUrl,
        authHeaders: _authHeaders,
        textColor: textColor,
        accentColor: accentColor,
        cardBg: cardBg,
        mutedColor: mutedColor,
        userPhotoUrl: _userPhotoUrl,
        userName: _userName,
      ),
    );
  }

  // ── SKELETON ──────────────────────────────────────────
  Widget _buildSkeletonList(Color cardBg) {
    return Column(
      children: List.generate(
        3,
        (_) => _buildSkeletonCard(cardBg),
      ),
    );
  }

  Widget _buildSkeletonCard(Color cardBg) {
    final shimmer = widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200;
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _shimmerBox(44, 44, shimmer, radius: 22),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _shimmerBox(16, 120, shimmer, radius: 8),
                  const SizedBox(height: 6),
                  _shimmerBox(12, 80, shimmer, radius: 8),
                ],
              ),
            ],
          ),
          const SizedBox(height: 14),
          _shimmerBox(14, double.infinity, shimmer, radius: 8),
          const SizedBox(height: 6),
          _shimmerBox(14, MediaQuery.of(context).size.width * 0.7, shimmer, radius: 8),
          const SizedBox(height: 12),
          _shimmerBox(180, double.infinity, shimmer, radius: 12),
        ],
      ),
    );
  }

  Widget _shimmerBox(double h, double w, Color color, {double radius = 4}) {
    return Container(
      height: h,
      width: w,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }

  // ── EMPTY FEED ────────────────────────────────────────
  Widget _buildEmptyFeed(Color textColor, Color mutedColor, Color accentColor) {
    return Padding(
      padding: const EdgeInsets.all(48),
      child: Column(
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: widget.isDark ? AppColors.cardDark : Colors.white,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppColors.dark, width: 2),
              boxShadow: const [
                BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
              ],
            ),
            child: Icon(Icons.newspaper_rounded, size: 40, color: accentColor),
          ),
          const SizedBox(height: 24),
          Text(
            'Feed masih kosong!',
            style: GoogleFonts.spaceGrotesk(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Jadilah yang pertama berbagi cerita kepada komunitas The Archive.',
            textAlign: TextAlign.center,
            style: GoogleFonts.spaceGrotesk(
              fontSize: 14,
              color: mutedColor,
              fontWeight: FontWeight.w500,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }

  // ── PROFILE TAB ───────────────────────────────────────
  Widget _buildProfileTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          const SizedBox(height: 16),
          // Profile Header Card
          NeoBox(
            isDark: widget.isDark,
            backgroundColor: cardBg,
            borderRadius: 24,
            borderWidth: 2,
            shadowOffset: 6,
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    _buildAvatar(_userPhotoUrl, _userName, 80),
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: const Color(0xFF22C55E),
                        shape: BoxShape.circle,
                        border: Border.all(color: cardBg, width: 2),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  _userName,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  _userEmail,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 14,
                    color: mutedColor,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(99),
                  ),
                  child: Text(
                    'Class of \'24',
                    style: GoogleFonts.spaceGrotesk(
                      fontSize: 13,
                      color: accentColor,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          // Settings options
          _buildProfileOption(
            icon: Icons.person_outline_rounded,
            label: 'Edit Profil',
            textColor: textColor,
            accentColor: accentColor,
            cardBg: cardBg,
            onTap: () {},
          ),
          const SizedBox(height: 12),
          _buildProfileOption(
            icon: Icons.notifications_outlined,
            label: 'Notifikasi',
            textColor: textColor,
            accentColor: accentColor,
            cardBg: cardBg,
            onTap: () {},
          ),
          const SizedBox(height: 12),
          _buildProfileOption(
            icon: widget.isDark ? Icons.wb_sunny_rounded : Icons.dark_mode_rounded,
            label: widget.isDark ? 'Mode Terang' : 'Mode Gelap',
            textColor: textColor,
            accentColor: accentColor,
            cardBg: cardBg,
            onTap: widget.onToggleTheme,
          ),
          const SizedBox(height: 12),
          _buildProfileOption(
            icon: Icons.help_outline_rounded,
            label: 'Bantuan & Dukungan',
            textColor: textColor,
            accentColor: accentColor,
            cardBg: cardBg,
            onTap: () {},
          ),
          const SizedBox(height: 24),

          // Logout button
          NeoButton(
            isDark: widget.isDark,
            backgroundColor: const Color(0xFFFEE2E2),
            textColor: Colors.red.shade800,
            onTap: () => _showLogoutDialog(textColor, accentColor, cardBg),
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.logout_rounded, color: Colors.red.shade800, size: 20),
                const SizedBox(width: 10),
                Text(
                  'Keluar dari Akun',
                  style: GoogleFonts.spaceGrotesk(
                    fontWeight: FontWeight.w800,
                    color: Colors.red.shade800,
                    fontSize: 15,
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),
          Text(
            '© 2026 The Archive (Memora)',
            style: GoogleFonts.spaceGrotesk(
              fontSize: 12,
              color: mutedColor,
            ),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }

  Widget _buildProfileOption({
    required IconData icon,
    required String label,
    required Color textColor,
    required Color accentColor,
    required Color cardBg,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: accentColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: accentColor, size: 20),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                label,
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: textColor,
                ),
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: textColor.withValues(alpha: 0.4), size: 22),
          ],
        ),
      ),
    );
  }

  void _showLogoutDialog(Color textColor, Color accentColor, Color cardBg) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: cardBg,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
          side: const BorderSide(color: AppColors.dark, width: 2),
        ),
        title: Text(
          'Keluar?',
          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor),
        ),
        content: Text(
          'Anda akan keluar dari akun ini. Lanjutkan?',
          style: GoogleFonts.spaceGrotesk(color: textColor, fontWeight: FontWeight.w500),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Batal', style: GoogleFonts.spaceGrotesk(color: accentColor, fontWeight: FontWeight.bold)),
          ),
          NeoButton(
            isDark: widget.isDark,
            backgroundColor: const Color(0xFFFEE2E2),
            textColor: Colors.red.shade800,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            onTap: () {
              Navigator.pop(ctx);
              _logout();
            },
            child: Text('Keluar', style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.bold, color: Colors.red.shade800)),
          ),
        ],
      ),
    );
  }

  // ── PLACEHOLDER TAB ───────────────────────────────────
  Widget _buildPlaceholderTab(IconData icon, String label, Color textColor, Color accentColor) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(
              color: widget.isDark ? AppColors.cardDark : Colors.white,
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: AppColors.dark, width: 2),
              boxShadow: const [
                BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
              ],
            ),
            child: Icon(icon, size: 44, color: accentColor),
          ),
          const SizedBox(height: 20),
          Text(
            label,
            style: GoogleFonts.spaceGrotesk(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Segera hadir!',
            style: GoogleFonts.spaceGrotesk(
              fontSize: 14,
              color: accentColor,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  // ── BOTTOM NAV ────────────────────────────────────────
  Widget _buildBottomNav(Color accentColor, Color textColor, Color cardBg) {
    final items = [
      (Icons.home_rounded, Icons.home_outlined, 'Home'),
      (Icons.search_rounded, Icons.search_outlined, 'Explore'),
      (Icons.photo_album_rounded, Icons.photo_album_outlined, 'Gallery'),
      (Icons.calendar_month_rounded, Icons.calendar_month_outlined, 'Events'),
      (Icons.person_rounded, Icons.person_outline_rounded, 'Profil'),
    ];

    return Container(
      decoration: BoxDecoration(
        color: cardBg,
        border: Border(
          top: BorderSide(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            width: 1.5,
          ),
        ),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: List.generate(items.length, (i) {
              final isActive = _currentTab == i;
              final item = items[i];
              return GestureDetector(
                onTap: () {
                  HapticFeedback.selectionClick();
                  setState(() => _currentTab = i);
                },
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    color: isActive ? accentColor.withValues(alpha: 0.12) : Colors.transparent,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isActive ? item.$1 : item.$2,
                        color: isActive ? accentColor : Colors.grey.shade500,
                        size: 24,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        item.$3,
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 10,
                          fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                          color: isActive ? accentColor : Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ),
        ),
      ),
    );
  }

  // ── AVATAR WIDGET ─────────────────────────────────────
  Widget _buildAvatar(String? photoUrl, String name, double size) {
    final initials = name.isNotEmpty
        ? name.trim().split(' ').take(2).map((w) => w[0].toUpperCase()).join()
        : 'U';

    if (photoUrl != null && photoUrl.isNotEmpty) {
      return ClipOval(
        child: Image.network(
          photoUrl,
          width: size,
          height: size,
          fit: BoxFit.cover,
          errorBuilder: (context, err, stack) => _fallbackAvatar(initials, size),
        ),
      );
    }
    return _fallbackAvatar(initials, size);
  }

  Widget _fallbackAvatar(String initials, double size) {
    return Container(
      width: size,
      height: size,
      decoration: const BoxDecoration(
        color: AppColors.dark,
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.spaceGrotesk(
            color: Colors.white,
            fontWeight: FontWeight.w700,
            fontSize: size * 0.32,
          ),
        ),
      ),
    );
  }
}

// ──────────────────────────────────────────────────────
// COMPOSE BOTTOM SHEET (Separate StatefulWidget)
// ──────────────────────────────────────────────────────
class _ComposeSheet extends StatefulWidget {
  final bool isDark;
  final Color textColor;
  final Color accentColor;
  final Color cardBg;
  final String? userPhotoUrl;
  final String userName;
  final bool isPosting;
  final Future<void> Function(String) onSubmit;

  const _ComposeSheet({
    required this.isDark,
    required this.textColor,
    required this.accentColor,
    required this.cardBg,
    this.userPhotoUrl,
    required this.userName,
    required this.isPosting,
    required this.onSubmit,
  });

  @override
  State<_ComposeSheet> createState() => _ComposeSheetState();
}

class _ComposeSheetState extends State<_ComposeSheet> {
  final TextEditingController _ctrl = TextEditingController();
  bool _isPosting = false;

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_ctrl.text.trim().isEmpty) return;
    setState(() => _isPosting = true);
    await widget.onSubmit(_ctrl.text);
    if (mounted) Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final mutedColor = widget.isDark ? Colors.grey.shade500 : Colors.grey.shade500;
    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      maxChildSize: 0.95,
      minChildSize: 0.5,
      builder: (ctx, ctrl) {
        return Container(
          decoration: BoxDecoration(
            color: widget.cardBg,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            border: const Border(
              top: BorderSide(color: AppColors.dark, width: 2),
            ),
            boxShadow: const [
              BoxShadow(
                color: AppColors.dark,
                offset: Offset(0, -4),
                blurRadius: 0,
              ),
            ],
          ),
          child: Column(
            children: [
              // Drag handle
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: mutedColor.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              // Header
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                child: Row(
                  children: [
                    Text(
                      'Buat Post',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: widget.textColor,
                      ),
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => Navigator.pop(context),
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(Icons.close_rounded, color: widget.textColor, size: 18),
                      ),
                    ),
                  ],
                ),
              ),
              Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200, height: 16),
              // Body
              Expanded(
                child: ListView(
                  controller: ctrl,
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildAvatarInSheet(widget.userPhotoUrl, widget.userName),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextField(
                            controller: _ctrl,
                            autofocus: true,
                            maxLines: null,
                            style: GoogleFonts.spaceGrotesk(
                              color: widget.textColor,
                              fontSize: 17,
                              fontWeight: FontWeight.w500,
                            ),
                            decoration: InputDecoration(
                              hintText: 'Apa yang sedang terjadi?',
                              hintStyle: GoogleFonts.spaceGrotesk(
                                color: mutedColor,
                                fontSize: 17,
                                fontWeight: FontWeight.w500,
                              ),
                              border: InputBorder.none,
                            ),
                            onChanged: (_) => setState(() {}),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
              // Action bar
              Container(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                decoration: BoxDecoration(
                  color: widget.cardBg,
                  border: Border(
                    top: BorderSide(
                      color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
                    ),
                  ),
                ),
                child: Row(
                  children: [
                    Icon(Icons.image_outlined, color: const Color(0xFF1D9BF0), size: 24),
                    const SizedBox(width: 16),
                    Icon(Icons.emoji_emotions_outlined, color: const Color(0xFFF59E0B), size: 24),
                    const SizedBox(width: 16),
                    Icon(Icons.calendar_today_outlined, color: const Color(0xFF10B981), size: 24),
                    const Spacer(),
                    NeoButton(
                      isDark: widget.isDark,
                      backgroundColor: _ctrl.text.trim().isEmpty || _isPosting
                          ? Colors.grey.shade400
                          : AppColors.dark,
                      textColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                      onTap: _ctrl.text.trim().isEmpty || _isPosting ? null : _submit,
                      child: _isPosting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                              ),
                            )
                          : Text(
                              'Post',
                              style: GoogleFonts.spaceGrotesk(
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                fontSize: 15,
                              ),
                            ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildAvatarInSheet(String? photoUrl, String name) {
    final initials = name.isNotEmpty
        ? name.trim().split(' ').take(2).map((w) => w[0].toUpperCase()).join()
        : 'U';
    if (photoUrl != null && photoUrl.isNotEmpty) {
      return ClipOval(
        child: Image.network(
          photoUrl,
          width: 44,
          height: 44,
          fit: BoxFit.cover,
          errorBuilder: (context, err, stack) => _fallback(initials),
        ),
      );
    }
    return _fallback(initials);
  }

  Widget _fallback(String initials) {
    return Container(
      width: 44,
      height: 44,
      decoration: const BoxDecoration(color: AppColors.dark, shape: BoxShape.circle),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.spaceGrotesk(
            color: Colors.white,
            fontWeight: FontWeight.w700,
            fontSize: 14,
          ),
        ),
      ),
    );
  }
}

// ──────────────────────────────────────────────────────
// COMMENTS BOTTOM SHEET
// ──────────────────────────────────────────────────────
class _CommentsSheet extends StatefulWidget {
  final bool isDark;
  final PostModel post;
  final String baseUrl;
  final Map<String, String> authHeaders;
  final Color textColor;
  final Color accentColor;
  final Color cardBg;
  final Color mutedColor;
  final String? userPhotoUrl;
  final String userName;

  const _CommentsSheet({
    required this.isDark,
    required this.post,
    required this.baseUrl,
    required this.authHeaders,
    required this.textColor,
    required this.accentColor,
    required this.cardBg,
    required this.mutedColor,
    this.userPhotoUrl,
    required this.userName,
  });

  @override
  State<_CommentsSheet> createState() => _CommentsSheetState();
}

class _CommentsSheetState extends State<_CommentsSheet> {
  List<Map<String, dynamic>> _comments = [];
  bool _isLoading = true;
  final TextEditingController _commentCtrl = TextEditingController();
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _fetchComments();
  }

  @override
  void dispose() {
    _commentCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchComments() async {
    try {
      final res = await http
          .get(
            Uri.parse('${widget.baseUrl}/api/posts/${widget.post.id}/comments'),
            headers: widget.authHeaders,
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _comments = List<Map<String, dynamic>>.from(data['data'] ?? []);
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _sendComment() async {
    final body = _commentCtrl.text.trim();
    if (body.isEmpty) return;
    setState(() => _isSending = true);

    try {
      final res = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/posts/${widget.post.id}/comments'),
            headers: widget.authHeaders,
            body: jsonEncode({'body': body}),
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 201) {
        _commentCtrl.clear();
        HapticFeedback.mediumImpact();
        await _fetchComments();
      }
    } catch (_) {}
    if (mounted) setState(() => _isSending = false);
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      maxChildSize: 0.92,
      minChildSize: 0.4,
      builder: (ctx, ctrl) {
        return Container(
          decoration: BoxDecoration(
            color: widget.cardBg,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            border: const Border(top: BorderSide(color: AppColors.dark, width: 2)),
            boxShadow: const [BoxShadow(color: AppColors.dark, offset: Offset(0, -4), blurRadius: 0)],
          ),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: widget.mutedColor.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                child: Row(
                  children: [
                    Text(
                      'Komentar',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: widget.textColor,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: widget.accentColor.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        '${widget.post.commentsCount}',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: widget.accentColor,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200),
              Expanded(
                child: _isLoading
                    ? Center(child: CircularProgressIndicator(color: widget.accentColor))
                    : _comments.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.chat_bubble_outline_rounded, size: 48, color: widget.mutedColor),
                                const SizedBox(height: 12),
                                Text(
                                  'Belum ada komentar',
                                  style: GoogleFonts.spaceGrotesk(color: widget.mutedColor, fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          )
                        : ListView.builder(
                            controller: ctrl,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            itemCount: _comments.length,
                            itemBuilder: (context, i) {
                              final c = _comments[i];
                              final user = c['user'] as Map<String, dynamic>? ?? {};
                              final authorName = user['name'] as String? ?? 'User';
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 16),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    _miniAvatar(null, authorName),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Container(
                                        padding: const EdgeInsets.all(12),
                                        decoration: BoxDecoration(
                                          color: widget.isDark ? const Color(0xFF252630) : AppColors.light,
                                          borderRadius: BorderRadius.circular(16),
                                        ),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Text(
                                                  authorName,
                                                  style: GoogleFonts.spaceGrotesk(
                                                    fontWeight: FontWeight.w700,
                                                    fontSize: 13,
                                                    color: widget.textColor,
                                                  ),
                                                ),
                                                Text(
                                                  c['created_at']?.toString() ?? '',
                                                  style: GoogleFonts.spaceGrotesk(
                                                    fontSize: 11,
                                                    color: widget.mutedColor,
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              c['body']?.toString() ?? '',
                                              style: GoogleFonts.spaceGrotesk(
                                                fontSize: 13,
                                                color: widget.textColor,
                                                fontWeight: FontWeight.w500,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
              ),
              // Comment input
              Container(
                padding: EdgeInsets.fromLTRB(16, 8, 16, MediaQuery.of(context).viewInsets.bottom + 16),
                decoration: BoxDecoration(
                  color: widget.cardBg,
                  border: Border(top: BorderSide(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200)),
                ),
                child: Row(
                  children: [
                    _miniAvatar(widget.userPhotoUrl, widget.userName),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _commentCtrl,
                        style: GoogleFonts.spaceGrotesk(color: widget.textColor, fontSize: 14),
                        decoration: InputDecoration(
                          hintText: 'Tulis komentar...',
                          hintStyle: GoogleFonts.spaceGrotesk(color: widget.mutedColor, fontSize: 14),
                          filled: true,
                          fillColor: widget.isDark ? const Color(0xFF1A1B24) : AppColors.light,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(24),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        onChanged: (_) => setState(() {}),
                      ),
                    ),
                    const SizedBox(width: 8),
                    GestureDetector(
                      onTap: _isSending || _commentCtrl.text.trim().isEmpty ? null : _sendComment,
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: _commentCtrl.text.trim().isNotEmpty
                              ? widget.accentColor
                              : Colors.grey.shade400,
                          shape: BoxShape.circle,
                        ),
                        child: _isSending
                            ? const Padding(
                                padding: EdgeInsets.all(10),
                                child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation<Color>(Colors.white)),
                              )
                            : const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _miniAvatar(String? photoUrl, String name) {
    final initials = name.isNotEmpty
        ? name.trim().split(' ').take(2).map((w) => w[0].toUpperCase()).join()
        : 'U';
    if (photoUrl != null && photoUrl.isNotEmpty) {
      return ClipOval(
        child: Image.network(
          photoUrl,
          width: 34,
          height: 34,
          fit: BoxFit.cover,
          errorBuilder: (context, err, stack) => _fallback(initials),
        ),
      );
    }
    return _fallback(initials);
  }

  Widget _fallback(String initials) {
    return Container(
      width: 34,
      height: 34,
      decoration: const BoxDecoration(color: AppColors.dark, shape: BoxShape.circle),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
        ),
      ),
    );
  }
}
