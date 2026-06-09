import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:memora_app/theme/app_theme.dart';

class NotificationModel {
  final int id;
  final String type;
  final String title;
  final Map<String, dynamic> data;
  final bool isRead;
  final String createdAt;

  NotificationModel({
    required this.id,
    required this.type,
    required this.title,
    required this.data,
    required this.isRead,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] as int,
      type: json['type'] as String? ?? 'default',
      title: json['title'] as String? ?? 'Notifikasi',
      data: json['data'] is Map<String, dynamic>
          ? json['data'] as Map<String, dynamic>
          : {},
      isRead: json['is_read'] as bool? ?? false,
      createdAt: json['created_at'] as String? ?? '',
    );
  }

  NotificationModel copyWith({bool? isRead}) {
    return NotificationModel(
      id: id,
      type: type,
      title: title,
      data: data,
      isRead: isRead ?? this.isRead,
      createdAt: createdAt,
    );
  }
}

class NotificationsScreen extends StatefulWidget {
  final String authToken;
  final String baseUrl;
  final bool isDark;

  const NotificationsScreen({
    super.key,
    required this.authToken,
    required this.baseUrl,
    required this.isDark,
  });

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<NotificationModel> _notifications = [];
  bool _isLoading = true;
  int _currentPage = 1;
  bool _hasMore = true;
  int _unreadCount = 0;
  final ScrollController _scrollController = ScrollController();

