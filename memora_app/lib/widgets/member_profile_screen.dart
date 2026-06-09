import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:memora_app/config/app_config.dart';
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/screens/feed_screen.dart';

class MemberProfileScreen extends StatefulWidget {
  final int memberId;
  final String authToken;
  final bool isDark;
  final VoidCallback onToggleTheme;

  const MemberProfileScreen({
    super.key,
    required this.memberId,
    required this.authToken,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<MemberProfileScreen> createState() => _MemberProfileScreenState();
}

class _MemberProfileScreenState extends State<MemberProfileScreen> {
  bool _isLoading = true;
  bool _isLoadingPosts = true;

  // Member details
  String _name = '';
  String _nickname = '';
  String _bio = '';
  String _city = '';
  String _job = '';
  String _company = '';
  String _quote = '';
  String _bornDate = '';
  String? _photoUrl;
  String? _bannerUrl;
  String _createdAt = '';

  List<PostModel> _myPosts = [];
  final String _baseUrl = AppConfig.baseUrl;

  @override
  void initState() {
    super.initState();
    _fetchProfile();
  }

  Map<String, String> get _authHeaders => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ${widget.authToken}',
      };

  Future<void> _fetchProfile() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/users/${widget.memberId}'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final user = data['data']['user'];
        setState(() {
          _name = user['name'] as String? ?? 'User';
          _nickname = user['nickname'] as String? ?? '';
          _bio = user['bio'] as String? ?? '';
          _city = user['city'] as String? ?? '';
          _job = user['job'] as String? ?? '';
          _company = user['company'] as String? ?? '';
          _quote = user['quote'] as String? ?? '';
          _bornDate = user['born_date'] as String? ?? '';
          _photoUrl = _resolveUrl(user['photo_url'] as String?);
          _bannerUrl = _resolveUrl(user['banner_url'] as String?);
          _createdAt = user['created_at'] as String? ?? '';
          _isLoading = false;
        });

        await _fetchMyPosts();
      } else {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _fetchMyPosts() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/posts?user_id=${widget.memberId}'),
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
          _isLoadingPosts = false;
        });
      } else {
        setState(() => _isLoadingPosts = false);
      }
    } catch (_) {
      setState(() => _isLoadingPosts = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
    final bgColor = widget.isDark ? AppColors.darkBg : AppColors.light;
    final textColor = widget.isDark ? Colors.white : AppColors.dark;
    final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
    final mutedColor = Colors.grey.shade500;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: widget.isDark ? AppColors.darkBg : Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: textColor),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          _name.isNotEmpty ? _name : 'Profil Anggota',
          style: GoogleFonts.spaceGrotesk(fontWeight: FontWeight.w800, color: textColor),
        ),
        centerTitle: true,
        shape: Border(
          bottom: BorderSide(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            width: 1,
          ),
        ),

      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: accentColor))
          : SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Banner & Avatar
                  Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Container(
                        height: 150,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: widget.isDark ? AppColors.cardDark : Colors.grey.shade200,
                          border: const Border(
                            bottom: BorderSide(color: AppColors.dark, width: 2),
                          ),
                          image: _bannerUrl != null
                              ? DecorationImage(
                                  image: NetworkImage(_bannerUrl!),
                                  fit: BoxFit.cover,
                                )
                              : const DecorationImage(
                                  image: NetworkImage("https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2000"),
                                  fit: BoxFit.cover,
                                ),
                        ),
                      ),
                      Positioned(
                        left: 20,
                        bottom: -40,
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
                            child: _buildAvatar(_photoUrl, _name, 80),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 56),

                  // Detail profile
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _name,
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            color: textColor,
                          ),
                        ),
                        if (_nickname.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(
                            '@${_nickname.toLowerCase().replaceAll(' ', '')}',
                            style: GoogleFonts.spaceGrotesk(
                              fontSize: 14,
                              color: mutedColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        Text(
                          _bio.isNotEmpty ? _bio : 'Belum ada bio.',
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 14,
                            color: textColor,
                            height: 1.5,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Wrap(
                          spacing: 16,
                          runSpacing: 10,
                          children: [
                            if (_city.isNotEmpty)
                              _buildInfoChip(Icons.location_on_outlined, _city, textColor, mutedColor),
                            if (_job.isNotEmpty)
                              _buildInfoChip(
                                Icons.work_outline_rounded,
                                '$_job${_company.isNotEmpty ? " @ $_company" : ""}',
                                textColor,
                                mutedColor,
                              ),
                            if (_bornDate.isNotEmpty)
                              _buildInfoChip(Icons.cake_outlined, 'Lahir: $_bornDate', textColor, mutedColor),
                            if (_createdAt.isNotEmpty)
                              _buildInfoChip(Icons.calendar_today_outlined, 'Bergabung: $_createdAt', textColor, mutedColor),
                          ],
                        ),

                        if (_quote.isNotEmpty) ...[
                          const SizedBox(height: 20),
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
                                    '"$_quote"',
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

                  // Posts list
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Row(
                      children: [
                        Icon(Icons.notes_rounded, color: textColor, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          'Cerita Anggota',
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

                  if (_isLoadingPosts)
                    const Center(child: Padding(
                      padding: EdgeInsets.all(24.0),
                      child: CircularProgressIndicator(),
                    ))
                  else if (_myPosts.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 20),
                      child: Center(
                        child: Column(
                          children: [
                            Text(
                              'Belum ada cerita',
                              style: GoogleFonts.spaceGrotesk(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: textColor,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'Cerita yang dibagikan oleh $_name akan muncul di sini.',
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
                    ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _myPosts.length,
                      itemBuilder: (ctx, idx) {
                        final post = _myPosts[idx];
                        // Renders with custom post rendering
                        return _buildPostCardLocal(post, textColor, accentColor, cardBg, mutedColor);
                      },
                    ),
                ],
              ),
            ),
    );
  }

  String? _resolveUrl(String? url) {
    if (url == null || url.isEmpty) return null;
    if (url.startsWith('http')) return url;
    return '$_baseUrl$url';
  }

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

  Widget _buildPostCardLocal(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
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
              ],
            ),
          ),
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
              ),
            ),
          ],
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}
