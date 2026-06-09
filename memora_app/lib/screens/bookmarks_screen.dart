import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:memora_app/theme/app_theme.dart';
import 'package:memora_app/screens/feed_screen.dart';
import 'package:memora_app/widgets/post_video_player.dart';

class BookmarksScreen extends StatefulWidget {
  final String authToken;
  final String baseUrl;
  final bool isDark;
  final VoidCallback onToggleTheme;

  const BookmarksScreen({
    super.key,
    required this.authToken,
    required this.baseUrl,
    required this.isDark,
    required this.onToggleTheme,
  });

  @override
  State<BookmarksScreen> createState() => _BookmarksScreenState();
}

class _BookmarksScreenState extends State<BookmarksScreen> {
  List<PostModel> _bookmarkedPosts = [];
  bool _isLoading = true;
  int _currentPage = 1;
  bool _hasMore = true;
  final ScrollController _scrollController = ScrollController();

  Map<String, String> get _authHeaders => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ${widget.authToken}',
      };

  @override
  void initState() {
    super.initState();
    _fetchBookmarks(reset: true);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
        if (!_isLoading && _hasMore) {
          _loadMoreBookmarks();
        }
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchBookmarks({bool reset = false}) async {
    if (reset) {
      setState(() {
        _isLoading = true;
        _currentPage = 1;
        _hasMore = true;
        _bookmarkedPosts = [];
      });
    }

    try {
      final response = await http
          .get(
            Uri.parse('${widget.baseUrl}/api/posts/bookmarks?page=$_currentPage'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List items = data['data'] as List? ?? [];
        final meta = data['meta'] as Map<String, dynamic>?;
        final lastPage = meta?['last_page'] as int? ?? 1;
        final List<PostModel> fetched =
            items.map((e) => PostModel.fromJson(e as Map<String, dynamic>, baseUrl: widget.baseUrl)).toList();

        setState(() {
          if (reset) {
            _bookmarkedPosts = fetched;
          } else {
            _bookmarkedPosts.addAll(fetched);
          }
          _hasMore = _currentPage < lastPage;
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadMoreBookmarks() async {
    _currentPage++;
    await _fetchBookmarks();
  }

  Future<void> _toggleLike(int postId) async {
    final idx = _bookmarkedPosts.indexWhere((p) => p.id == postId);
    if (idx == -1) return;

    final post = _bookmarkedPosts[idx];
    // Optimistic update
    setState(() {
      _bookmarkedPosts[idx] = post.copyWith(
        isLiked: !post.isLiked,
        likesCount: post.isLiked ? post.likesCount - 1 : post.likesCount + 1,
      );
    });

    try {
      await http
          .post(
            Uri.parse('${widget.baseUrl}/api/posts/$postId/like'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));
    } catch (_) {
      // Revert on error
      setState(() {
        _bookmarkedPosts[idx] = post;
      });
    }
  }

  Future<void> _toggleBookmark(int postId) async {
    final idx = _bookmarkedPosts.indexWhere((p) => p.id == postId);
    if (idx == -1) return;

    final post = _bookmarkedPosts[idx];
    
    // Remove dynamically from bookmarks page since user unbookmarked it
    setState(() {
      _bookmarkedPosts.removeAt(idx);
    });

    try {
      final response = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/posts/$postId/bookmark'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));

      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception('Gagal memperbarui bookmark.');
      }
    } catch (_) {
      // Revert on error (re-insert)
      setState(() {
        _bookmarkedPosts.insert(idx, post);
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

  Future<void> _votePoll(PostModel post, int pollId, int optionId) async {
    final postIdx = _bookmarkedPosts.indexWhere((p) => p.id == post.id);
    if (postIdx == -1) return;

    try {
      final response = await http
          .post(
            Uri.parse('${widget.baseUrl}/api/posts/${post.id}/poll/$pollId/vote'),
            headers: _authHeaders,
            body: jsonEncode({'option_id': optionId}),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          final updatedData = data['data'];
          final List optionsData = updatedData['options'] as List;

          final updatedOptions = optionsData.map((o) {
            return PollOptionModel(
              id: o['id'] as int,
              text: o['text'] as String,
              votesCount: o['votes_count'] as int,
              percent: o['percent'] as int,
            );
          }).toList();

          setState(() {
            final updatedPoll = post.poll!.copyWith(
              totalVotes: updatedData['total_votes'] as int,
              userVotedOptionId: optionId,
              options: updatedOptions,
            );
            _bookmarkedPosts[postIdx] = post.copyWith(poll: updatedPoll);
          });
        }
      }
    } catch (_) {}
  }

  void _showCommentsSheet(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => CommentsSheet(
        isDark: widget.isDark,
        commentsPath: '/api/posts/${post.id}/comments',
        initialCommentsCount: post.commentsCount,
        baseUrl: widget.baseUrl,
        authHeaders: _authHeaders,
        textColor: textColor,
        accentColor: accentColor,
        cardBg: cardBg,
        mutedColor: mutedColor,
        userName: 'User', // dynamic name will load inside the CommentsSheet or fallback
      ),
    ).then((_) {
      // Sync comment count on close
      _syncPostCommentCount(post.id);
    });
  }

  Future<void> _syncPostCommentCount(int postId) async {
    try {
      final res = await http.get(
        Uri.parse('${widget.baseUrl}/api/posts/$postId'),
        headers: _authHeaders,
      ).timeout(const Duration(seconds: 5));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        final postData = data['data'];
        final freshCommentsCount = postData['comments_count'] as int? ?? 0;
        final idx = _bookmarkedPosts.indexWhere((p) => p.id == postId);
        if (idx != -1) {
          setState(() {
            _bookmarkedPosts[idx] = _bookmarkedPosts[idx].copyWith(); // copyWith currently sets commentsCount from original this, let's fix model later or just set it
          });
          // Since copyWith doesn't support changing commentCount directly, let's instantiate new Model or modify copyWith later if needed.
          // For now, let's fetch again or modify copyWith if necessary. We'll update state model commentsCount manually:
          setState(() {
            final p = _bookmarkedPosts[idx];
            _bookmarkedPosts[idx] = PostModel(
              id: p.id,
              authorId: p.authorId,
              content: p.content,
              photoUrl: p.photoUrl,
              category: p.category,
              likesCount: p.likesCount,
              isLiked: p.isLiked,
              isBookmarked: p.isBookmarked,
              commentsCount: freshCommentsCount,
              authorName: p.authorName,
              authorPhotoUrl: p.authorPhotoUrl,
              createdAt: p.createdAt,
              poll: p.poll,
            );
          });
        }
      }
    } catch (_) {}
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
        title: Text(
          'Bookmark Tersimpan',
          style: GoogleFonts.spaceGrotesk(
            color: textColor,
            fontWeight: FontWeight.w800,
            fontSize: 20,
          ),
        ),
        leading: IconButton(
          icon: Icon(Icons.arrow_back_rounded, color: textColor),
          onPressed: () => Navigator.pop(context),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(
            color: widget.isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            height: 1,
          ),
        ),
      ),
      body: RefreshIndicator(
        color: accentColor,
        onRefresh: () => _fetchBookmarks(reset: true),
        child: _isLoading && _bookmarkedPosts.isEmpty
            ? Center(child: CircularProgressIndicator(color: accentColor))
            : _bookmarkedPosts.isEmpty
                ? _buildEmptyState(textColor, mutedColor, accentColor, cardBg)
                : ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    itemCount: _bookmarkedPosts.length + (_hasMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == _bookmarkedPosts.length) {
                        return Center(
                          child: Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: CircularProgressIndicator(color: accentColor),
                          ),
                        );
                      }
                      final post = _bookmarkedPosts[index];
                      return _buildPostCard(post, textColor, accentColor, cardBg, mutedColor);
                    },
                  ),
      ),
    );
  }

  Widget _buildEmptyState(Color textColor, Color mutedColor, Color accentColor, Color cardBg) {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Container(
        height: MediaQuery.of(context).size.height * 0.7,
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: cardBg,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.dark, width: 2),
                boxShadow: const [
                  BoxShadow(color: AppColors.dark, offset: Offset(4, 4), blurRadius: 0),
                ],
              ),
              child: Icon(Icons.bookmark_border_rounded, size: 48, color: accentColor),
            ),
            const SizedBox(height: 24),
            Text(
              'Belum ada bookmark',
              style: GoogleFonts.spaceGrotesk(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: textColor,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Postingan yang Anda simpan akan muncul di sini agar mudah dibaca kembali.',
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

  Widget _buildPostCard(PostModel post, Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
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
              ],
            ),
          ),
        ],
      ),
    );
  }

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

  Widget _buildPollWidget(PostModel post, PollModel poll, Color accent,
      Color textColor, Color mutedColor, Color cardBg) {
    final hasVoted = poll.userVotedOptionId != null;
    final showResults = hasVoted || poll.isExpired;
    final pillBorder = widget.isDark ? const Color(0xFF3B82F6) : const Color(0xFF2563EB);

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
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}
