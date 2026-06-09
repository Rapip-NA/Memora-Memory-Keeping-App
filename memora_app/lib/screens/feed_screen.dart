import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:gal/gal.dart';
import 'package:path_provider/path_provider.dart';
import 'package:memora_app/config/app_config.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/widgets/neo_widgets.dart';
import 'package:memora_app/screens/login_screen.dart';
import 'package:memora_app/screens/admin_screen.dart';
import 'package:memora_app/widgets/member_profile_screen.dart';
import 'package:memora_app/screens/bookmarks_screen.dart';
import 'package:memora_app/widgets/post_video_player.dart';
import 'package:memora_app/screens/notifications_screen.dart';

// ──────────────────────────────────────────────────────
// MODEL
// ──────────────────────────────────────────────────────
// POLL MODELS
// ──────────────────────────────────────────────────────
class PollOptionModel {
  final int id;
  final String text;
  final int votesCount;
  final int percent;

  PollOptionModel({
    required this.id,
    required this.text,
    required this.votesCount,
    required this.percent,
  });

  factory PollOptionModel.fromJson(Map<String, dynamic> json) {
    return PollOptionModel(
      id: json['id'] as int,
      text: json['text'] as String? ?? '',
      votesCount: json['votes_count'] as int? ?? 0,
      percent: json['percent'] as int? ?? 0,
    );
  }
}

class PollModel {
  final int id;
  final int totalVotes;
  final bool isExpired;
  final int? userVotedOptionId;
  final List<PollOptionModel> options;

  PollModel({
    required this.id,
    required this.totalVotes,
    required this.isExpired,
    this.userVotedOptionId,
    required this.options,
  });

  factory PollModel.fromJson(Map<String, dynamic> json) {
    final opts = (json['options'] as List? ?? [])
        .map((e) => PollOptionModel.fromJson(e as Map<String, dynamic>))
        .toList();
    return PollModel(
      id: json['id'] as int,
      totalVotes: json['total_votes'] as int? ?? 0,
      isExpired: json['is_expired'] as bool? ?? false,
      userVotedOptionId: json['user_voted_option_id'] as int?,
      options: opts,
    );
  }

  PollModel copyWith({int? userVotedOptionId, List<PollOptionModel>? options, int? totalVotes}) {
    return PollModel(
      id: id,
      totalVotes: totalVotes ?? this.totalVotes,
      isExpired: isExpired,
      userVotedOptionId: userVotedOptionId ?? this.userVotedOptionId,
      options: options ?? this.options,
    );
  }
}

// ──────────────────────────────────────────────────────
// POST MODEL
// ──────────────────────────────────────────────────────
class PostModel {
  final int id;
  final int authorId;
  final String content;
  final String? photoUrl;
  final String? category;
  final int likesCount;
  final bool isLiked;
  final bool isBookmarked;
  final int commentsCount;
  final String authorName;
  final String? authorPhotoUrl;
  final String createdAt;
  final PollModel? poll;

  PostModel({
    required this.id,
    required this.authorId,
    required this.content,
    this.photoUrl,
    this.category,
    required this.likesCount,
    required this.isLiked,
    required this.isBookmarked,
    required this.commentsCount,
    required this.authorName,
    this.authorPhotoUrl,
    required this.createdAt,
    this.poll,
  });

  factory PostModel.fromJson(Map<String, dynamic> json, {String baseUrl = ''}) {
    final author = json['author'] as Map<String, dynamic>? ?? {};

    // photo_url dari Laravel bisa berupa path relatif '/storage/...' — gabungkan dengan baseUrl
    String? resolveUrl(String? url) {
      if (url == null || url.isEmpty) return null;
      if (url.startsWith('http')) return url;
      return '$baseUrl$url';
    }

    final pollData = json['poll'];
    PollModel? poll;
    if (pollData != null && pollData is Map<String, dynamic>) {
      poll = PollModel.fromJson(pollData);
    }

    return PostModel(
      id: json['id'] as int,
      authorId: author['id'] as int? ?? 0,
      content: json['content'] as String? ?? '',
      photoUrl: resolveUrl(json['photo_url'] as String?),
      category: json['category'] as String?,
      likesCount: json['likes_count'] as int? ?? 0,
      isLiked: json['is_liked'] as bool? ?? false,
      isBookmarked: json['is_bookmarked'] as bool? ?? false,
      commentsCount: json['comments_count'] as int? ?? 0,
      authorName: author['name'] as String? ?? 'User',
      authorPhotoUrl: resolveUrl(author['photo_url'] as String?),
      createdAt: json['created_at'] as String? ?? '',
      poll: poll,
    );
  }