  Map<String, String> get _authHeaders => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ${widget.authToken}',
      };

  @override
  void initState() {
    super.initState();
    _fetchNotifications(reset: true);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        if (!_isLoading && _hasMore) {
          _loadMoreNotifications();
        }
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchNotifications({bool reset = false}) async {
    if (reset) {
      setState(() {
        _isLoading = true;
        _currentPage = 1;
        _hasMore = true;
        _notifications = [];
      });
    }

    try {
      final response = await http
          .get(
            Uri.parse('${widget.baseUrl}/api/notifications?page=$_currentPage'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List items = data['data']['notifications'] as List? ?? [];
        final meta = data['meta'] as Map<String, dynamic>?;
        final lastPage = meta?['last_page'] as int? ?? 1;
        final unreadCount = data['data']['unread_count'] as int? ?? 0;

        final List<NotificationModel> fetched =
            items.map((e) => NotificationModel.fromJson(e as Map<String, dynamic>)).toList();

        setState(() {
          if (reset) {
            _notifications = fetched;
          } else {
            _notifications.addAll(fetched);
          }
          _unreadCount = unreadCount;
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

  Future<void> _loadMoreNotifications() async {
    setState(() {
      _currentPage++;
      _isLoading = true;
    });
    await _fetchNotifications(reset: false);
  }

  Future<void> _markAsRead(int notificationId) async {
    HapticFeedback.lightImpact();
    // Optimistic UI update
    setState(() {
      _notifications = _notifications.map((n) {
        if (n.id == notificationId && !n.isRead) {
          if (_unreadCount > 0) _unreadCount--;
          return n.copyWith(isRead: true);
        }
        return n;
      }).toList();
    });

    try {
      final response = await http
          .put(
            Uri.parse('${widget.baseUrl}/api/notifications/$notificationId/read'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode != 200) {
        // Rollback on failure
        _fetchNotifications(reset: true);
      }
    } catch (_) {
      _fetchNotifications(reset: true);
    }
  }

  Future<void> _markAllAsRead() async {
    HapticFeedback.mediumImpact();
    // Optimistic UI update
    setState(() {
      _notifications = _notifications.map((n) => n.copyWith(isRead: true)).toList();
      _unreadCount = 0;
    });

    try {
      final response = await http
          .put(
            Uri.parse('${widget.baseUrl}/api/notifications/read-all'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Semua notifikasi ditandai sudah dibaca',
              style: GoogleFonts.spaceGrotesk(
                color: AppColors.dark,
                fontWeight: FontWeight.bold,
              ),
            ),
            backgroundColor: widget.isDark ? AppColors.orange : AppColors.lime,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      } else {
        _fetchNotifications(reset: true);
      }
    } catch (_) {
      _fetchNotifications(reset: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final accentColor = widget.isDark ? AppColors.orange : AppColors.lime;
    final cardBg = widget.isDark ? AppColors.cardDark : Colors.white;
    final textColor = widget.isDark ? Colors.white : AppColors.dark;
    final mutedColor = Colors.grey.shade500;

    return Scaffold(
      backgroundColor: widget.isDark ? AppColors.darkBg : Colors.white,
      appBar: AppBar(
        backgroundColor: widget.isDark ? AppColors.darkBg : Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: Padding(
          padding: const EdgeInsets.all(8.0),
          child: GestureDetector(
            onTap: () => Navigator.pop(context, _unreadCount),
            child: Container(
              decoration: BoxDecoration(
                color: cardBg,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.dark, width: 2),
              ),
              child: Icon(Icons.arrow_back_rounded, color: textColor, size: 20),
            ),
          ),
        ),
        title: Text(
          'Notifikasi',
          style: GoogleFonts.spaceGrotesk(
            fontWeight: FontWeight.w900,
            fontSize: 20,
            color: textColor,
          ),
        ),
        actions: [
          if (_unreadCount > 0)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Center(
                child: GestureDetector(
                  onTap: _markAllAsRead,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: accentColor,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.dark, width: 2),
                      boxShadow: const [
                        BoxShadow(
                          color: AppColors.dark,
                          offset: Offset(2, 2),
                          blurRadius: 0,
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.done_all_rounded, color: AppColors.dark, size: 16),
                        const SizedBox(width: 4),
                        Text(
                          'Baca Semua',
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: AppColors.dark,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
      body: PopScope(
        canPop: false,
        onPopInvokedWithResult: (didPop, result) {
          if (didPop) return;
          Navigator.pop(context, _unreadCount);
        },
        child: RefreshIndicator(
          color: accentColor,
          onRefresh: () => _fetchNotifications(reset: true),
          child: _notifications.isEmpty && !_isLoading
              ? _buildEmptyState(textColor, cardBg)
              : _buildList(textColor, accentColor, cardBg, mutedColor),
        ),
      ),
    );
  }

  Widget _buildEmptyState(Color textColor, Color cardBg) {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Container(
        height: MediaQuery.of(context).size.height * 0.7,
        alignment: Alignment.center,
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: cardBg,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: AppColors.dark, width: 2.5),
            boxShadow: const [
              BoxShadow(
                color: AppColors.dark,
                offset: Offset(6, 6),
                blurRadius: 0,
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: widget.isDark ? AppColors.darkBg : AppColors.light,
                  shape: BoxShape.circle,
                  border: Border.all(color: AppColors.dark, width: 2),
                ),
                child: Icon(
                  Icons.notifications_none_rounded,
                  size: 48,
                  color: widget.isDark ? AppColors.orange : AppColors.lime,
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Sunyi Sekali...',
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 20,
                  fontWeight: FontWeight.w900,
                  color: textColor,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'Belum ada notifikasi baru untukmu saat ini. Tarik ke bawah untuk memuat ulang.',
                textAlign: TextAlign.center,
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: textColor.withValues(alpha: 0.7),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildList(Color textColor, Color accentColor, Color cardBg, Color mutedColor) {
    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(20),
      itemCount: _notifications.length + (_hasMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == _notifications.length) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.dark),
              ),
            ),
          );
        }

        final item = _notifications[index];
        final message = item.data['message'] as String? ?? 'Pesan notifikasi';

        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: GestureDetector(
            onTap: () => _markAsRead(item.id),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: item.isRead
                    ? cardBg
                    : (widget.isDark ? AppColors.orangeOpacity20 : AppColors.limeOpacity20),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: item.isRead ? AppColors.dark : accentColor,
                  width: item.isRead ? 2 : 2.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.dark,
                    offset: item.isRead ? const Offset(3, 3) : const Offset(4, 4),
                    blurRadius: 0,
                  ),
                ],
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Icon indicator
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: item.isRead
                          ? (widget.isDark ? AppColors.darkBg : AppColors.light)
                          : accentColor,
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.dark, width: 1.5),
                    ),
                    child: Icon(
                      _getNotificationIcon(item.type),
                      size: 18,
                      color: item.isRead ? textColor : AppColors.dark,
                    ),
                  ),
                  const SizedBox(width: 14),
                  // Content details
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              item.title,
                              style: GoogleFonts.spaceGrotesk(
                                fontWeight: FontWeight.w900,
                                fontSize: 13,
                                color: textColor,
                              ),
                            ),
                            if (!item.isRead)
                              Container(
                                width: 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: accentColor,
                                  shape: BoxShape.circle,
                                  border: Border.all(color: AppColors.dark, width: 1),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          message,
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: textColor,
                            height: 1.3,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          item.createdAt,
                          style: GoogleFonts.spaceGrotesk(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: mutedColor,
                          ),
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
    );
  }

  IconData _getNotificationIcon(String type) {
    switch (type) {
      case 'birthday':
        return Icons.cake_outlined;
      case 'new_event':
        return Icons.calendar_month_outlined;
      case 'new_post':
        return Icons.edit_note_outlined;
      case 'broadcast':
        return Icons.campaign_outlined;
      case 'approval':
        return Icons.verified_user_outlined;
      case 'like':
        return Icons.favorite_border_rounded;
      case 'comment':
        return Icons.chat_bubble_outline_rounded;
      default:
        return Icons.notifications_none_outlined;
    }
  }
}