  PostModel copyWith({bool? isLiked, int? likesCount, PollModel? poll, String? content, bool? isBookmarked}) {
    return PostModel(
      id: id,
      authorId: authorId,
      content: content ?? this.content,
      photoUrl: photoUrl,
      category: category,
      likesCount: likesCount ?? this.likesCount,
      isLiked: isLiked ?? this.isLiked,
      isBookmarked: isBookmarked ?? this.isBookmarked,
      commentsCount: commentsCount,
      authorName: authorName,
      authorPhotoUrl: authorPhotoUrl,
      createdAt: createdAt,
      poll: poll ?? this.poll,
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
  int _userId = 0;
  String _userName = '';
  String _userEmail = '';
  String? _userPhotoUrl;
  String _userNickname = '';
  String _userBio = '';
  String _userCity = '';
  String _userJob = '';
  String _userCompany = '';
  String _userQuote = '';
  String _userBornDate = '';
  String? _userBannerUrl;
  String _userCreatedAt = '';
  String _userRole = '';

  // ── My Posts & Loading ──
  List<PostModel> _myPosts = [];
  bool _isLoadingMyPosts = false;
  bool _isLoadingProfile = false;

  // ── API ──
  String _baseUrl = AppConfig.baseUrl;
  String _authToken = '';

  // ── Feed State ──
  int _unreadNotificationCount = 0;
  List<PostModel> _posts = [];
  bool _isLoadingPosts = true;
  bool _hasMore = true;
  int _currentPage = 1;
  final ScrollController _feedScrollController = ScrollController();

  // ── Explore State ──
  List<dynamic> _members = [];
  bool _isLoadingMembers = false;
  final TextEditingController _memberSearchCtrl = TextEditingController();
  final TextEditingController _memberJobCtrl = TextEditingController();
  final TextEditingController _memberCityCtrl = TextEditingController();

  // ── Map State ──
  List<dynamic> _mapUsers = [];
  bool _isLoadingMap = false;
  bool _mapExpanded = false;

  // ── Gallery State ──
  List<dynamic> _memories = [];
  bool _isLoadingMemories = false;

  // ── Events State ──
  List<dynamic> _events = [];
  bool _isLoadingEvents = false;
  bool _isPastSelected = false;
  StateSetter? _eventDetailSheetStateSetter;

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
    _memberSearchCtrl.dispose();
    _memberJobCtrl.dispose();
    _memberCityCtrl.dispose();
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
      String savedUrl = prefs.getString('backend_url') ?? AppConfig.baseUrl;
      // Migrasi: reset URL lama (localhost/IP lokal) ke URL production
      if (!savedUrl.startsWith('https://') && !savedUrl.startsWith('http://memora')) {
        savedUrl = AppConfig.baseUrl;
        prefs.setString('backend_url', savedUrl);
      }
      _baseUrl = savedUrl;
      _userPhotoUrl = null;
    });
    await _fetchPosts(reset: true);
    await _fetchUserProfile();
    await _fetchUnreadNotificationCount();
  }

  String? _resolveUrl(String? url) {
    if (url == null || url.isEmpty) return null;
    if (url.startsWith('http')) return url;
    return '$_baseUrl$url';
  }

  Future<void> _fetchUnreadNotificationCount() async {
    if (_authToken.isEmpty) return;
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/notifications?unread=1'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _unreadNotificationCount = data['data']['unread_count'] as int? ?? 0;
        });
      }
    } catch (_) {}
  }

  Future<void> _fetchUserProfile() async {
    if (_authToken.isEmpty) return;
    setState(() {
      _isLoadingProfile = true;
    });
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/auth/me'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final user = data['data']['user'];
        setState(() {
          _userId = user['id'] as int? ?? 0;
          _userName = user['name'] as String? ?? 'User';
          _userEmail = user['email'] as String? ?? '';
          _userNickname = user['nickname'] as String? ?? '';
          _userBio = user['bio'] as String? ?? '';
          _userCity = user['city'] as String? ?? '';
          _userJob = user['job'] as String? ?? '';
          _userCompany = user['company'] as String? ?? '';
          _userQuote = user['quote'] as String? ?? '';
          _userBornDate = user['born_date'] as String? ?? '';
          _userPhotoUrl = _resolveUrl(user['photo_url'] as String?);
          _userBannerUrl = _resolveUrl(user['banner_url'] as String?);
          _userCreatedAt = user['created_at'] as String? ?? '';
          _userRole = user['role'] as String? ?? 'member';
          _isLoadingProfile = false;
        });

        // Simpan ke SharedPreferences agar sinkron
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_name', _userName);
        await prefs.setString('user_email', _userEmail);

        // Setelah profil berhasil diambil, ambil postingan pengguna
        await _fetchMyPosts();
      } else {
        setState(() {
          _isLoadingProfile = false;
        });
      }
    } catch (_) {
      setState(() {
        _isLoadingProfile = false;
      });
    }
  }

  Future<void> _fetchMyPosts() async {
    if (_userId == 0) return;
    setState(() {
      _isLoadingMyPosts = true;
      _myPosts = [];
    });
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/posts?user_id=$_userId'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List items = data['data'] as List? ?? [];
        final List<PostModel> fetched = items
            .map((e) => PostModel.fromJson(e as Map<String, dynamic>, baseUrl: _baseUrl))
            .toList();
        setState(() {
          _myPosts = fetched;
          _isLoadingMyPosts = false;
        });
      } else {
        setState(() {
          _myPosts = [];
          _isLoadingMyPosts = false;
        });
      }
    } catch (_) {
      setState(() {
        _myPosts = [];
        _isLoadingMyPosts = false;
      });
    }
  }

  void _onTabChanged(int i) {
    if (i == 1) {
      _fetchMembers();
      _fetchMapUsers();
    } else if (i == 2) {
      _fetchMemories();
    } else if (i == 3) {
      _fetchEvents();
    }
  }

  Future<void> _fetchMembers() async {
    if (_authToken.isEmpty) return;
    setState(() {
      _isLoadingMembers = true;
    });
    try {
      final queryParams = <String, String>{};
      if (_memberSearchCtrl.text.isNotEmpty) {
        queryParams['search'] = _memberSearchCtrl.text.trim();
      }
      if (_memberJobCtrl.text.isNotEmpty) {
        queryParams['job'] = _memberJobCtrl.text.trim();
      }
      if (_memberCityCtrl.text.isNotEmpty) {
        queryParams['city'] = _memberCityCtrl.text.trim();
      }

      final uri = Uri.parse('$_baseUrl/api/users').replace(queryParameters: queryParams);
      final response = await http.get(uri, headers: _authHeaders).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _members = data['data'] as List? ?? [];
          _isLoadingMembers = false;
        });
      } else {
        setState(() => _isLoadingMembers = false);
      }
    } catch (_) {
      setState(() => _isLoadingMembers = false);
    }
  }

  Future<void> _fetchMapUsers() async {
    if (_authToken.isEmpty) return;
    setState(() => _isLoadingMap = true);
    try {
      final response = await http
          .get(Uri.parse('$_baseUrl/api/users/map'), headers: _authHeaders)
          .timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _mapUsers = data['data'] as List? ?? [];
          _isLoadingMap = false;
        });
      } else {
        setState(() => _isLoadingMap = false);
      }
    } catch (_) {
      setState(() => _isLoadingMap = false);
    }
  }

  Future<void> _fetchMemories() async {
    if (_authToken.isEmpty) return;
    setState(() {
      _isLoadingMemories = true;
    });
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/gallery'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _memories = data['data'] as List? ?? [];
          _isLoadingMemories = false;
        });
      } else {
        setState(() => _isLoadingMemories = false);
      }
    } catch (_) {
      setState(() => _isLoadingMemories = false);
    }
  }

  Future<void> _fetchEvents() async {
    if (_authToken.isEmpty) return;
    setState(() {
      _isLoadingEvents = true;
    });
    try {
      final urlSuffix = _isPastSelected ? '/api/events?past=true' : '/api/events?upcoming=true';
      final response = await http.get(
        Uri.parse('$_baseUrl$urlSuffix'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _events = data['data'] as List? ?? [];
          _isLoadingEvents = false;
        });
      } else {
        setState(() => _isLoadingEvents = false);
      }
    } catch (_) {
      setState(() => _isLoadingEvents = false);
    }
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
            items.map((e) => PostModel.fromJson(e as Map<String, dynamic>, baseUrl: _baseUrl)).toList();
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

  Future<void> _toggleBookmark(int postId) async {
    final idx = _posts.indexWhere((p) => p.id == postId);
    if (idx == -1) return;

    final post = _posts[idx];
    // Optimistic update
    setState(() {
      _posts[idx] = post.copyWith(
        isBookmarked: !post.isBookmarked,
      );
    });

    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/api/posts/$postId/bookmark'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));

      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception('Gagal memperbarui bookmark.');
      }
    } catch (_) {
      // Revert on error
      setState(() {
        _posts[idx] = post;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Gagal memperbarui bookmark.',
              style: GoogleFonts.spaceGrotesk(color: Colors.white),
            ),
            backgroundColor: Colors.red.shade800,
          ),
        );
      }
    }
  }

  Future<void> _submitPost(String content, String category, {XFile? photo, List<String>? pollOptions}) async {
    if (content.trim().isEmpty) return;
    setState(() => _isPosting = true);

    try {
      http.Response response;

      if (photo != null) {
        // Multipart request untuk post dengan foto
        final request = http.MultipartRequest(
          'POST',
          Uri.parse('$_baseUrl/api/posts'),
        );
        request.headers.addAll({
          'Accept': 'application/json',
          'Authorization': 'Bearer $_authToken',
        });
        request.fields['content'] = content.trim();
        request.fields['category'] = category;
        request.files.add(await http.MultipartFile.fromPath(
          'photo',
          photo.path,
          filename: photo.name,
        ));
        final streamed = await request.send().timeout(const Duration(seconds: 30));
        response = await http.Response.fromStream(streamed);
      } else {
        // JSON request biasa (bisa dengan poll_options)
        final bodyMap = <String, dynamic>{
          'content': content.trim(),
          'category': category,
        };
        if (pollOptions != null && pollOptions.isNotEmpty) {
          bodyMap['poll_options'] = pollOptions;
          bodyMap['poll_duration_days'] = 1;
        }

        response = await http
            .post(
              Uri.parse('$_baseUrl/api/posts'),
              headers: _authHeaders,
              body: jsonEncode(bodyMap),
            )
            .timeout(const Duration(seconds: 12));
      }

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
                  _buildExploreTab(textColor, accentColor, cardBg, mutedColor),
                  _buildGalleryTab(textColor, accentColor, cardBg, mutedColor),
                  _buildEventsTab(textColor, accentColor, cardBg, mutedColor),
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
          // Admin button (only for admin role)
          if (_userRole == 'admin') ...[
            GestureDetector(
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => AdminScreen(
                    isDark: widget.isDark,
                    authToken: _authToken,
                    baseUrl: _baseUrl,
                  ),
                ),
              ),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: accentColor,
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
            ),
            const SizedBox(width: 10),
          ],

          // Notification
          GestureDetector(
            onTap: () async {
              HapticFeedback.lightImpact();
              final updatedUnread = await Navigator.push<int>(
                context,
                MaterialPageRoute(
                  builder: (_) => NotificationsScreen(
                    authToken: _authToken,
                    baseUrl: _baseUrl,
                    isDark: widget.isDark,
                  ),
                ),
              );
              if (updatedUnread != null) {
                setState(() {
                  _unreadNotificationCount = updatedUnread;
                });
              } else {
                _fetchUnreadNotificationCount();
              }
            },
            child: Padding(
              padding: const EdgeInsets.all(4.0),
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Icon(Icons.notifications_outlined, color: textColor, size: 24),
                  if (_unreadNotificationCount > 0)
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
            ),
          ),
        ],
      ),
    );
  }

  // ── FEED TAB ──────────────────────────────────────────
  Widget _buildFeedTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return RefreshIndicator(
      color: accentColor,
      onRefresh: () async {
        await _fetchPosts(reset: true);
        await _fetchUnreadNotificationCount();
      },
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
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg, autoPickImage: true),
              ),
              _buildQuickAction(
                icon: Icons.emoji_emotions_outlined,
                label: 'Emoji',
                color: const Color(0xFFF59E0B),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg, autoOpenEmoji: true),
              ),
              _buildQuickAction(
                icon: Icons.calendar_today_outlined,
                label: 'Event',
                color: const Color(0xFF10B981),
                onTap: () => _showCreateEventSheet(textColor, accentColor, cardBg),
              ),
              _buildQuickAction(
                icon: Icons.poll_outlined,
                label: 'Polling',
                color: const Color(0xFF8B5CF6),
                onTap: () => _showComposeSheet(textColor, accentColor, cardBg, openPollMode: true),
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

  void _showComposeSheet(
    Color textColor,
    Color accentColor,
    Color cardBg, {
    bool autoPickImage = false,
    bool autoOpenEmoji = false,
    bool openPollMode = false,
  }) {
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
        autoPickImage: autoPickImage,
        autoOpenEmoji: autoOpenEmoji,
        openPollMode: openPollMode,
        onSubmit: (content, category, {XFile? photo, List<String>? pollOptions}) =>
            _submitPost(content, category, photo: photo, pollOptions: pollOptions),
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
                // Show options button only for own posts
                if (post.authorId == _userId)
                  GestureDetector(
                    onTap: () => _showPostOptions(
                      post, textColor, accentColor, cardBg, mutedColor,
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(4),
                      child: Icon(Icons.more_horiz, color: mutedColor, size: 22),
                    ),
                  )
                else
                  const SizedBox(width: 30),
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

          // Image/Video (if any)
          if (post.photoUrl != null) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(0)),
              child: (post.photoUrl!.toLowerCase().endsWith('.mp4') ||
                      post.photoUrl!.toLowerCase().endsWith('.mov') ||
                      post.photoUrl!.toLowerCase().endsWith('.avi') ||
                      post.photoUrl!.toLowerCase().endsWith('.webm') ||
                      post.photoUrl!.toLowerCase().endsWith('.mkv') ||
                      post.photoUrl!.toLowerCase().endsWith('.3gp'))
                  ? Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: PostVideoPlayer(
                        videoUrl: post.photoUrl!,
                        isDark: widget.isDark,
                      ),
                    )
                  : Image.network(
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

          // Poll (if any)
          if (post.poll != null) ...[
            const SizedBox(height: 12),
            _buildPollWidget(post, post.poll!, accentColor, textColor, mutedColor, cardBg),
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
                const Spacer(),
                _buildActionButton(
                  icon: post.isBookmarked ? Icons.bookmark_rounded : Icons.bookmark_border_rounded,
                  label: '',
                  color: post.isBookmarked ? accentColor : mutedColor,
                  onTap: () => _toggleBookmark(post.id),
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

  // ── POLL WIDGET ──────────────────────────────────────
  Widget _buildPollWidget(PostModel post, PollModel poll, Color accent,
      Color textColor, Color mutedColor, Color cardBg) {
    final hasVoted = poll.userVotedOptionId != null;
    final showResults = hasVoted || poll.isExpired;

    // Use accent-like blue similar to web's blue border style
    final pillBorder = widget.isDark
        ? const Color(0xFF3B82F6) // blue-500 like web
        : const Color(0xFF2563EB);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ...poll.options.map((opt) {
            final isVoted = opt.id == poll.userVotedOptionId;
            final pct = opt.percent / 100.0;

            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: GestureDetector(
                onTap: showResults ? null : () {
                  HapticFeedback.mediumImpact();
                  _votePoll(post, poll.id, opt.id);
                },
                child: Container(
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: widget.isDark ? Colors.black12 : Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: showResults
                          ? (isVoted ? pillBorder : (widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200))
                          : (widget.isDark ? Colors.grey.shade800 : Colors.grey.shade300),
                      width: isVoted ? 1.8 : 1.2,
                    ),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10.5),
                    child: Stack(
                      children: [
                        // Progress fill (only when voted/expired)
                        if (showResults)
                          Positioned.fill(
                            child: FractionallySizedBox(
                              widthFactor: pct,
                              alignment: Alignment.centerLeft,
                              child: Container(
                                decoration: BoxDecoration(
                                  color: isVoted
                                      ? pillBorder.withValues(alpha: 0.2)
                                      : (widget.isDark
                                          ? Colors.grey.shade800.withValues(alpha: 0.3)
                                          : Colors.grey.shade200.withValues(alpha: 0.5)),
                                  borderRadius: BorderRadius.circular(10.5),
                                ),
                              ),
                            ),
                          ),
                        // Text content
                        Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 14),
                          child: Row(
                            children: [
                              if (isVoted) ...[
                                Icon(Icons.check_circle_rounded,
                                    color: pillBorder, size: 16),
                                const SizedBox(width: 8),
                              ],
                              Expanded(
                                child: Text(
                                  opt.text,
                                  style: GoogleFonts.spaceGrotesk(
                                    fontSize: 14,
                                    fontWeight: isVoted
                                        ? FontWeight.w700
                                        : FontWeight.w500,
                                    color: isVoted ? pillBorder : textColor,
                                  ),
                                ),
                              ),
                              if (showResults) ...[
                                const SizedBox(width: 8),
                                Text(
                                  '${opt.percent}%',
                                  style: GoogleFonts.spaceGrotesk(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    color: isVoted ? pillBorder : textColor,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          }),
          // Footer: vote count + expiry
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${poll.totalVotes} suara • ${poll.isExpired ? "Selesai" : "Aktif"}',
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 12,
                  color: mutedColor,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }

  Future<void> _votePoll(PostModel post, int pollId, int optionId) async {
    final postIdx = _posts.indexWhere((p) => p.id == post.id);
    if (postIdx == -1 || post.poll == null) return;

    // Optimistic update
    final newTotal = post.poll!.totalVotes + 1;
    final updatedOpts = post.poll!.options.map((opt) => PollOptionModel(
      id: opt.id,
      text: opt.text,
      votesCount: opt.id == optionId ? opt.votesCount + 1 : opt.votesCount,
      percent: newTotal > 0
          ? (((opt.id == optionId ? opt.votesCount + 1 : opt.votesCount) / newTotal) * 100).round()
          : 0,
    )).toList();

    setState(() {
      _posts[postIdx] = post.copyWith(
        poll: post.poll!.copyWith(
          userVotedOptionId: optionId,
          totalVotes: newTotal,
          options: updatedOpts,
        ),
      );
    });

    try {
      final res = await http
          .post(
            Uri.parse('$_baseUrl/api/posts/${post.id}/poll/$pollId/vote'),
            headers: _authHeaders,
            body: jsonEncode({'option_id': optionId}),
          )
          .timeout(const Duration(seconds: 10));

      if (res.statusCode != 200) {
        setState(() => _posts[postIdx] = post);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text('Gagal memilih opsi',
                style: GoogleFonts.spaceGrotesk(color: Colors.white)),
            backgroundColor: Colors.red.shade700,
            behavior: SnackBarBehavior.floating,
          ));
        }
      }
    } catch (_) {
      setState(() => _posts[postIdx] = post);
    }
  }

  // ── COMMENTS SHEET ────────────────────────────────────
  void _showCommentsSheet(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => CommentsSheet(
        isDark: widget.isDark,
        commentsPath: '/api/posts/${post.id}/comments',
        initialCommentsCount: post.commentsCount,
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

  // ── POST OPTIONS MENU ─────────────────────────────────
  void _showPostOptions(
    PostModel post,
    Color textColor,
    Color accentColor,
    Color cardBg,
    Color mutedColor,
  ) {
    HapticFeedback.selectionClick();
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          border: const Border(
            top: BorderSide(color: AppColors.dark, width: 2),
          ),
        ),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Handle bar
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade400,
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            const SizedBox(height: 20),
            // Edit option
            _buildOptionTile(
              icon: Icons.edit_rounded,
              label: 'Edit Post',
              color: textColor,
              cardBg: cardBg,
              onTap: () {
                Navigator.pop(ctx);
                _showEditPostSheet(post, textColor, accentColor, cardBg, mutedColor);
              },
            ),
            const SizedBox(height: 12),
            // Delete option
            _buildOptionTile(
              icon: Icons.delete_outline_rounded,
              label: 'Hapus Post',
              color: Colors.red.shade600,
              cardBg: widget.isDark
                  ? Colors.red.shade900.withValues(alpha: 0.3)
                  : Colors.red.shade50,
              onTap: () {
                Navigator.pop(ctx);
                _confirmDeletePost(post, textColor, accentColor, cardBg);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOptionTile({
    required IconData icon,
    required String label,
    required Color color,
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
            Icon(icon, color: color, size: 22),
            const SizedBox(width: 16),
            Text(
              label,
              style: GoogleFonts.spaceGrotesk(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: color,
              ),
            ),
            const Spacer(),
            Icon(
              Icons.chevron_right_rounded,
              color: color.withValues(alpha: 0.5),
              size: 20,
            ),
          ],
        ),
      ),
    );
  }

  // ── EDIT POST SHEET ───────────────────────────────────
  void _showEditPostSheet(
    PostModel post,
    Color textColor,
    Color accentColor,
    Color cardBg,
    Color mutedColor,
  ) {
    final contentCtrl = TextEditingController(text: post.content);
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          Future<void> save() async {
            final newContent = contentCtrl.text.trim();
            if (newContent.isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    'Konten post tidak boleh kosong.',
                    style: GoogleFonts.spaceGrotesk(color: Colors.white),
                  ),
                  backgroundColor: Colors.red.shade700,
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              );
              return;
            }

            // Capture navigator & messenger before async gap
            final nav = Navigator.of(ctx);
            final messenger = ScaffoldMessenger.of(context);

            setModalState(() => isSaving = true);
            try {
              final res = await http
                  .put(
                    Uri.parse('$_baseUrl/api/posts/${post.id}'),
                    headers: _authHeaders,
                    body: jsonEncode({'content': newContent}),
                  )
                  .timeout(const Duration(seconds: 12));

              if (res.statusCode == 200 && mounted) {
                // Update post in local list optimistically
                final idx = _posts.indexWhere((p) => p.id == post.id);
                if (idx != -1) {
                  setState(() {
                    _posts[idx] = _posts[idx].copyWith(content: newContent);
                  });
                }
                // Also update in myPosts
                final myIdx = _myPosts.indexWhere((p) => p.id == post.id);
                if (myIdx != -1) {
                  setState(() {
                    _myPosts[myIdx] = _myPosts[myIdx].copyWith(content: newContent);
                  });
                }

                nav.pop();
                HapticFeedback.mediumImpact();
                messenger.showSnackBar(
                  SnackBar(
                    backgroundColor:
                        widget.isDark ? AppColors.orange : AppColors.lime,
                    content: Text(
                      '✅ Post berhasil diperbarui!',
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
              } else {
                setModalState(() => isSaving = false);
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                      'Gagal memperbarui post.',
                      style: GoogleFonts.spaceGrotesk(color: Colors.white),
                    ),
                    backgroundColor: Colors.red.shade700,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            } catch (_) {
              setModalState(() => isSaving = false);
              messenger.showSnackBar(
                SnackBar(
                  content: Text(
                    'Tidak dapat terhubung ke server.',
                    style: GoogleFonts.spaceGrotesk(color: Colors.white),
                  ),
                  backgroundColor: Colors.red.shade700,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            }
          }

          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(context).viewInsets.bottom,
            ),
            child: Container(
              decoration: BoxDecoration(
                color: cardBg,
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(24)),
                border: const Border(
                    top: BorderSide(color: AppColors.dark, width: 2)),
              ),
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Handle bar
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade400,
                      borderRadius: BorderRadius.circular(99),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Title row
                  Row(
                    children: [
                      Icon(Icons.edit_rounded, color: accentColor, size: 20),
                      const SizedBox(width: 10),
                      Text(
                        'Edit Post',
                        style: GoogleFonts.spaceGrotesk(
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                          color: textColor,
                        ),
                      ),
                      const Spacer(),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx),
                        icon: Icon(Icons.close_rounded,
                            color: mutedColor, size: 22),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Text field
                  TextField(
                    controller: contentCtrl,
                    maxLines: 6,
                    minLines: 3,
                    autofocus: true,
                    style: GoogleFonts.spaceGrotesk(
                      color: textColor,
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      height: 1.55,
                    ),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: widget.isDark
                          ? const Color(0xFF1A1B24)
                          : AppColors.light,
                      hintText: 'Apa yang sedang terjadi?',
                      hintStyle: GoogleFonts.spaceGrotesk(
                        color: Colors.grey.shade400,
                        fontWeight: FontWeight.w500,
                      ),
                      contentPadding: const EdgeInsets.all(16),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide:
                            const BorderSide(color: AppColors.dark, width: 2),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide(
                          color: widget.isDark
                              ? Colors.grey.shade700
                              : Colors.grey.shade300,
                          width: 1.5,
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide:
                            const BorderSide(color: AppColors.dark, width: 2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Save button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isSaving ? null : save,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: accentColor,
                        foregroundColor: AppColors.dark,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                          side:
                              const BorderSide(color: AppColors.dark, width: 2),
                        ),
                        elevation: 0,
                      ),
                      child: isSaving
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                color: AppColors.dark,
                                strokeWidth: 2,
                              ),
                            )
                          : Text(
                              'Simpan Perubahan',
                              style: GoogleFonts.spaceGrotesk(
                                fontWeight: FontWeight.w800,
                                fontSize: 15,
                                color: AppColors.dark,
                              ),
                            ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // ── CONFIRM DELETE POST ───────────────────────────────
  void _confirmDeletePost(
    PostModel post,
    Color textColor,
    Color accentColor,
    Color cardBg,
  ) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: cardBg,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: AppColors.dark, width: 2),
        ),
        title: Row(
          children: [
            Icon(Icons.delete_outline_rounded,
                color: Colors.red.shade600, size: 22),
            const SizedBox(width: 10),
            Text(
              'Hapus Post?',
              style: GoogleFonts.spaceGrotesk(
                fontWeight: FontWeight.w800,
                color: textColor,
              ),
            ),
          ],
        ),
        content: Text(
          'Post ini akan dihapus secara permanen dan tidak dapat dikembalikan.',
          style: GoogleFonts.spaceGrotesk(
            color: widget.isDark ? Colors.grey.shade300 : Colors.grey.shade700,
            fontWeight: FontWeight.w500,
            height: 1.5,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(
              'Batal',
              style: GoogleFonts.spaceGrotesk(
                color: widget.isDark
                    ? Colors.grey.shade400
                    : Colors.grey.shade600,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade600,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
              padding:
                  const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              elevation: 0,
            ),
            onPressed: () {
              Navigator.pop(ctx);
              _deletePost(post);
            },
            child: Text(
              'Hapus',
              style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }

  // ── DELETE POST API ───────────────────────────────────
  Future<void> _deletePost(PostModel post) async {
    // Optimistic removal
    final idx = _posts.indexWhere((p) => p.id == post.id);
    final myIdx = _myPosts.indexWhere((p) => p.id == post.id);

    setState(() {
      if (idx != -1) _posts.removeAt(idx);
      if (myIdx != -1) _myPosts.removeAt(myIdx);
    });

    try {
      final res = await http
          .delete(
            Uri.parse('$_baseUrl/api/posts/${post.id}'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 12));

      if (res.statusCode == 200 || res.statusCode == 204) {
        HapticFeedback.mediumImpact();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                '🗑️ Post berhasil dihapus.',
                style: GoogleFonts.spaceGrotesk(
                    fontWeight: FontWeight.bold, color: AppColors.dark),
              ),
              backgroundColor:
                  widget.isDark ? AppColors.orange : AppColors.lime,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: const BorderSide(color: AppColors.dark, width: 2),
              ),
            ),
          );
        }
      } else {
        // Revert if failed
        setState(() {
          if (idx != -1) _posts.insert(idx, post);
          if (myIdx != -1) _myPosts.insert(myIdx, post);
        });
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                'Gagal menghapus post.',
                style: GoogleFonts.spaceGrotesk(color: Colors.white),
              ),
              backgroundColor: Colors.red.shade700,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    } catch (_) {
      // Revert on error
      setState(() {
        if (idx != -1) _posts.insert(idx, post);
        if (myIdx != -1) _myPosts.insert(myIdx, post);
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Tidak dapat terhubung ke server.',
              style: GoogleFonts.spaceGrotesk(color: Colors.white),
            ),
            backgroundColor: Colors.red.shade700,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
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
    if (_isLoadingProfile && _userId == 0) {
      return Center(
        child: CircularProgressIndicator(color: accentColor),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Banner & Avatar Section
          Stack(
            clipBehavior: Clip.none,
            children: [
              // Banner
              Container(
                height: 160,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: widget.isDark ? AppColors.cardDark : Colors.grey.shade200,
                  border: const Border(
                    bottom: BorderSide(color: AppColors.dark, width: 2),
                  ),
                  image: _userBannerUrl != null
                      ? DecorationImage(
                          image: NetworkImage(_userBannerUrl!),
                          fit: BoxFit.cover,
                        )
                      : const DecorationImage(
                          image: NetworkImage("https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2000"),
                          fit: BoxFit.cover,
                        ),
                ),
              ),
              // Overlapping Avatar
              Positioned(
                left: 20,
                bottom: -45,
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(
                    color: AppColors.dark,
                    shape: BoxShape.circle,
                  ),
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: widget.isDark ? AppColors.cardDark : Colors.white,
                        width: 3,
                      ),
                      shape: BoxShape.circle,
                    ),
                    child: _buildAvatar(_userPhotoUrl, _userName, 90),
                  ),
                ),
              ),
            ],
          ),

          // Action Buttons (Edit Profile & Theme toggle)
          Padding(
            padding: const EdgeInsets.only(right: 16, top: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                // Theme Toggle
                GestureDetector(
                  onTap: widget.onToggleTheme,
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: cardBg,
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.dark, width: 2),
                    ),
                    child: Icon(
                      widget.isDark ? Icons.wb_sunny_rounded : Icons.dark_mode_rounded,
                      color: textColor,
                      size: 20,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                // Edit Profile Button
                GestureDetector(
                  onTap: _showEditProfileSheet,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    decoration: BoxDecoration(
                      color: accentColor,
                      borderRadius: BorderRadius.circular(99),
                      border: Border.all(color: AppColors.dark, width: 2),
                      boxShadow: const [
                        BoxShadow(
                          color: AppColors.dark,
                          offset: Offset(3, 3),
                          blurRadius: 0,
                        ),
                      ],
                    ),
                    child: Text(
                      'Edit Profil',
                      style: GoogleFonts.spaceGrotesk(
                        color: AppColors.dark,
                        fontWeight: FontWeight.w800,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // User details info card
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _userName,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                  ),
                ),
                if (_userNickname.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    '@${_userNickname.toLowerCase().replaceAll(' ', '')}',
                    style: GoogleFonts.spaceGrotesk(
                      fontSize: 14,
                      color: mutedColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
                const SizedBox(height: 12),
                // Bio
                Text(
                  _userBio.isNotEmpty ? _userBio : 'Belum ada bio.',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 14,
                    color: textColor,
                    height: 1.5,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 16),
                // Extra metadata rows (wrap)
                Wrap(
                  spacing: 16,
                  runSpacing: 10,
                  children: [
                    if (_userCity.isNotEmpty)
                      _buildInfoChip(Icons.location_on_outlined, _userCity, textColor, mutedColor),
                    if (_userJob.isNotEmpty)
                      _buildInfoChip(
                        Icons.work_outline_rounded,
                        '$_userJob${_userCompany.isNotEmpty ? " @ $_userCompany" : ""}',
                        textColor,
                        mutedColor,
                      ),
                    if (_userBornDate.isNotEmpty)
                      _buildInfoChip(Icons.cake_outlined, 'Lahir: $_userBornDate', textColor, mutedColor),
                    if (_userCreatedAt.isNotEmpty)
                      _buildInfoChip(Icons.calendar_today_outlined, 'Bergabung: $_userCreatedAt', textColor, mutedColor),
                  ],
                ),

                if (_userQuote.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  // Quote Box
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: accentColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.dark, width: 2),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.format_quote_rounded, color: accentColor, size: 28),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            '"$_userQuote"',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              fontStyle: FontStyle.italic,
                              color: textColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),

          const SizedBox(height: 32),

          // Posting Saya Feed Section
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                Icon(Icons.grid_on_rounded, color: textColor, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Posting Saya',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // User specific posts feed
          if (_isLoadingMyPosts)
            _buildSkeletonList(cardBg)
          else if (_myPosts.isEmpty)
            _buildEmptyMyPosts(textColor, mutedColor, accentColor)
          else
            Column(
              children: _myPosts.map((post) => _buildPostCard(post, textColor, accentColor, cardBg, mutedColor)).toList(),
            ),

          const SizedBox(height: 32),

          // Settings options and Logout section
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              children: [
                _buildProfileOption(
                  icon: Icons.notifications_outlined,
                  label: 'Notifikasi',
                  textColor: textColor,
                  accentColor: accentColor,
                  cardBg: cardBg,
                  onTap: () async {
                    HapticFeedback.lightImpact();
                    final updatedUnread = await Navigator.push<int>(
                      context,
                      MaterialPageRoute(
                        builder: (_) => NotificationsScreen(
                          authToken: _authToken,
                          baseUrl: _baseUrl,
                          isDark: widget.isDark,
                        ),
                      ),
                    );
                    if (updatedUnread != null) {
                      setState(() {
                        _unreadNotificationCount = updatedUnread;
                      });
                    } else {
                      _fetchUnreadNotificationCount();
                    }
                  },
                ),
                const SizedBox(height: 12),
                _buildProfileOption(
                  icon: Icons.bookmark_border_rounded,
                  label: 'Bookmark Tersimpan',
                  textColor: textColor,
                  accentColor: accentColor,
                  cardBg: cardBg,
                  onTap: () {
                    HapticFeedback.lightImpact();
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => BookmarksScreen(
                          authToken: _authToken,
                          baseUrl: _baseUrl,
                          isDark: widget.isDark,
                          onToggleTheme: widget.onToggleTheme,
                        ),
                      ),
                    ).then((_) {
                      // Refresh posts on return in case bookmark status changed
                      _fetchPosts();
                    });
                  },
                ),
                const SizedBox(height: 12),
                _buildProfileOption(
                  icon: Icons.help_outline_rounded,
                  label: 'Bantuan & Dukungan',
                  textColor: textColor,
                  accentColor: accentColor,
                  cardBg: cardBg,
                  onTap: () {
                    HapticFeedback.lightImpact();
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Halaman Bantuan segera hadir!', style: GoogleFonts.spaceGrotesk()),
                        backgroundColor: AppColors.dark,
                      ),
                    );
                  },
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
                const SizedBox(height: 24),
                Text(
                  '© 2026 The Archive (Memora)',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 12,
                    color: mutedColor,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoChip(IconData icon, String label, Color textColor, Color mutedColor) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: mutedColor, size: 16),
        const SizedBox(width: 6),
        Text(
          label,
          style: GoogleFonts.spaceGrotesk(
            fontSize: 13,
            color: textColor.withValues(alpha: 0.8),
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyMyPosts(Color textColor, Color mutedColor, Color accentColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 20),
      child: Center(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: widget.isDark ? AppColors.cardDark : Colors.white,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.dark, width: 2),
              ),
              child: Icon(Icons.notes_rounded, size: 36, color: accentColor),
            ),
            const SizedBox(height: 16),
            Text(
              'Belum ada postingan',
              style: GoogleFonts.spaceGrotesk(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: textColor,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Cerita yang Anda bagikan akan muncul di sini.',
              textAlign: TextAlign.center,
              style: GoogleFonts.spaceGrotesk(
                fontSize: 13,
                color: mutedColor,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
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
                  _onTabChanged(i);
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

    final resolvedUrl = _resolveUrl(photoUrl);
    if (resolvedUrl != null && resolvedUrl.isNotEmpty) {
      return ClipOval(
        child: Image.network(
          resolvedUrl,
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

  void _showEditProfileSheet() {
    final nameCtrl = TextEditingController(text: _userName);
    final nicknameCtrl = TextEditingController(text: _userNickname);
    final bioCtrl = TextEditingController(text: _userBio);
    final cityCtrl = TextEditingController(text: _userCity);
    final jobCtrl = TextEditingController(text: _userJob);
    final companyCtrl = TextEditingController(text: _userCompany);
    final quoteCtrl = TextEditingController(text: _userQuote);
    String bornDate = _userBornDate;

    XFile? pickedAvatar;
    XFile? pickedBanner;
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
        final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
        final textColor = widget.isDark ? Colors.white : AppColors.dark;
        final mutedColor = Colors.grey.shade500;

        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> pickAvatarImage() async {
              final picker = ImagePicker();
              final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
              if (picked != null) {
                setModalState(() => pickedAvatar = picked);
              }
            }

            Future<void> pickBannerImage() async {
              final picker = ImagePicker();
              final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
              if (picked != null) {
                setModalState(() => pickedBanner = picked);
              }
            }

            Future<void> selectBornDate() async {
              DateTime initial = DateTime.tryParse(bornDate) ?? DateTime(2000, 1, 1);
              final pickedDate = await showDatePicker(
                context: context,
                initialDate: initial,
                firstDate: DateTime(1950),
                lastDate: DateTime.now(),
                builder: (context, child) {
                  return Theme(
                    data: Theme.of(context).copyWith(
                      colorScheme: widget.isDark
                          ? const ColorScheme.dark(primary: AppColors.orange, surface: AppColors.cardDark)
                          : const ColorScheme.light(primary: AppColors.lime, surface: Colors.white),
                    ),
                    child: child!,
                  );
                },
              );
              if (pickedDate != null) {
                setModalState(() {
                  bornDate = "${pickedDate.year}-${pickedDate.month.toString().padLeft(2, '0')}-${pickedDate.day.toString().padLeft(2, '0')}";
                });
              }
            }

            Future<void> saveProfile() async {
              if (nameCtrl.text.trim().isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Nama tidak boleh kosong', style: GoogleFonts.spaceGrotesk(color: Colors.white)),
                    backgroundColor: Colors.redAccent,
                  ),
                );
                return;
              }

              final navigator = Navigator.of(ctx);
              final messenger = ScaffoldMessenger.of(context);

              setModalState(() => isSaving = true);

              try {
                // 1. Update text info
                final textResponse = await http.put(
                  Uri.parse('$_baseUrl/api/users/$_userId'),
                  headers: _authHeaders,
                  body: jsonEncode({
                    'name': nameCtrl.text.trim(),
                    'nickname': nicknameCtrl.text.trim(),
                    'bio': bioCtrl.text.trim(),
                    'city': cityCtrl.text.trim(),
                    'job': jobCtrl.text.trim(),
                    'company': companyCtrl.text.trim(),
                    'quote': quoteCtrl.text.trim(),
                    'born_date': bornDate.isNotEmpty ? bornDate : null,
                  }),
                ).timeout(const Duration(seconds: 10));

                if (textResponse.statusCode != 200) {
                  String errMsg = 'Gagal memperbarui info profil';
                  try {
                    final Map<String, dynamic> errorData = jsonDecode(textResponse.body);
                    if (errorData.containsKey('errors') && errorData['errors'] is Map) {
                      final errors = errorData['errors'] as Map<String, dynamic>;
                      final firstErrorList = errors.values.first;
                      if (firstErrorList is List && firstErrorList.isNotEmpty) {
                        errMsg = firstErrorList.first.toString();
                      } else {
                        errMsg = errorData['message'] ?? errMsg;
                      }
                    } else {
                      errMsg = errorData['message'] ?? errMsg;
                    }
                  } catch (_) {}
                  throw Exception(errMsg);
                }

                // 2. Upload photo if selected
                if (pickedAvatar != null) {
                  final request = http.MultipartRequest(
                    'POST',
                    Uri.parse('$_baseUrl/api/users/$_userId/photo'),
                  );
                  request.headers.addAll({
                    'Authorization': 'Bearer $_authToken',
                    'Accept': 'application/json',
                  });
                  request.files.add(
                    await http.MultipartFile.fromPath('photo', pickedAvatar!.path),
                  );
                  final response = await request.send().timeout(const Duration(seconds: 15));
                  if (response.statusCode != 200) {
                    final respStr = await response.stream.bytesToString();
                    String errMsg = 'Gagal mengunggah foto profil';
                    try {
                      final Map<String, dynamic> errorData = jsonDecode(respStr);
                      if (errorData.containsKey('errors') && errorData['errors'] is Map) {
                        final errors = errorData['errors'] as Map<String, dynamic>;
                        final firstErrorList = errors.values.first;
                        if (firstErrorList is List && firstErrorList.isNotEmpty) {
                          errMsg = firstErrorList.first.toString();
                        } else {
                          errMsg = errorData['message'] ?? errMsg;
                        }
                      } else {
                        errMsg = errorData['message'] ?? errMsg;
                      }
                    } catch (_) {}
                    throw Exception(errMsg);
                  }
                }

                // 3. Upload banner if selected
                if (pickedBanner != null) {
                  final request = http.MultipartRequest(
                    'POST',
                    Uri.parse('$_baseUrl/api/users/$_userId/banner-photo'),
                  );
                  request.headers.addAll({
                    'Authorization': 'Bearer $_authToken',
                    'Accept': 'application/json',
                  });
                  request.files.add(
                    await http.MultipartFile.fromPath('banner_photo', pickedBanner!.path),
                  );
                  final response = await request.send().timeout(const Duration(seconds: 15));
                  if (response.statusCode != 200) {
                    final respStr = await response.stream.bytesToString();
                    String errMsg = 'Gagal mengunggah foto banner';
                    try {
                      final Map<String, dynamic> errorData = jsonDecode(respStr);
                      if (errorData.containsKey('errors') && errorData['errors'] is Map) {
                        final errors = errorData['errors'] as Map<String, dynamic>;
                        final firstErrorList = errors.values.first;
                        if (firstErrorList is List && firstErrorList.isNotEmpty) {
                          errMsg = firstErrorList.first.toString();
                        } else {
                          errMsg = errorData['message'] ?? errMsg;
                        }
                      } else {
                        errMsg = errorData['message'] ?? errMsg;
                      }
                    } catch (_) {}
                    throw Exception(errMsg);
                  }
                }

                // Refresh profile
                await _fetchUserProfile();

                navigator.pop();
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                      'Profil berhasil diperbarui!',
                      style: GoogleFonts.spaceGrotesk(color: AppColors.dark, fontWeight: FontWeight.bold),
                    ),
                    backgroundColor: widget.isDark ? AppColors.orange : AppColors.lime,
                    behavior: SnackBarBehavior.floating,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                );
              } catch (e) {
                setModalState(() => isSaving = false);
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                      'Terjadi kesalahan: ${e.toString().replaceAll('Exception: ', '')}',
                      style: GoogleFonts.spaceGrotesk(color: Colors.white),
                    ),
                    backgroundColor: Colors.redAccent,
                  ),
                );
              }
            }

            return Container(
              height: MediaQuery.of(context).size.height * 0.85,
              decoration: BoxDecoration(
                color: cardBg,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                border: const Border(top: BorderSide(color: AppColors.dark, width: 2)),
              ),
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                child: Scaffold(
                  backgroundColor: cardBg,
                  appBar: AppBar(
                    backgroundColor: cardBg,
                    elevation: 0,
                    title: Text(
                      'Edit Profil',
                      style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor),
                    ),
                    leading: IconButton(
                      icon: Icon(Icons.close_rounded, color: textColor),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                    actions: [
                      if (isSaving)
                        const Center(
                          child: Padding(
                            padding: EdgeInsets.only(right: 16.0),
                            child: SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.dark),
                            ),
                          ),
                        )
                      else
                        TextButton(
                          onPressed: saveProfile,
                          child: Text(
                            'Simpan',
                            style: GoogleFonts.spaceGrotesk(
                              color: accentColor,
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                            ),
                          ),
                        ),
                    ],
                  ),
                  body: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Edit Banner
                        Text(
                          'Foto Banner',
                          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor, fontSize: 14),
                        ),
                        const SizedBox(height: 8),
                        GestureDetector(
                          onTap: pickBannerImage,
                          child: Container(
                            height: 120,
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: widget.isDark ? Colors.grey.shade900 : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.dark, width: 2),
                              image: pickedBanner != null
                                  ? DecorationImage(
                                      image: FileImage(File(pickedBanner!.path)),
                                      fit: BoxFit.cover,
                                    )
                                  : (_userBannerUrl != null
                                      ? DecorationImage(
                                          image: NetworkImage(_userBannerUrl!),
                                          fit: BoxFit.cover,
                                        )
                                      : null),
                            ),
                            child: Center(
                              child: Container(
                                padding: const EdgeInsets.all(8),
                                decoration: const BoxDecoration(
                                  color: Colors.black54,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.camera_alt_outlined, color: Colors.white, size: 24),
                              ),
                            ),
                          ),
                        ),

                        const SizedBox(height: 20),

                        // Edit Avatar
                        Text(
                          'Foto Profil',
                          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor, fontSize: 14),
                        ),
                        const SizedBox(height: 8),
                        Center(
                          child: GestureDetector(
                            onTap: pickAvatarImage,
                            child: Stack(
                              alignment: Alignment.center,
                              children: [
                                Container(
                                  width: 100,
                                  height: 100,
                                  decoration: BoxDecoration(
                                    color: widget.isDark ? Colors.grey.shade900 : Colors.grey.shade100,
                                    shape: BoxShape.circle,
                                    border: Border.all(color: AppColors.dark, width: 2),
                                  ),
                                  child: ClipOval(
                                    child: pickedAvatar != null
                                        ? Image.file(
                                            File(pickedAvatar!.path),
                                            fit: BoxFit.cover,
                                          )
                                        : _buildAvatar(_userPhotoUrl, _userName, 96),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: const BoxDecoration(
                                    color: Colors.black54,
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.camera_alt_outlined, color: Colors.white, size: 20),
                                ),
                              ],
                            ),
                          ),
                        ),

                        const SizedBox(height: 24),

                        // Inputs fields
                        _buildEditField('Nama Lengkap', nameCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Username / Nickname', nicknameCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Bio', bioCtrl, textColor, cardBg, maxLines: 3, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Kota Asal', cityCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Pekerjaan', jobCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Perusahaan', companyCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),
                        _buildEditField('Kutipan Favorit (Quote)', quoteCtrl, textColor, cardBg, isDark: widget.isDark),
                        const SizedBox(height: 16),

                        // DatePicker Field
                        Text(
                          'Tanggal Lahir',
                          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor, fontSize: 13),
                        ),
                        const SizedBox(height: 8),
                        GestureDetector(
                          onTap: selectBornDate,
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                            decoration: BoxDecoration(
                              color: widget.isDark ? const Color(0xFF282932) : AppColors.light,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: AppColors.dark, width: 2),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.calendar_month_rounded, color: mutedColor),
                                const SizedBox(width: 12),
                                Text(
                                  bornDate.isNotEmpty ? bornDate : 'Pilih Tanggal Lahir',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: bornDate.isNotEmpty ? textColor : mutedColor,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildEditField(
    String label,
    TextEditingController ctrl,
    Color textColor,
    Color cardBg, {
    int maxLines = 1,
    required bool isDark,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor, fontSize: 13),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: ctrl,
          maxLines: maxLines,
          style: GoogleFonts.spaceGrotesk(color: textColor, fontWeight: FontWeight.w600),
          decoration: InputDecoration(
            filled: true,
            fillColor: isDark ? const Color(0xFF282932) : AppColors.light,
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.dark, width: 2),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.dark, width: 2),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.dark, width: 2),
            ),
          ),
        ),
      ],
    );
  }

  // ── RESOLVE PHOTO URL HELPER ─────────────────────────
  String? _resolvePhotoUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http')) return path;
    return '$_baseUrl$path';
  }

  // ── EXPLORE TAB METHODS ──────────────────────────────
  Widget _buildExploreTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return RefreshIndicator(
      color: accentColor,
      onRefresh: _fetchMembers,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: _buildMapPreview(textColor, accentColor, cardBg, mutedColor),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Main search bar
                  Container(
                    decoration: BoxDecoration(
                      color: cardBg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.dark, width: 2),
                      boxShadow: const [
                        BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
                      ],
                    ),
                    child: TextField(
                      controller: _memberSearchCtrl,
                      style: GoogleFonts.spaceGrotesk(color: textColor, fontWeight: FontWeight.w600),
                      decoration: InputDecoration(
                        hintText: 'Cari nama atau username...',
                        hintStyle: GoogleFonts.spaceGrotesk(color: mutedColor, fontWeight: FontWeight.w500),
                        prefixIcon: Icon(Icons.search_rounded, color: mutedColor),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                      ),
                      onSubmitted: (_) => _fetchMembers(),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Filters label
                  Row(
                    children: [
                      Icon(Icons.tune_rounded, color: textColor, size: 18),
                      const SizedBox(width: 8),
                      Text(
                        'Filter Pencarian',
                        style: GoogleFonts.spaceGrotesk(
                          color: textColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  // Job and City inputs side-by-side
                  Row(
                    children: [
                      Expanded(
                        child: _buildExploreFilterField(
                          'Pekerjaan',
                          _memberJobCtrl,
                          Icons.work_outline_rounded,
                          textColor,
                          cardBg,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildExploreFilterField(
                          'Kota Asal',
                          _memberCityCtrl,
                          Icons.location_on_outlined,
                          textColor,
                          cardBg,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Apply filter button
                  NeoButton(
                    isDark: widget.isDark,
                    backgroundColor: accentColor,
                    textColor: AppColors.dark,
                    onTap: _fetchMembers,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.search_rounded, color: AppColors.dark, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          'Terapkan Filter',
                          style: GoogleFonts.spaceGrotesk(
                            color: AppColors.dark,
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_isLoadingMembers && _members.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.only(top: 80),
                child: Center(child: CircularProgressIndicator(color: accentColor)),
              ),
            )
          else if (_members.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.only(top: 80, left: 32, right: 32),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: cardBg,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.dark, width: 2),
                        boxShadow: const [
                          BoxShadow(color: AppColors.dark, offset: Offset(3, 3), blurRadius: 0),
                        ],
                      ),
                      child: Icon(Icons.people_outline_rounded, size: 40, color: accentColor),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Anggota tidak ditemukan',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Coba sesuaikan kata kunci pencarian atau filter Anda.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 13,
                        color: mutedColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final member = _members[index] as Map<String, dynamic>;
                    return _buildExploreMemberCard(member, textColor, accentColor, cardBg, mutedColor);
                  },
                  childCount: _members.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ── MAP PREVIEW CARD ─────────────────────────────────
  Widget _buildMapPreview(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    final usersWithCoords = _mapUsers
        .where((u) => u['lat'] != null && u['lng'] != null)
        .toList();

    // Build a static map URL using OpenStreetMap + staticmap.openstreetmap.de
    String buildStaticMapUrl() {
      if (usersWithCoords.isEmpty) {
        // Default to Indonesia center
        return 'https://staticmap.openstreetmap.de/staticmap.php?center=-2.5,118&zoom=4&size=700x300&maptype=osm';
      }

      // Calculate center from average lat/lng
      double sumLat = 0, sumLng = 0;
      for (final u in usersWithCoords) {
        sumLat += (u['lat'] as num).toDouble();
        sumLng += (u['lng'] as num).toDouble();
      }
      final centerLat = sumLat / usersWithCoords.length;
      final centerLng = sumLng / usersWithCoords.length;

      // Build marker params (max 20 to keep URL short)
      final markers = usersWithCoords.take(20).map((u) {
        final lat = (u['lat'] as num).toDouble();
        final lng = (u['lng'] as num).toDouble();
        return 'marker=$lat,$lng,red-pushpin';
      }).join('&');

      return 'https://staticmap.openstreetmap.de/staticmap.php'
          '?center=$centerLat,$centerLng'
          '&zoom=5'
          '&size=700x300'
          '&maptype=osm'
          '&$markers';
    }

    final mapUrl = buildStaticMapUrl();
    final mapHeight = _mapExpanded ? 260.0 : 160.0;

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.location_on_rounded, color: accentColor, size: 18),
                ),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Peta Anggota',
                      style: GoogleFonts.spaceGrotesk(
                        color: textColor,
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                      ),
                    ),
                    Text(
                      _isLoadingMap
                          ? 'Memuat...'
                          : '${usersWithCoords.length} anggota dengan lokasi',
                      style: GoogleFonts.spaceGrotesk(
                        color: mutedColor,
                        fontWeight: FontWeight.w500,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                GestureDetector(
                  onTap: () => setState(() => _mapExpanded = !_mapExpanded),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(99),
                    ),
                    child: Row(
                      children: [
                        Text(
                          _mapExpanded ? 'Perkecil' : 'Perluas',
                          style: GoogleFonts.spaceGrotesk(
                            color: mutedColor,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Icon(
                          _mapExpanded ? Icons.expand_less_rounded : Icons.expand_more_rounded,
                          color: mutedColor,
                          size: 16,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Map image
          AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeInOut,
            height: mapHeight,
            child: ClipRRect(
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(20)),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (_isLoadingMap)
                    Container(
                      color: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade100,
                      child: Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            CircularProgressIndicator(color: accentColor, strokeWidth: 2),
                            const SizedBox(height: 8),
                            Text(
                              'Memuat peta...',
                              style: GoogleFonts.spaceGrotesk(
                                color: mutedColor,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    )
                  else
                    Image.network(
                      mapUrl,
                      fit: BoxFit.cover,
                      loadingBuilder: (ctx, child, progress) {
                        if (progress == null) return child;
                        return Container(
                          color: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade100,
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
                      errorBuilder: (ctx, err, stack) => _buildMapFallback(
                        textColor, accentColor, cardBg, mutedColor, usersWithCoords,
                      ),
                    ),
                  // Count badge top-right
                  if (!_isLoadingMap && usersWithCoords.isNotEmpty)
                    Positioned(
                      top: 10,
                      right: 10,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.6),
                          borderRadius: BorderRadius.circular(99),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.people_rounded, color: Colors.white, size: 13),
                            const SizedBox(width: 4),
                            Text(
                              '${usersWithCoords.length} anggota',
                              style: GoogleFonts.spaceGrotesk(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Fallback jika static map gagal dimuat
  Widget _buildMapFallback(
    Color textColor,
    Color accentColor,
    Color cardBg,
    Color mutedColor,
    List<dynamic> usersWithCoords,
  ) {
    return Container(
      color: widget.isDark ? const Color(0xFF0F172A) : const Color(0xFFE8F4F8),
      child: Stack(
        children: [
          // Grid background (fake map feel)
          CustomPaint(
            painter: _MapGridPainter(isDark: widget.isDark),
            child: const SizedBox.expand(),
          ),
          // User avatars scattered
          ...usersWithCoords.take(12).map((u) {
            final name = u['name'] as String? ?? 'U';
            final photoUrl = u['photo_url'] as String?;
            final initials = name.trim().split(' ').take(2).map((w) => w[0].toUpperCase()).join();
            // Use lat/lng to pseudo-position within the widget
            final lat = (u['lat'] as num?)?.toDouble() ?? 0.0;
            final lng = (u['lng'] as num?)?.toDouble() ?? 0.0;
            // Map lat (-11 to 6) and lng (95 to 141) to 0..1
            final px = ((lng - 95) / 46).clamp(0.05, 0.95);
            final py = ((lat + 11) / 17).clamp(0.05, 0.85);

            return Positioned(
              left: px * 280,
              top: py * 100,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: accentColor, width: 2),
                      color: cardBg,
                    ),
                    child: ClipOval(
                      child: photoUrl != null && photoUrl.isNotEmpty
                          ? Image.network(
                              photoUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (ctx2, e, s) => Center(
                                child: Text(initials,
                                  style: GoogleFonts.spaceGrotesk(
                                    color: textColor, fontSize: 10, fontWeight: FontWeight.w700)),
                              ),
                            )
                          : Center(
                              child: Text(initials,
                                style: GoogleFonts.spaceGrotesk(
                                  color: textColor, fontSize: 10, fontWeight: FontWeight.w700)),
                            ),
                    ),
                  ),
                  Container(
                    width: 2,
                    height: 6,
                    color: accentColor,
                  ),
                  Container(
                    width: 6,
                    height: 6,
                    decoration: BoxDecoration(color: accentColor, shape: BoxShape.circle),
                  ),
                ],
              ),
            );
          }),
          // Label overlay
          Positioned(
            bottom: 8,
            left: 10,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                '© OpenStreetMap',
                style: GoogleFonts.spaceGrotesk(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w500),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildExploreFilterField(
    String hint,
    TextEditingController ctrl,
    IconData icon,
    Color textColor,
    Color cardBg,
  ) {
    return Container(
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.dark, width: 2),
      ),
      child: TextField(
        controller: ctrl,
        style: GoogleFonts.spaceGrotesk(color: textColor, fontWeight: FontWeight.w600, fontSize: 13),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: GoogleFonts.spaceGrotesk(color: Colors.grey.shade500, fontWeight: FontWeight.w500, fontSize: 13),
          prefixIcon: Icon(icon, color: Colors.grey.shade500, size: 16),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        ),
        onSubmitted: (_) => _fetchMembers(),
      ),
    );
  }

  Widget _buildExploreMemberCard(
    Map<String, dynamic> member,
    Color textColor,
    Color accentColor,
    Color cardBg,
    Color mutedColor,
  ) {
    final name = member['name'] as String? ?? 'User';
    final nickname = member['nickname'] as String? ?? '';
    final job = member['job'] as String? ?? '';
    final company = member['company'] as String? ?? '';
    final city = member['city'] as String? ?? '';
    final photo = _resolvePhotoUrl(member['photo_url'] as String?);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: cardBg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.dark, width: 2),
        boxShadow: const [
          BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Banner Area (Simple solid color theme or gradient header)
          Container(
            height: 60,
            decoration: BoxDecoration(
              color: accentColor.withValues(alpha: 0.15),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              border: const Border(bottom: BorderSide(color: AppColors.dark, width: 2)),
            ),
          ),
          // Info Content
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Avatar
                Container(
                  padding: const EdgeInsets.all(3),
                  decoration: const BoxDecoration(color: AppColors.dark, shape: BoxShape.circle),
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.white, width: 2),
                      shape: BoxShape.circle,
                    ),
                    child: _buildAvatar(photo, name, 64),
                  ),
                ),
                const SizedBox(width: 16),
                // Text details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: GoogleFonts.spaceGrotesk(
                          color: textColor,
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                      ),
                      if (nickname.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          '@${nickname.toLowerCase().replaceAll(' ', '')}',
                          style: GoogleFonts.spaceGrotesk(
                            color: mutedColor,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ],
                      const SizedBox(height: 10),
                      if (job.isNotEmpty)
                        Row(
                          children: [
                            Icon(Icons.work_outline_rounded, color: mutedColor, size: 14),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                '$job${company.isNotEmpty ? " @ $company" : ""}',
                                style: GoogleFonts.spaceGrotesk(
                                  color: textColor.withValues(alpha: 0.8),
                                  fontWeight: FontWeight.w600,
                                  fontSize: 12,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      if (city.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Icon(Icons.location_on_outlined, color: mutedColor, size: 14),
                            const SizedBox(width: 6),
                            Text(
                              city,
                              style: GoogleFonts.spaceGrotesk(
                                color: textColor.withValues(alpha: 0.8),
                                fontWeight: FontWeight.w600,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          // View Profile Button
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: NeoButton(
              isDark: widget.isDark,
              backgroundColor: widget.isDark ? const Color(0xFF1E293B) : Colors.white,
              textColor: textColor,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => MemberProfileScreen(
                      memberId: member['id'],
                      authToken: _authToken,
                      isDark: widget.isDark,
                      onToggleTheme: widget.onToggleTheme,
                    ),
                  ),
                );
              },
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'Lihat Profil Lengkap',
                    style: GoogleFonts.spaceGrotesk(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(width: 6),
                  const Icon(Icons.arrow_forward_rounded, size: 16),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── GALLERY TAB METHODS ──────────────────────────────
  Widget _buildGalleryTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton(
        onPressed: _showUploadMemorySheet,
        backgroundColor: accentColor,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.dark, width: 2),
        ),
        elevation: 0,
        child: const Icon(Icons.add_photo_alternate_outlined, color: AppColors.dark),
      ),
      body: RefreshIndicator(
        color: accentColor,
        onRefresh: _fetchMemories,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Galeri Memori',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Momen berharga yang dibagikan oleh seluruh anggota.',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 13,
                        color: mutedColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (_isLoadingMemories && _memories.isEmpty)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.only(top: 100),
                  child: Center(child: CircularProgressIndicator(color: accentColor)),
                ),
              )
            else if (_memories.isEmpty)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.only(top: 100, left: 32, right: 32),
                  child: Column(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: cardBg,
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.dark, width: 2),
                          boxShadow: const [
                            BoxShadow(color: AppColors.dark, offset: Offset(3, 3), blurRadius: 0),
                          ],
                        ),
                        child: Icon(Icons.photo_album_outlined, size: 40, color: accentColor),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Galeri masih kosong',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: textColor,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Jadilah yang pertama mengunggah memori berharga!',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 13,
                          color: mutedColor,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverGrid(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 16,
                    crossAxisSpacing: 16,
                    childAspectRatio: 0.8,
                  ),
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final item = _memories[index] as Map<String, dynamic>;
                      final photo = _resolvePhotoUrl(item['photo_url'] as String?);
                      final caption = item['caption'] as String? ?? '';
                      final author = item['uploader'] as Map<String, dynamic>? ?? {};
                      final authorName = author['name'] as String? ?? 'User';

                      return GestureDetector(
                        onTap: () => _showMemoryDetailSheet(item),
                        child: Container(
                          decoration: BoxDecoration(
                            color: cardBg,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.dark, width: 2),
                            boxShadow: const [
                              BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
                            ],
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                // Photo
                                Expanded(
                                  child: photo != null
                                      ? Image.network(
                                          photo,
                                          fit: BoxFit.cover,
                                          errorBuilder: (context, error, stackTrace) => Container(
                                            color: Colors.grey.shade200,
                                            child: Icon(Icons.broken_image_outlined, color: mutedColor),
                                          ),
                                        )
                                      : Container(color: Colors.grey.shade200),
                                ),
                                // Text details (Caption & Author name)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: cardBg,
                                    border: const Border(top: BorderSide(color: AppColors.dark, width: 1.5)),
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        caption.isNotEmpty ? caption : 'No caption',
                                        style: GoogleFonts.spaceGrotesk(
                                          color: textColor,
                                          fontWeight: FontWeight.w700,
                                          fontSize: 11,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        'Oleh: $authorName',
                                        style: GoogleFonts.spaceGrotesk(
                                          color: mutedColor,
                                          fontWeight: FontWeight.w500,
                                          fontSize: 9,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                    childCount: _memories.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  void _showUploadMemorySheet() {
    final captionCtrl = TextEditingController();
    XFile? pickedPhoto;
    bool uploading = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
        final textColor = widget.isDark ? Colors.white : AppColors.dark;
        final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
        final mutedColor = Colors.grey.shade500;

        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> chooseImage() async {
              final picker = ImagePicker();
              final img = await picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
              if (img != null) {
                setModalState(() => pickedPhoto = img);
              }
            }

            Future<void> performUpload() async {
              final messenger = ScaffoldMessenger.of(context);
              if (pickedPhoto == null) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                      'Pilih foto terlebih dahulu!',
                      style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                    backgroundColor: Colors.redAccent,
                  ),
                );
                return;
              }

              setModalState(() => uploading = true);
              final navigator = Navigator.of(ctx);

              try {
                final request = http.MultipartRequest(
                  'POST',
                  Uri.parse('$_baseUrl/api/gallery'),
                );
                request.headers.addAll({
                  'Accept': 'application/json',
                  'Authorization': 'Bearer $_authToken',
                });
                
                final captionText = captionCtrl.text.trim();
                if (captionText.isNotEmpty) {
                  request.fields['caption'] = captionText;
                }
                
                request.files.add(await http.MultipartFile.fromPath(
                  'photo',
                  pickedPhoto!.path,
                  filename: pickedPhoto!.name,
                ));

                final streamed = await request.send().timeout(const Duration(seconds: 35));
                final response = await http.Response.fromStream(streamed);

                if (response.statusCode == 201) {
                  await _fetchMemories();
                  navigator.pop();
                  if (mounted) {
                    messenger.showSnackBar(
                      SnackBar(
                        backgroundColor: widget.isDark ? AppColors.orange : AppColors.lime,
                        content: Text(
                          '✅ Memori berhasil dibagikan!',
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
                  throw Exception(data['message'] ?? 'Gagal mengunggah.');
                }
              } catch (e) {
                setModalState(() => uploading = false);
                messenger.showSnackBar(
                  SnackBar(
                    backgroundColor: Colors.redAccent,
                    content: Text(
                      'Terjadi kesalahan: ${e.toString()}',
                      style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.w600),
                    ),
                  ),
                );
              }
            }

            return Container(
              height: MediaQuery.of(context).size.height * 0.8,
              decoration: BoxDecoration(
                color: cardBg,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                border: const Border(top: BorderSide(color: AppColors.dark, width: 2)),
                boxShadow: const [BoxShadow(color: AppColors.dark, offset: Offset(0, -4), blurRadius: 0)],
              ),
              child: Column(
                children: [
                  // Header
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    child: Row(
                      children: [
                        Text(
                          'Unggah Memori',
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: textColor,
                          ),
                        ),
                        const Spacer(),
                        GestureDetector(
                          onTap: () => Navigator.pop(ctx),
                          child: Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade100,
                              shape: BoxShape.circle,
                            ),
                            child: Icon(Icons.close_rounded, color: textColor, size: 18),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200, height: 1),
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.all(20),
                      children: [
                        // Photo Picker Area
                        GestureDetector(
                          onTap: chooseImage,
                          child: Container(
                            height: 200,
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: widget.isDark ? const Color(0xFF1E293B) : AppColors.light,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.dark, width: 2),
                              image: pickedPhoto != null
                                  ? DecorationImage(
                                      image: FileImage(File(pickedPhoto!.path)),
                                      fit: BoxFit.cover,
                                    )
                                  : null,
                            ),
                            child: pickedPhoto == null
                                ? Center(
                                    child: Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(Icons.add_a_photo_outlined, size: 44, color: mutedColor),
                                        const SizedBox(height: 10),
                                        Text(
                                          'Pilih Foto dari Galeri',
                                          style: GoogleFonts.spaceGrotesk(
                                            color: textColor,
                                            fontWeight: FontWeight.w700,
                                            fontSize: 13,
                                          ),
                                        ),
                                      ],
                                    ),
                                  )
                                : null,
                          ),
                        ),
                        const SizedBox(height: 20),
                        // Caption field
                        Text(
                          'Keterangan (Caption)',
                          style: GoogleFonts.spaceGrotesk(
                            fontWeight: FontWeight.w800,
                            color: textColor,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: captionCtrl,
                          maxLines: 3,
                          style: GoogleFonts.spaceGrotesk(color: textColor, fontWeight: FontWeight.w600),
                          decoration: InputDecoration(
                            hintText: 'Tulis cerita singkat tentang foto ini...',
                            hintStyle: GoogleFonts.spaceGrotesk(color: mutedColor, fontSize: 13),
                            filled: true,
                            fillColor: widget.isDark ? const Color(0xFF282932) : AppColors.light,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: AppColors.dark, width: 2),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: AppColors.dark, width: 2),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: AppColors.dark, width: 2),
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                        // Submit Button
                        NeoButton(
                          isDark: widget.isDark,
                          backgroundColor: uploading ? Colors.grey.shade400 : accentColor,
                          textColor: AppColors.dark,
                          onTap: uploading ? null : performUpload,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          child: uploading
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    valueColor: AlwaysStoppedAnimation<Color>(AppColors.dark),
                                  ),
                                )
                              : Text(
                                  'Bagikan Momen',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: AppColors.dark,
                                    fontWeight: FontWeight.w800,
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
      },
    );
  }

  Future<void> _downloadImageToGallery(String imageUrl, BuildContext ctx, Function(bool) setDownloading) async {
    final messenger = ScaffoldMessenger.of(ctx);
    setDownloading(true);
    try {
      final hasAccess = await Gal.hasAccess();
      if (!hasAccess) {
        final granted = await Gal.requestAccess();
        if (!granted) {
          messenger.showSnackBar(
            SnackBar(
              backgroundColor: Colors.redAccent,
              content: Text(
                'Izin galeri ditolak. Gagal menyimpan gambar.',
                style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.bold),
              ),
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: const BorderSide(color: AppColors.dark, width: 2),
              ),
            ),
          );
          setDownloading(false);
          return;
        }
      }

      final response = await http.get(Uri.parse(imageUrl)).timeout(const Duration(seconds: 30));
      if (response.statusCode == 200) {
        final tempDir = await getTemporaryDirectory();
        final extension = imageUrl.split('.').last.split('?').first;
        final safeExtension = (extension.length > 4 || extension.isEmpty) ? 'jpg' : extension;
        final filename = 'memora_${DateTime.now().millisecondsSinceEpoch}.$safeExtension';
        final file = File('${tempDir.path}/$filename');
        await file.writeAsBytes(response.bodyBytes);

        await Gal.putImage(file.path);

        messenger.showSnackBar(
          SnackBar(
            backgroundColor: widget.isDark ? AppColors.orange : AppColors.lime,
            content: Text(
              '✅ Gambar berhasil disimpan ke galeri!',
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
      } else {
        throw Exception('Server returned status code: ${response.statusCode}');
      }
    } catch (e) {
      messenger.showSnackBar(
        SnackBar(
          backgroundColor: Colors.redAccent,
          content: Text(
            'Gagal mengunduh gambar: ${e.toString()}',
            style: GoogleFonts.spaceGrotesk(color: Colors.white, fontWeight: FontWeight.bold),
          ),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: const BorderSide(color: AppColors.dark, width: 2),
          ),
        ),
      );
    } finally {
      setDownloading(false);
    }
  }

  void _showMemoryDetailSheet(Map<String, dynamic> memory) {
    final photo = _resolvePhotoUrl(memory['photo_url'] as String?);
    final caption = memory['caption'] as String? ?? '';
    final author = memory['uploader'] as Map<String, dynamic>? ?? {};
    final authorName = author['name'] as String? ?? 'User';
    final authorPhoto = _resolvePhotoUrl(author['photo_url'] as String?);
    final authorId = author['id'] as int? ?? 0;
    final createdAt = memory['created_at']?.toString() ?? '';
    final commentsCount = memory['comments_count'] as int? ?? 0;
    final isOwner = authorId == _userId;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        bool downloading = false;
        return StatefulBuilder(
          builder: (context, setModalState) {
        final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
        final textColor = widget.isDark ? Colors.white : AppColors.dark;
        final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
        final mutedColor = Colors.grey.shade500;

        return Container(
          height: MediaQuery.of(context).size.height * 0.85,
          decoration: BoxDecoration(
            color: cardBg,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            border: const Border(top: BorderSide(color: AppColors.dark, width: 2)),
            boxShadow: const [BoxShadow(color: AppColors.dark, offset: Offset(0, -4), blurRadius: 0)],
          ),
          child: Column(
            children: [
              // Drag indicator
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
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  children: [
                    // Detail photo neobrutalist box
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.transparent,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.dark, width: 2.5),
                        boxShadow: const [
                          BoxShadow(color: AppColors.dark, offset: Offset(6, 6), blurRadius: 0),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(17),
                        child: photo != null
                            ? Image.network(photo, fit: BoxFit.cover)
                            : Container(height: 200, color: Colors.grey.shade200),
                      ),
                    ),
                    const SizedBox(height: 24),
                    // Uploader info row
                    Row(
                      children: [
                        GestureDetector(
                          onTap: () {
                            Navigator.pop(ctx);
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => MemberProfileScreen(
                                  memberId: authorId,
                                  authToken: _authToken,
                                  isDark: widget.isDark,
                                  onToggleTheme: widget.onToggleTheme,
                                ),
                              ),
                            );
                          },
                          child: _buildAvatar(authorPhoto, authorName, 46),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              GestureDetector(
                                onTap: () {
                                  Navigator.pop(ctx);
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => MemberProfileScreen(
                                        memberId: authorId,
                                        authToken: _authToken,
                                        isDark: widget.isDark,
                                        onToggleTheme: widget.onToggleTheme,
                                      ),
                                    ),
                                  );
                                },
                                child: Text(
                                  authorName,
                                  style: GoogleFonts.spaceGrotesk(
                                    color: textColor,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 15,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                createdAt,
                                style: GoogleFonts.spaceGrotesk(
                                  color: mutedColor,
                                  fontWeight: FontWeight.w500,
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (isOwner)
                          IconButton(
                            icon: const Icon(Icons.delete_forever_rounded, color: Colors.redAccent, size: 24),
                            onPressed: () {
                              Navigator.pop(ctx);
                              _confirmDeleteMemory(memory['id'] as int);
                            },
                          ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200),
                    const SizedBox(height: 12),
                    // Caption
                    Text(
                      'Keterangan',
                      style: GoogleFonts.spaceGrotesk(
                        color: mutedColor,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      caption.isNotEmpty ? caption : 'Tidak ada keterangan.',
                      style: GoogleFonts.spaceGrotesk(
                        color: textColor,
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 24),
                    // Tagged Users Section (Check if api returns tagged_users list)
                    if (memory['tagged_users'] != null && (memory['tagged_users'] as List).isNotEmpty) ...[
                      Text(
                        'Anggota Terkait',
                        style: GoogleFonts.spaceGrotesk(
                          color: mutedColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: (memory['tagged_users'] as List).map((tu) {
                          final tuName = tu['name'] as String? ?? 'User';
                          final tuPhoto = _resolvePhotoUrl(tu['photo_url'] as String?);
                          return Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: AppColors.dark, width: 1.5),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                _buildAvatar(tuPhoto, tuName, 18),
                                const SizedBox(width: 6),
                                Text(
                                  tuName,
                                  style: GoogleFonts.spaceGrotesk(
                                    color: textColor,
                                    fontWeight: FontWeight.w700,
                                    fontSize: 11,
                                  ),
                                ),
                              ],
                            ),
                          );
                        }).toList(),
                      ),
                      const SizedBox(height: 24),
                    ],
                    // Action Buttons (Download & Comment sheet trigger)
                    Row(
                      children: [
                        if (photo != null) ...[
                          Expanded(
                            child: NeoButton(
                              isDark: widget.isDark,
                              backgroundColor: downloading
                                  ? Colors.grey.shade400
                                  : (widget.isDark ? const Color(0xFF1E293B) : Colors.white),
                              textColor: textColor,
                              onTap: downloading
                                  ? null
                                  : () => _downloadImageToGallery(
                                        photo,
                                        ctx,
                                        (val) => setModalState(() => downloading = val),
                                      ),
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  downloading
                                      ? SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            valueColor: AlwaysStoppedAnimation<Color>(textColor),
                                          ),
                                        )
                                      : const Icon(Icons.download_rounded, size: 20),
                                  const SizedBox(width: 8),
                                  Text(
                                    downloading ? 'Mengunduh...' : 'Unduh',
                                    style: GoogleFonts.spaceGrotesk(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 14,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                        ],
                        Expanded(
                          child: NeoButton(
                            isDark: widget.isDark,
                            backgroundColor: accentColor,
                            textColor: AppColors.dark,
                            onTap: downloading
                                ? null
                                : () {
                                    Navigator.pop(ctx);
                                    _showMemoryCommentsSheet(memory);
                                  },
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.chat_bubble_outline_rounded, color: AppColors.dark, size: 20),
                                const SizedBox(width: 8),
                                Text(
                                  'Komentar ($commentsCount)',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: AppColors.dark,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 14,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ],
          ),
        );
          },
        );
      },
    );
  }

  void _showMemoryCommentsSheet(Map<String, dynamic> memory) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => CommentsSheet(
        isDark: widget.isDark,
        commentsPath: '/api/gallery/${memory['id']}/comments',
        initialCommentsCount: memory['comments_count'] as int? ?? 0,
        baseUrl: _baseUrl,
        authHeaders: _authHeaders,
        textColor: widget.isDark ? Colors.white : AppColors.dark,
        accentColor: widget.isDark ? AppColors.orange : AppColors.lime,
        cardBg: widget.isDark ? AppColors.cardDark : Colors.white,
        mutedColor: Colors.grey.shade500,
        userPhotoUrl: _userPhotoUrl,
        userName: _userName,
      ),
    ).then((_) {
      // Refresh memory list when closing comments sheet to sync counts
      _fetchMemories();
    });
  }

  void _confirmDeleteMemory(int id) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: widget.isDark ? AppColors.cardDark : Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
          side: const BorderSide(color: AppColors.dark, width: 2),
        ),
        title: Text(
          'Hapus Memori?',
          style: GoogleFonts.spaceGrotesk(
            fontWeight: FontWeight.w800,
            color: widget.isDark ? Colors.white : AppColors.dark,
          ),
        ),
        content: Text(
          'Momen berharga ini akan dihapus secara permanen dari galeri. Lanjutkan?',
          style: GoogleFonts.spaceGrotesk(
            color: widget.isDark ? Colors.white : AppColors.dark,
            fontWeight: FontWeight.w500,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(
              'Batal',
              style: GoogleFonts.spaceGrotesk(
                color: widget.isDark ? AppColors.orange : AppColors.lime,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          NeoButton(
            isDark: widget.isDark,
            backgroundColor: const Color(0xFFFEE2E2),
            textColor: Colors.red.shade800,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            onTap: () {
              Navigator.pop(ctx);
              _deleteMemory(id);
            },
            child: Text(
              'Hapus',
              style: GoogleFonts.spaceGrotesk(
                fontWeight: FontWeight.bold,
                color: Colors.red.shade800,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteMemory(int id) async {
    setState(() => _isLoadingMemories = true);
    try {
      final response = await http.delete(
        Uri.parse('$_baseUrl/api/gallery/$id'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200 || response.statusCode == 204) {
        await _fetchMemories();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              backgroundColor: Colors.redAccent,
              content: Text(
                '✅ Memori berhasil dihapus!',
                style: GoogleFonts.spaceGrotesk(
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          );
        }
      } else {
        throw Exception('Gagal menghapus.');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.redAccent,
            content: Text(
              'Gagal menghapus memori: ${e.toString()}',
              style: GoogleFonts.spaceGrotesk(color: Colors.white),
            ),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoadingMemories = false);
      }
    }
  }

  // ── EVENTS TAB METHODS ───────────────────────────────
  Widget _buildEventsTab(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return RefreshIndicator(
      color: accentColor,
      onRefresh: _fetchEvents,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                   Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Acara Komunitas',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                                color: textColor,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Ikuti agenda pertemuan, diskusi, reuni, dan kegiatan seru lainnya.',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 13,
                                color: mutedColor,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () => _showCreateEventSheet(textColor, accentColor, cardBg),
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: accentColor,
                            shape: BoxShape.circle,
                            border: Border.all(color: AppColors.dark, width: 2),
                            boxShadow: const [
                              BoxShadow(
                                color: AppColors.dark,
                                offset: Offset(2, 2),
                                blurRadius: 0,
                              ),
                            ],
                          ),
                          child: const Icon(Icons.add, color: AppColors.dark, size: 20),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Segmented switch (Upcoming / Past)
                  Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.dark, width: 2),
                    ),
                    child: Row(
                      children: [
                        // Upcoming button
                        Expanded(
                          child: GestureDetector(
                            onTap: () {
                              if (_isPastSelected) {
                                setState(() {
                                  _isPastSelected = false;
                                  _events = [];
                                });
                                _fetchEvents();
                              }
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              decoration: BoxDecoration(
                                color: !_isPastSelected ? accentColor : Colors.transparent,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Center(
                                child: Text(
                                  'Akan Datang',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: !_isPastSelected ? AppColors.dark : mutedColor,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 13,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        // Past button
                        Expanded(
                          child: GestureDetector(
                            onTap: () {
                              if (!_isPastSelected) {
                                setState(() {
                                  _isPastSelected = true;
                                  _events = [];
                                });
                                _fetchEvents();
                              }
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              decoration: BoxDecoration(
                                color: _isPastSelected ? accentColor : Colors.transparent,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Center(
                                child: Text(
                                  'Telah Lewat',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: _isPastSelected ? AppColors.dark : mutedColor,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 13,
                                  ),
                                ),
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
          ),
          if (_isLoadingEvents && _events.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.only(top: 80),
                child: Center(child: CircularProgressIndicator(color: accentColor)),
              ),
            )
          else if (_events.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.only(top: 80, left: 32, right: 32),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: cardBg,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.dark, width: 2),
                        boxShadow: const [
                          BoxShadow(color: AppColors.dark, offset: Offset(3, 3), blurRadius: 0),
                        ],
                      ),
                      child: Icon(Icons.calendar_month_outlined, size: 40, color: accentColor),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Tidak ada acara',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      _isPastSelected ? 'Belum ada agenda acara yang telah selesai.' : 'Belum ada agenda acara baru yang direncanakan.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 13,
                        color: mutedColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final event = _events[index] as Map<String, dynamic>;
                    final id = event['id'] as int? ?? 0;
                    final title = event['title'] as String? ?? 'Acara';
                    final dateRaw = event['event_date_raw']?.toString() ?? '';
                    final location = event['location'] as String? ?? '';
                    final organizer = event['organizer'] as String? ?? 'Panitia';
                    final attendeesCount = event['attendees_count'] as int? ?? 0;
                    final rsvpStatus = event['rsvp_status'] ?? event['my_rsvp'] as String?;

                    // Parsing date for bold badge
                    String day = '??';
                    String month = 'EVT';
                    if (dateRaw.isNotEmpty) {
                      try {
                        final dt = DateTime.parse(dateRaw);
                        day = dt.day.toString();
                        final months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGT', 'SEP', 'OKT', 'NOV', 'DES'];
                        month = months[dt.month - 1];
                      } catch (_) {}
                    }

                    return GestureDetector(
                      onTap: () => _showEventDetailSheet(event, textColor, accentColor, cardBg, mutedColor),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          color: cardBg,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: AppColors.dark, width: 2),
                          boxShadow: const [
                            BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Padding(
                              padding: const EdgeInsets.all(16),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Date bold banner badge
                                  Container(
                                    width: 60,
                                    height: 64,
                                    decoration: BoxDecoration(
                                      color: accentColor,
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(color: AppColors.dark, width: 2),
                                      boxShadow: const [
                                        BoxShadow(color: AppColors.dark, offset: Offset(2, 2), blurRadius: 0),
                                      ],
                                    ),
                                    child: Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Text(
                                          day,
                                          style: GoogleFonts.spaceGrotesk(
                                            color: AppColors.dark,
                                            fontWeight: FontWeight.w800,
                                            fontSize: 20,
                                            height: 1,
                                          ),
                                        ),
                                        Text(
                                          month,
                                          style: GoogleFonts.spaceGrotesk(
                                            color: AppColors.dark,
                                            fontWeight: FontWeight.w800,
                                            fontSize: 11,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  // Info details
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          title,
                                          style: GoogleFonts.spaceGrotesk(
                                            color: textColor,
                                            fontWeight: FontWeight.w800,
                                            fontSize: 16,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          'Diselenggarakan oleh: $organizer',
                                          style: GoogleFonts.spaceGrotesk(
                                            color: mutedColor,
                                            fontWeight: FontWeight.w600,
                                            fontSize: 11,
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            Icon(Icons.location_on_outlined, color: mutedColor, size: 14),
                                            const SizedBox(width: 6),
                                            Expanded(
                                              child: Text(
                                                location,
                                                style: GoogleFonts.spaceGrotesk(
                                                  color: textColor.withValues(alpha: 0.8),
                                                  fontWeight: FontWeight.w600,
                                                  fontSize: 12,
                                                ),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            // Attendees block
                            GestureDetector(
                              onTap: () => _showAttendeesSheet(id),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                                decoration: BoxDecoration(
                                  color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade50,
                                  border: const Border(
                                    top: BorderSide(color: AppColors.dark, width: 1.5),
                                    bottom: BorderSide(color: AppColors.dark, width: 1.5),
                                  ),
                                ),
                                child: Row(
                                  children: [
                                    Icon(Icons.people_outline_rounded, color: mutedColor, size: 16),
                                    const SizedBox(width: 8),
                                    Text(
                                      '$attendeesCount Hadir',
                                      style: GoogleFonts.spaceGrotesk(
                                        color: textColor,
                                        fontWeight: FontWeight.w700,
                                        fontSize: 12,
                                      ),
                                    ),
                                    const Spacer(),
                                    Text(
                                      'Lihat Daftar Hadir',
                                      style: GoogleFonts.spaceGrotesk(
                                        color: accentColor,
                                        fontWeight: FontWeight.w800,
                                        fontSize: 11,
                                      ),
                                    ),
                                    const SizedBox(width: 4),
                                    Icon(Icons.arrow_forward_ios_rounded, color: accentColor, size: 10),
                                  ],
                                ),
                              ),
                            ),
                            // RSVP Buttons action
                            Padding(
                              padding: const EdgeInsets.all(12),
                              child: _buildRsvpToggle(id, rsvpStatus),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                  childCount: _events.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildRsvpToggle(int eventId, String? rsvpStatus) {
    final activeHadir = rsvpStatus == 'hadir';
    final activeTidakHadir = rsvpStatus == 'tidak_hadir';
    final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;

    return Row(
      children: [
        // Hadir button
        Expanded(
          child: GestureDetector(
            onTap: () => _submitRsvp(eventId, 'hadir'),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                color: activeHadir ? accentColor : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.dark, width: activeHadir ? 2 : 1),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    activeHadir ? Icons.check_circle_rounded : Icons.check_circle_outline_rounded,
                    color: activeHadir ? AppColors.dark : Colors.grey.shade500,
                    size: 16,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    'Hadir',
                    style: GoogleFonts.spaceGrotesk(
                      color: activeHadir ? AppColors.dark : Colors.grey.shade500,
                      fontWeight: activeHadir ? FontWeight.w800 : FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 10),
        // Tidak Hadir button
        Expanded(
          child: GestureDetector(
            onTap: () => _submitRsvp(eventId, 'tidak_hadir'),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                color: activeTidakHadir ? const Color(0xFFFEE2E2) : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: activeTidakHadir ? AppColors.dark : Colors.grey.shade300, width: activeTidakHadir ? 2 : 1),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    activeTidakHadir ? Icons.cancel_rounded : Icons.cancel_outlined,
                    color: activeTidakHadir ? Colors.red.shade800 : Colors.grey.shade500,
                    size: 16,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    'Tidak Hadir',
                    style: GoogleFonts.spaceGrotesk(
                      color: activeTidakHadir ? Colors.red.shade800 : Colors.grey.shade500,
                      fontWeight: activeTidakHadir ? FontWeight.w800 : FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _submitRsvp(int eventId, String status) async {
    final idx = _events.indexWhere((e) => e['id'] == eventId);
    if (idx == -1) return;

    final event = _events[idx];
    final oldStatus = event['rsvp_status'] ?? event['my_rsvp'] as String?;
    final oldAttendeeCount = event['attendees_count'] as int? ?? 0;

    // Optimistic update
    setState(() {
      int diff = 0;
      if (status == 'hadir' && oldStatus != 'hadir') {
        diff = 1;
      } else if (status == 'tidak_hadir' && oldStatus == 'hadir') {
        diff = -1;
      }
      
      _events[idx] = Map<String, dynamic>.from(event)
        ..['rsvp_status'] = status
        ..['my_rsvp'] = status
        ..['attendees_count'] = oldAttendeeCount + diff;
    });
    _eventDetailSheetStateSetter?.call(() {});

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/events/$eventId/rsvp'),
        headers: _authHeaders,
        body: jsonEncode({'status': status}),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        final responseData = data['data'] as Map<String, dynamic>?;
        if (responseData != null) {
          setState(() {
            _events[idx] = Map<String, dynamic>.from(_events[idx])
              ..['rsvp_status'] = responseData['rsvp_status']
              ..['my_rsvp'] = responseData['rsvp_status']
              ..['attendees_count'] = responseData['attendees_count'];
          });
          _eventDetailSheetStateSetter?.call(() {});
        }
        HapticFeedback.mediumImpact();
      } else {
        // Revert
        setState(() {
          _events[idx] = event;
        });
        _eventDetailSheetStateSetter?.call(() {});
      }
    } catch (_) {
      // Revert
      setState(() {
        _events[idx] = event;
      });
      _eventDetailSheetStateSetter?.call(() {});
    }
  }

  void _showAttendeesSheet(int eventId) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
        final textColor = widget.isDark ? Colors.white : AppColors.dark;
        final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
        final mutedColor = Colors.grey.shade500;
        
        List<dynamic> attendees = [];
        bool loading = true;
        
        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> fetchAttendees() async {
              try {
                final response = await http.get(
                  Uri.parse('$_baseUrl/api/events/$eventId/attendees'),
                  headers: _authHeaders,
                ).timeout(const Duration(seconds: 10));
                if (response.statusCode == 200) {
                  final data = jsonDecode(response.body);
                  setModalState(() {
                    attendees = data['data'] as List? ?? [];
                    loading = false;
                  });
                } else {
                  setModalState(() => loading = false);
                }
              } catch (_) {
                setModalState(() => loading = false);
              }
            }
            
            if (loading && attendees.isEmpty) {
              fetchAttendees();
            }
            
            return Container(
              height: MediaQuery.of(context).size.height * 0.6,
              decoration: BoxDecoration(
                color: cardBg,
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
                        color: mutedColor.withValues(alpha: 0.4),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                    child: Row(
                      children: [
                        Text(
                          'Daftar Hadir',
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: textColor,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: accentColor.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(99),
                          ),
                          child: Text(
                            '${attendees.length}',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: accentColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200),
                  Expanded(
                    child: loading
                        ? Center(child: CircularProgressIndicator(color: accentColor))
                        : attendees.isEmpty
                            ? Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.people_outline_rounded, size: 48, color: mutedColor),
                                    const SizedBox(height: 12),
                                    Text(
                                      'Belum ada yang RSVP',
                                      style: GoogleFonts.spaceGrotesk(color: mutedColor, fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              )
                            : ListView.builder(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                itemCount: attendees.length,
                                itemBuilder: (context, i) {
                                  final attendee = attendees[i];
                                  final name = attendee['name'] as String? ?? 'User';
                                  final nickname = attendee['nickname'] as String? ?? '';
                                  final city = attendee['city'] as String? ?? '';
                                  final photo = _resolvePhotoUrl(attendee['photo_url'] as String?);
                                  
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 12),
                                    child: GestureDetector(
                                      onTap: () {
                                        Navigator.pop(context);
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (context) => MemberProfileScreen(
                                              memberId: attendee['id'],
                                              authToken: _authToken,
                                              isDark: widget.isDark,
                                              onToggleTheme: widget.onToggleTheme,
                                            ),
                                          ),
                                        );
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.all(12),
                                        decoration: BoxDecoration(
                                          color: widget.isDark ? const Color(0xFF252630) : AppColors.light,
                                          borderRadius: BorderRadius.circular(16),
                                          border: Border.all(
                                            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
                                          ),
                                        ),
                                        child: Row(
                                          children: [
                                            _buildAvatar(photo, name, 40),
                                            const SizedBox(width: 12),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    name,
                                                    style: GoogleFonts.spaceGrotesk(
                                                      fontWeight: FontWeight.w700,
                                                      fontSize: 14,
                                                      color: textColor,
                                                    ),
                                                  ),
                                                  if (nickname.isNotEmpty || city.isNotEmpty) ...[
                                                    const SizedBox(height: 2),
                                                    Text(
                                                      '${nickname.isNotEmpty ? "@$nickname" : ""}${city.isNotEmpty ? " • $city" : ""}',
                                                      style: GoogleFonts.spaceGrotesk(
                                                        fontSize: 12,
                                                        color: mutedColor,
                                                        fontWeight: FontWeight.w500,
                                                    ),
                                                  ),
                                                ],
                                              ],
                                            ),
                                          ),
                                          Icon(Icons.chevron_right_rounded, color: mutedColor, size: 20),
                                        ],
                                      ),
                                    ),
                                  ),
                                );
                              },
                            ),
                ),
              ],
            ),
          );
        },
      );
    },
  );
}

  void _showCreateEventSheet(Color textColor, Color accentColor, Color cardBg) {
    final titleCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final locCtrl = TextEditingController();
    DateTime? eventDate;
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (BuildContext context, StateSetter setSheetState) {
            return Container(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              decoration: BoxDecoration(
                color: widget.isDark ? AppColors.darkBg : Colors.white,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                border: const Border(
                  top: BorderSide(color: AppColors.dark, width: 3),
                  left: BorderSide(color: AppColors.dark, width: 3),
                  right: BorderSide(color: AppColors.dark, width: 3),
                ),
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Container(
                        width: 40,
                        height: 5,
                        decoration: BoxDecoration(
                          color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    Text(
                      '🎉 Buat Event Baru',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 16),
                    _buildSheetTextField(
                      controller: titleCtrl,
                      label: 'Judul Event',
                      hint: 'cth: Reuni Angkatan 2020',
                      icon: Icons.event_rounded,
                      accentColor: accentColor,
                      textColor: textColor,
                    ),
                    const SizedBox(height: 14),
                    _buildSheetTextField(
                      controller: descCtrl,
                      label: 'Deskripsi',
                      hint: 'Detail tentang event...',
                      icon: Icons.description_rounded,
                      accentColor: accentColor,
                      textColor: textColor,
                      maxLines: 3,
                    ),
                    const SizedBox(height: 14),
                    _buildSheetTextField(
                      controller: locCtrl,
                      label: 'Lokasi',
                      hint: 'cth: Aula Serbaguna, Jakarta',
                      icon: Icons.location_on_rounded,
                      accentColor: accentColor,
                      textColor: textColor,
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'Tanggal Event',
                      style: GoogleFonts.spaceGrotesk(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 6),
                    GestureDetector(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: eventDate ?? DateTime.now(),
                          firstDate: DateTime.now(),
                          lastDate: DateTime.now().add(const Duration(days: 365 * 3)),
                          builder: (ctx, child) => Theme(
                            data: Theme.of(ctx).copyWith(
                              colorScheme: ColorScheme.dark(
                                primary: accentColor,
                                onPrimary: AppColors.dark,
                                surface: cardBg,
                              ),
                            ),
                            child: child!,
                          ),
                        );
                        if (picked != null) {
                          setSheetState(() => eventDate = picked);
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                        decoration: BoxDecoration(
                          color: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: eventDate != null
                                ? accentColor
                                : (widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                            width: 1.5,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.calendar_today_rounded,
                              color: eventDate != null ? accentColor : Colors.grey.shade500,
                              size: 18,
                            ),
                            const SizedBox(width: 10),
                            Text(
                              eventDate == null
                                  ? 'Pilih Tanggal Event'
                                  : '${eventDate!.day.toString().padLeft(2, '0')} / '
                                      '${eventDate!.month.toString().padLeft(2, '0')} / '
                                      '${eventDate!.year}',
                              style: GoogleFonts.spaceGrotesk(
                                color: eventDate != null ? textColor : Colors.grey.shade500,
                                fontWeight: FontWeight.w600,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: accentColor,
                        foregroundColor: AppColors.dark,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: const BorderSide(color: AppColors.dark, width: 2),
                        ),
                        elevation: 0,
                      ),
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              final title = titleCtrl.text.trim();
                              final desc = descCtrl.text.trim();
                              final loc = locCtrl.text.trim();

                              if (title.isEmpty || eventDate == null) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      'Judul dan tanggal event wajib diisi',
                                      style: GoogleFonts.spaceGrotesk(),
                                    ),
                                    backgroundColor: Colors.red.shade800,
                                  ),
                                );
                                return;
                              }

                              setSheetState(() => isSubmitting = true);

                              try {
                                final dateStr =
                                    '${eventDate!.year.toString().padLeft(4, '0')}-${eventDate!.month.toString().padLeft(2, '0')}-${eventDate!.day.toString().padLeft(2, '0')} 00:00:00';
                                
                                final response = await http.post(
                                  Uri.parse('$_baseUrl/api/events'),
                                  headers: _authHeaders,
                                  body: jsonEncode({
                                    'title': title,
                                    'description': desc,
                                    'event_date': dateStr,
                                    'location': loc.isEmpty ? null : loc,
                                  }),
                                ).timeout(const Duration(seconds: 12));

                                if (response.statusCode == 201 || response.statusCode == 200) {
                                  Navigator.pop(ctx); // Close sheet
                                  ScaffoldMessenger.of(this.context).showSnackBar(
                                    SnackBar(
                                      backgroundColor: accentColor,
                                      content: Text(
                                        '🎉 Event berhasil dibuat!',
                                        style: GoogleFonts.spaceGrotesk(
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.dark,
                                        ),
                                      ),
                                    ),
                                  );
                                  setState(() {
                                    _currentTab = 3;
                                    _events = [];
                                  });
                                  _fetchEvents();
                                } else {
                                  final data = jsonDecode(response.body);
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(
                                        data['message'] ?? 'Gagal membuat event',
                                        style: GoogleFonts.spaceGrotesk(),
                                      ),
                                      backgroundColor: Colors.red.shade800,
                                    ),
                                  );
                                }
                              } catch (_) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      'Koneksi bermasalah',
                                      style: GoogleFonts.spaceGrotesk(),
                                    ),
                                    backgroundColor: Colors.red.shade800,
                                  ),
                                );
                              } finally {
                                setSheetState(() => isSubmitting = false);
                              }
                            },
                      child: isSubmitting
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.5,
                                color: AppColors.dark,
                              ),
                            )
                          : Text(
                              'Buat Event',
                              style: GoogleFonts.spaceGrotesk(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                              ),
                            ),
                    ),
                    const SizedBox(height: 10),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildSheetTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    required Color accentColor,
    required Color textColor,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.spaceGrotesk(
            fontWeight: FontWeight.w600,
            fontSize: 13,
            color: textColor,
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          style: GoogleFonts.spaceGrotesk(color: textColor, fontSize: 14),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.spaceGrotesk(color: Colors.grey.shade500, fontSize: 14),
            prefixIcon: Icon(icon, color: accentColor, size: 20),
            filled: true,
            fillColor: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade50,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: accentColor, width: 2),
            ),
          ),
        ),
      ],
    );
  }

  void _showEventDetailSheet(
    Map<String, dynamic> event,
    Color textColor,
    Color accentColor,
    Color cardBg,
    Color mutedColor,
  ) {
    final id = event['id'] as int? ?? 0;
    final title = event['title'] as String? ?? 'Acara';
    final description = event['description'] as String? ?? '';
    final dateStr = event['event_date']?.toString() ?? event['date']?.toString() ?? '';
    final timeStr = event['time']?.toString() ?? '';
    final location = event['location'] as String? ?? '';
    final organizer = event['organizer'] as String? ?? 'Panitia';

    double? eventLat;
    double? eventLng;
    bool loadingMap = false;
    bool geocodingFailed = false;

    Future<void> geocodeLocation(String loc, StateSetter setModalState) async {
      if (loc.isEmpty || eventLat != null) return;
      setModalState(() {
        loadingMap = true;
        geocodingFailed = false;
      });
      try {
        final query = Uri.encodeComponent(loc);
        final response = await http.get(
          Uri.parse('https://nominatim.openstreetmap.org/search?q=$query&format=json&limit=1'),
          headers: {'User-Agent': 'MemoraApp/1.0'},
        ).timeout(const Duration(seconds: 8));

        if (response.statusCode == 200) {
          final List data = jsonDecode(response.body);
          if (data.isNotEmpty) {
            final first = data[0];
            eventLat = double.tryParse(first['lat']?.toString() ?? '');
            eventLng = double.tryParse(first['lon']?.toString() ?? '');
          }
        }
        setModalState(() {
          loadingMap = false;
          if (eventLat == null || eventLng == null) {
            geocodingFailed = true;
          }
        });
      } catch (_) {
        setModalState(() {
          loadingMap = false;
          geocodingFailed = true;
        });
      }
    }

    Future<void> launchGoogleMaps(String address) async {
      final url = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(address)}');
      if (await canLaunchUrl(url)) {
        await launchUrl(url, mode: LaunchMode.externalApplication);
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Tidak dapat membuka peta.', style: GoogleFonts.spaceGrotesk()),
              backgroundColor: Colors.red.shade700,
            ),
          );
        }
      }
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            _eventDetailSheetStateSetter = setModalState;

            final currentEvent = _events.firstWhere((e) => e['id'] == id, orElse: () => event);
            final currentRsvp = currentEvent['rsvp_status'] ?? currentEvent['my_rsvp'] as String?;
            final currentAttendees = currentEvent['attendees_count'] as int? ?? 0;

            if (eventLat == null && !loadingMap && !geocodingFailed && location.isNotEmpty) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                geocodeLocation(location, setModalState);
              });
            }

            Widget buildMapFallback() {
              return Container(
                color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade100,
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.map_outlined, color: mutedColor, size: 32),
                    const SizedBox(height: 8),
                    Text(
                      location,
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.spaceGrotesk(
                        color: textColor,
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: accentColor,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.dark, width: 1.5),
                      ),
                      child: Text(
                        'Buka di Google Maps',
                        style: GoogleFonts.spaceGrotesk(
                          color: AppColors.dark,
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }

            Widget buildMapSection() {
              if (location.isEmpty) return const SizedBox.shrink();

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 20),
                  Text(
                    'Peta Lokasi',
                    style: GoogleFonts.spaceGrotesk(
                      color: mutedColor,
                      fontWeight: FontWeight.w700,
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: () => launchGoogleMaps(location),
                    child: Container(
                      height: 160,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade50,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.dark, width: 2),
                        boxShadow: const [
                          BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(14),
                        child: loadingMap
                            ? Center(child: CircularProgressIndicator(color: accentColor))
                            : (geocodingFailed || eventLat == null || eventLng == null)
                                ? buildMapFallback()
                                : Stack(
                                    children: [
                                      Image.network(
                                        'https://staticmap.openstreetmap.de/staticmap.php'
                                        '?center=$eventLat,$eventLng'
                                        '&zoom=14'
                                        '&size=600x300'
                                        '&maptype=osm'
                                        '&marker=$eventLat,$eventLng,red-pushpin',
                                        width: double.infinity,
                                        height: double.infinity,
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, __, ___) => buildMapFallback(),
                                      ),
                                      Positioned(
                                        bottom: 12,
                                        right: 12,
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                          decoration: BoxDecoration(
                                            color: AppColors.dark,
                                            borderRadius: BorderRadius.circular(20),
                                          ),
                                          child: Row(
                                            children: [
                                              const Icon(Icons.map_rounded, color: Colors.white, size: 14),
                                              const SizedBox(width: 6),
                                              Text(
                                                'Buka di Maps',
                                                style: GoogleFonts.spaceGrotesk(
                                                  color: Colors.white,
                                                  fontSize: 11,
                                                  fontWeight: FontWeight.w700,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                      ),
                    ),
                  ),
                ],
              );
            }

            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              decoration: BoxDecoration(
                color: cardBg,
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
                        color: mutedColor.withValues(alpha: 0.4),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      children: [
                        // Event Banner Badge
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: accentColor.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.dark, width: 2),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: accentColor,
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: AppColors.dark, width: 1.5),
                                ),
                                child: Text(
                                  'ACARA KOMUNITAS',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: AppColors.dark,
                                    fontWeight: FontWeight.w800,
                                    fontSize: 10,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                title,
                                style: GoogleFonts.spaceGrotesk(
                                  color: textColor,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 20,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                'Organizer: $organizer',
                                style: GoogleFonts.spaceGrotesk(
                                  color: mutedColor,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),
                        // Timing & Location detail row
                        _buildEventDetailRow(Icons.calendar_month_rounded, 'Tanggal', dateStr, textColor, mutedColor),
                        if (timeStr.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          _buildEventDetailRow(Icons.access_time_rounded, 'Waktu', timeStr, textColor, mutedColor),
                        ],
                        const SizedBox(height: 12),
                        _buildEventDetailRow(Icons.location_on_outlined, 'Lokasi', location, textColor, mutedColor),
                        const SizedBox(height: 12),
                        _buildEventDetailRow(Icons.people_outline_rounded, 'Daftar Hadir', '$currentAttendees Hadir', textColor, mutedColor, isAction: true, onTap: () {
                          Navigator.pop(ctx);
                          _showAttendeesSheet(id);
                        }),
                        const SizedBox(height: 20),
                        Divider(color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200),
                        const SizedBox(height: 12),
                        // Description
                        Text(
                          'Deskripsi Acara',
                          style: GoogleFonts.spaceGrotesk(
                            color: mutedColor,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          description.isNotEmpty ? description : 'Tidak ada rincian deskripsi.',
                          style: GoogleFonts.spaceGrotesk(
                            color: textColor,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                            height: 1.5,
                          ),
                        ),
                        buildMapSection(),
                        const SizedBox(height: 32),
                        // RSVP Block
                        Text(
                          'Konfirmasi Kehadiran Anda',
                          style: GoogleFonts.spaceGrotesk(
                            color: textColor,
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(height: 10),
                        _buildRsvpToggleInSheet(id, currentRsvp, () {
                          setModalState(() {});
                        }),
                        const SizedBox(height: 48),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    ).then((_) {
      _eventDetailSheetStateSetter = null;
    });
  }

  Widget _buildEventDetailRow(IconData icon, String title, String val, Color textColor, Color mutedColor, {bool isAction = false, VoidCallback? onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade100,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: AppColors.dark, width: 1.5),
            ),
            child: Icon(icon, color: widget.isDark ? AppColors.orange : AppColors.lime, size: 18),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.spaceGrotesk(color: mutedColor, fontWeight: FontWeight.w700, fontSize: 11),
                ),
                const SizedBox(height: 2),
                Text(
                  val,
                  style: GoogleFonts.spaceGrotesk(
                    color: textColor,
                    fontWeight: FontWeight.w800,
                    fontSize: 13,
                    decoration: isAction ? TextDecoration.underline : null,
                  ),
                ),
              ],
            ),
          ),
          if (isAction)
            Icon(Icons.arrow_forward_ios_rounded, color: widget.isDark ? AppColors.orange : AppColors.lime, size: 14),
        ],
      ),
    );
  }

  Widget _buildRsvpToggleInSheet(int eventId, String? rsvpStatus, VoidCallback onUpdate) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: widget.isDark ? const Color(0xFF1E293B) : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.dark, width: 1.5),
      ),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: () {
                _submitRsvp(eventId, 'hadir');
                Future.delayed(const Duration(milliseconds: 150), onUpdate);
              },
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  color: rsvpStatus == 'hadir' ? (widget.isDark ? AppColors.orange : AppColors.lime) : Colors.transparent,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Center(
                  child: Text(
                    'Hadir',
                    style: GoogleFonts.spaceGrotesk(
                      color: rsvpStatus == 'hadir' ? AppColors.dark : Colors.grey.shade500,
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: GestureDetector(
              onTap: () {
                _submitRsvp(eventId, 'tidak_hadir');
                Future.delayed(const Duration(milliseconds: 150), onUpdate);
              },
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  color: rsvpStatus == 'tidak_hadir' ? const Color(0xFFFEE2E2) : Colors.transparent,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Center(
                  child: Text(
                    'Tidak Hadir',
                    style: GoogleFonts.spaceGrotesk(
                      color: rsvpStatus == 'tidak_hadir' ? Colors.red.shade800 : Colors.grey.shade500,
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
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
  final Future<void> Function(String content, String category, {XFile? photo, List<String>? pollOptions}) onSubmit;
  final bool autoPickImage;
  final bool autoOpenEmoji;
  final bool openPollMode;

  const _ComposeSheet({
    required this.isDark,
    required this.textColor,
    required this.accentColor,
    required this.cardBg,
    this.userPhotoUrl,
    required this.userName,
    required this.isPosting,
    required this.onSubmit,
    this.autoPickImage = false,
    this.autoOpenEmoji = false,
    this.openPollMode = false,
  });

  @override
  State<_ComposeSheet> createState() => _ComposeSheetState();
}

class _ComposeSheetState extends State<_ComposeSheet> {
  final TextEditingController _ctrl = TextEditingController();
  bool _isPosting = false;
  XFile? _pickedImage;
  bool _pickedIsVideo = false;

  // Emoji picker state
  bool _showEmojiPicker = false;

  // Poll mode state
  bool _isPollMode = false;
  final TextEditingController _pollQuestionCtrl = TextEditingController();
  final List<TextEditingController> _pollOptionCtrls = [
    TextEditingController(),
    TextEditingController(),
  ];

  // Common emojis grouped
  static const List<String> _emojis = [
    '😀','😂','🥹','😍','🥰','😎','🤔','😅','😭','🥺',
    '😤','🤩','🫶','👍','👏','🙌','🔥','❤️','💪','✨',
    '🎉','🎊','🚀','🌟','💡','🏆','🙏','😊','🤗','💬',
    '🎯','📸','🌈','🍀','💯','👀','🫠','🤝','🥳','😢',
  ];

  @override
  void initState() {
    super.initState();
    if (widget.autoPickImage) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _pickImage());
    }
    if (widget.autoOpenEmoji) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        setState(() => _showEmojiPicker = true);
      });
    }
    if (widget.openPollMode) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        setState(() => _isPollMode = true);
      });
    }
  }

  // Kategori valid sesuai validasi API Laravel
  static const List<Map<String, String>> _categories = [
    {'value': 'karier',      'label': '💼 Karier'},
    {'value': 'pendidikan',  'label': '📚 Pendidikan'},
    {'value': 'keluarga',    'label': '🏠 Keluarga'},
    {'value': 'perjalanan',  'label': '✈️ Perjalanan'},
    {'value': 'lainnya',     'label': '✨ Lainnya'},
  ];
  String _selectedCategory = 'lainnya';

  @override
  void dispose() {
    _ctrl.dispose();
    _pollQuestionCtrl.dispose();
    for (final c in _pollOptionCtrls) { c.dispose(); }
    super.dispose();
  }

  void _insertEmoji(String emoji) {
    final text = _ctrl.text;
    final sel = _ctrl.selection;
    final start = sel.start < 0 ? text.length : sel.start;
    final end = sel.end < 0 ? text.length : sel.end;
    final newText = text.replaceRange(start, end, emoji);
    _ctrl.value = TextEditingValue(
      text: newText,
      selection: TextSelection.collapsed(offset: start + emoji.length),
    );
    setState(() {});
  }



  bool get _canSubmit {
    if (_isPollMode) {
      final q = _pollQuestionCtrl.text.trim();
      final validOpts = _pollOptionCtrls.where((c) => c.text.trim().isNotEmpty).length;
      return q.isNotEmpty && validOpts >= 2;
    }
    return _ctrl.text.trim().isNotEmpty;
  }

  /// Membuka galeri foto/video perangkat
  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final picked = await picker.pickMedia(
      imageQuality: 85,
    );
    if (picked != null) {
      final isVideo = picked.path.toLowerCase().endsWith('.mp4') ||
                      picked.path.toLowerCase().endsWith('.mov') ||
                      picked.path.toLowerCase().endsWith('.avi') ||
                      picked.path.toLowerCase().endsWith('.webm') ||
                      picked.path.toLowerCase().endsWith('.mkv') ||
                      picked.path.toLowerCase().endsWith('.3gp');
      setState(() {
        _pickedImage = picked;
        _pickedIsVideo = isVideo;
      });
    }
  }

  Future<void> _submit() async {
    if (!_canSubmit) return;
    setState(() => _isPosting = true);
    if (_isPollMode) {
      final question = _pollQuestionCtrl.text.trim();
      final opts = _pollOptionCtrls
          .map((c) => c.text.trim())
          .where((s) => s.isNotEmpty)
          .toList();
      await widget.onSubmit(question, _selectedCategory, pollOptions: opts);
    } else {
      await widget.onSubmit(_ctrl.text, _selectedCategory, photo: _pickedImage);
    }
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
                    // ── Text input or Poll mode ──
                    if (_isPollMode) ...[
                      // Poll mode UI
                      Container(
                        margin: const EdgeInsets.only(bottom: 4),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF8B5CF6).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF8B5CF6).withValues(alpha: 0.4)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.poll_rounded, color: Color(0xFF8B5CF6), size: 18),
                            const SizedBox(width: 8),
                            Text(
                              'Mode Polling',
                              style: GoogleFonts.spaceGrotesk(
                                color: const Color(0xFF8B5CF6),
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                              ),
                            ),
                            const Spacer(),
                            GestureDetector(
                              onTap: () => setState(() => _isPollMode = false),
                              child: const Icon(Icons.close_rounded, color: Color(0xFF8B5CF6), size: 18),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      // Optional caption
                      TextField(
                        controller: _ctrl,
                        maxLines: 2,
                        style: GoogleFonts.spaceGrotesk(
                          color: widget.textColor,
                          fontSize: 15,
                          fontWeight: FontWeight.w500,
                        ),
                        decoration: InputDecoration(
                          hintText: 'Tambahkan caption (opsional)...',
                          hintStyle: GoogleFonts.spaceGrotesk(
                            color: widget.isDark ? Colors.grey.shade500 : Colors.grey.shade400,
                            fontSize: 15,
                          ),
                          border: InputBorder.none,
                        ),
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 12),
                      // Poll question
                      TextField(
                        controller: _pollQuestionCtrl,
                        maxLines: null,
                        style: GoogleFonts.spaceGrotesk(
                          color: widget.textColor,
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                        decoration: InputDecoration(
                          hintText: '📊 Tulis pertanyaan polling...',
                          hintStyle: GoogleFonts.spaceGrotesk(
                            color: widget.isDark ? Colors.grey.shade500 : Colors.grey.shade400,
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                          filled: true,
                          fillColor: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade50,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFF8B5CF6), width: 2),
                          ),
                        ),
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 12),
                      // Poll options
                      ...List.generate(_pollOptionCtrls.length, (i) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Row(
                            children: [
                              Container(
                                width: 28,
                                height: 28,
                                decoration: BoxDecoration(
                                  color: const Color(0xFF8B5CF6).withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                ),
                                child: Center(
                                  child: Text(
                                    String.fromCharCode(65 + i),
                                    style: GoogleFonts.spaceGrotesk(
                                      color: const Color(0xFF8B5CF6),
                                      fontWeight: FontWeight.w800,
                                      fontSize: 13,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: TextField(
                                  controller: _pollOptionCtrls[i],
                                  style: GoogleFonts.spaceGrotesk(
                                    color: widget.textColor,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w500,
                                  ),
                                  decoration: InputDecoration(
                                    hintText: 'Pilihan ${i + 1}...',
                                    hintStyle: GoogleFonts.spaceGrotesk(
                                      color: widget.isDark ? Colors.grey.shade600 : Colors.grey.shade400,
                                    ),
                                    filled: true,
                                    fillColor: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade50,
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(10),
                                      borderSide: BorderSide(color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                                    ),
                                    enabledBorder: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(10),
                                      borderSide: BorderSide(color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade300),
                                    ),
                                    focusedBorder: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(10),
                                      borderSide: const BorderSide(color: Color(0xFF8B5CF6), width: 1.5),
                                    ),
                                  ),
                                  onChanged: (_) => setState(() {}),
                                ),
                              ),
                              if (i >= 2)
                                GestureDetector(
                                  onTap: () => setState(() {
                                    _pollOptionCtrls[i].dispose();
                                    _pollOptionCtrls.removeAt(i);
                                  }),
                                  child: Padding(
                                    padding: const EdgeInsets.only(left: 8),
                                    child: Icon(Icons.remove_circle_outline, color: Colors.red.shade400, size: 20),
                                  ),
                                ),
                            ],
                          ),
                        );
                      }),
                      // Add option button
                      if (_pollOptionCtrls.length < 5)
                        GestureDetector(
                          onTap: () => setState(() {
                            _pollOptionCtrls.add(TextEditingController());
                          }),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              border: Border.all(color: const Color(0xFF8B5CF6).withValues(alpha: 0.5), width: 1.5),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Center(
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.add_rounded, color: Color(0xFF8B5CF6), size: 18),
                                  const SizedBox(width: 6),
                                  Text(
                                    'Tambah Pilihan',
                                    style: GoogleFonts.spaceGrotesk(
                                      color: const Color(0xFF8B5CF6),
                                      fontWeight: FontWeight.w700,
                                      fontSize: 13,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                    ] else ...[
                      // Normal text mode
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildAvatarInSheet(widget.userPhotoUrl, widget.userName),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextField(
                              controller: _ctrl,
                              autofocus: !widget.autoOpenEmoji,
                              maxLines: null,
                              style: GoogleFonts.spaceGrotesk(
                                color: widget.textColor,
                                fontSize: 17,
                                fontWeight: FontWeight.w500,
                              ),
                              decoration: InputDecoration(
                                hintText: 'Apa yang sedang terjadi?',
                                hintStyle: GoogleFonts.spaceGrotesk(
                                  color: widget.isDark ? Colors.grey.shade500 : Colors.grey.shade400,
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
                    ],
                    const SizedBox(height: 20),
                    // ── Emoji Picker Panel ──────────────────────────
                    if (_showEmojiPicker) ...[
                      Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: widget.isDark ? const Color(0xFF1A1B24) : Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: widget.isDark ? Colors.grey.shade700 : Colors.grey.shade200,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Emoji',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                                    fontWeight: FontWeight.w700,
                                    fontSize: 12,
                                  ),
                                ),
                                const Spacer(),
                                GestureDetector(
                                  onTap: () => setState(() => _showEmojiPicker = false),
                                  child: Icon(Icons.keyboard_arrow_down_rounded,
                                    color: widget.isDark ? Colors.grey.shade500 : Colors.grey.shade500, size: 20),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Wrap(
                              spacing: 4,
                              runSpacing: 4,
                              children: _emojis.map((emoji) {
                                return GestureDetector(
                                  onTap: () => _insertEmoji(emoji),
                                  child: Container(
                                    width: 40,
                                    height: 40,
                                    decoration: BoxDecoration(
                                      color: widget.isDark ? const Color(0xFF252630) : Colors.white,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Center(
                                      child: Text(emoji, style: const TextStyle(fontSize: 22)),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                          ],
                        ),
                      ),
                    ],
                    // ── Category selector ──────────────────────────
                    Text(
                      'Kategori',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: widget.isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _categories.map((cat) {
                        final isSelected = _selectedCategory == cat['value'];
                        return GestureDetector(
                          onTap: () => setState(() => _selectedCategory = cat['value']!),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 150),
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                            decoration: BoxDecoration(
                              color: isSelected
                                  ? widget.accentColor
                                  : (widget.isDark ? const Color(0xFF252630) : Colors.grey.shade100),
                              borderRadius: BorderRadius.circular(99),
                              border: Border.all(
                                color: isSelected ? widget.accentColor : Colors.transparent,
                                width: 1.5,
                              ),
                            ),
                            child: Text(
                              cat['label']!,
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: isSelected
                                    ? AppColors.dark
                                    : (widget.isDark ? Colors.grey.shade300 : Colors.grey.shade700),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
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
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Preview foto/video terpilih
                    if (_pickedImage != null) ...[
                      Stack(
                        alignment: Alignment.topRight,
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: _pickedIsVideo
                                ? Container(
                                    height: 160,
                                    width: double.infinity,
                                    color: Colors.black87,
                                    child: Stack(
                                      alignment: Alignment.center,
                                      children: [
                                        const Icon(Icons.videocam_rounded, color: Colors.white, size: 48),
                                        Positioned(
                                          bottom: 12,
                                          left: 12,
                                          right: 12,
                                          child: Text(
                                            _pickedImage!.name,
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(color: Colors.white70, fontSize: 12),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                  )
                                : Image.file(
                                    File(_pickedImage!.path),
                                    height: 160,
                                    width: double.infinity,
                                    fit: BoxFit.cover,
                                  ),
                          ),
                          GestureDetector(
                            onTap: () => setState(() {
                              _pickedImage = null;
                              _pickedIsVideo = false;
                            }),
                            child: Container(
                              margin: const EdgeInsets.all(6),
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Colors.black.withValues(alpha: 0.6),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close_rounded, color: Colors.white, size: 16),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                    ],
                    // Action icons + tombol post
                    Row(
                      children: [
                        // Foto
                        GestureDetector(
                          onTap: _isPollMode ? null : _pickImage,
                          child: Icon(
                            _pickedIsVideo
                                ? Icons.videocam_outlined
                                : Icons.image_outlined,
                            color: _isPollMode
                                ? Colors.grey.shade600
                                : (_pickedImage != null ? widget.accentColor : const Color(0xFF1D9BF0)),
                            size: 24,
                          ),
                        ),
                        const SizedBox(width: 16),
                        // Emoji toggle
                        GestureDetector(
                          onTap: () => setState(() => _showEmojiPicker = !_showEmojiPicker),
                          child: Icon(
                            Icons.emoji_emotions_outlined,
                            color: _showEmojiPicker
                                ? const Color(0xFFF59E0B)
                                : (widget.isDark ? Colors.grey.shade400 : Colors.grey.shade500),
                            size: 24,
                          ),
                        ),
                        const SizedBox(width: 16),
                        // Poll toggle
                        GestureDetector(
                          onTap: () => setState(() {
                            _isPollMode = !_isPollMode;
                            if (_isPollMode) {
                              _pickedImage = null;
                              _pickedIsVideo = false;
                            }
                          }),
                          child: Icon(
                            Icons.poll_outlined,
                            color: _isPollMode
                                ? const Color(0xFF8B5CF6)
                                : (widget.isDark ? Colors.grey.shade400 : Colors.grey.shade500),
                            size: 24,
                          ),
                        ),
                        const Spacer(),
                        NeoButton(
                          isDark: widget.isDark,
                          backgroundColor: !_canSubmit || _isPosting
                              ? Colors.grey.shade400
                              : AppColors.dark,
                          textColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                          onTap: !_canSubmit || _isPosting ? null : _submit,
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
class CommentsSheet extends StatefulWidget {
  final bool isDark;
  final String commentsPath;
  final int initialCommentsCount;
  final String baseUrl;
  final Map<String, String> authHeaders;
  final Color textColor;
  final Color accentColor;
  final Color cardBg;
  final Color mutedColor;
  final String? userPhotoUrl;
  final String userName;

  const CommentsSheet({
    super.key,
    required this.isDark,
    required this.commentsPath,
    required this.initialCommentsCount,
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
  State<CommentsSheet> createState() => CommentsSheetState();
}

class CommentsSheetState extends State<CommentsSheet> {
  List<Map<String, dynamic>> _comments = [];
  bool _isLoading = true;
  final TextEditingController _commentCtrl = TextEditingController();
  bool _isSending = false;
  late int _commentsCount;

  @override
  void initState() {
    super.initState();
    _commentsCount = widget.initialCommentsCount;
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
            Uri.parse('${widget.baseUrl}${widget.commentsPath}'),
            headers: widget.authHeaders,
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _comments = List<Map<String, dynamic>>.from(data['data'] ?? []);
          _commentsCount = _comments.length;
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
            Uri.parse('${widget.baseUrl}${widget.commentsPath}'),
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
                        '$_commentsCount',
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
                              // API mengembalikan field 'author' (CommentResource)
                              final author = c['author'] as Map<String, dynamic>? ?? {};
                              final authorName = author['name'] as String? ?? 'User';
                              // photo_url comment bisa relatif — resolvenya di sini
                              final rawPhotoUrl = author['photo_url'] as String?;
                              final photoUrl = (rawPhotoUrl != null && !rawPhotoUrl.startsWith('http'))
                                  ? '${widget.baseUrl}$rawPhotoUrl'
                                  : rawPhotoUrl;
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 16),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    _miniAvatar(photoUrl, authorName),
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

// ──────────────────────────────────────────────────────
// MAP GRID PAINTER — fallback visual for map preview
// ──────────────────────────────────────────────────────
class _MapGridPainter extends CustomPainter {
  final bool isDark;
  _MapGridPainter({required this.isDark});

  @override
  void paint(Canvas canvas, Size size) {
    final gridColor = isDark
        ? const Color(0xFF1E293B)
        : const Color(0xFFCBDAE0);
    final roadColor = isDark
        ? const Color(0xFF253347)
        : const Color(0xFFBDD0D8);

    // Background
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = isDark ? const Color(0xFF0F172A) : const Color(0xFFD8EAF0),
    );

    final gridPaint = Paint()
      ..color = gridColor
      ..strokeWidth = 1;

    // Horizontal grid lines
    for (double y = 0; y < size.height; y += 24) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
    }
    // Vertical grid lines
    for (double x = 0; x < size.width; x += 24) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), gridPaint);
    }

    // Fake roads
    final roadPaint = Paint()
      ..color = roadColor
      ..strokeWidth = 6
      ..strokeCap = StrokeCap.round;

    // Horizontal roads
    for (final y in [size.height * 0.3, size.height * 0.65]) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), roadPaint);
    }
    // Vertical roads
    for (final x in [size.width * 0.25, size.width * 0.6]) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), roadPaint);
    }
  }

  @override
  bool shouldRepaint(_MapGridPainter oldDelegate) => oldDelegate.isDark != isDark;
}
