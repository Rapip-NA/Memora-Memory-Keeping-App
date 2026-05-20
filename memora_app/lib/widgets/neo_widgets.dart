import 'package:flutter/material.dart';
import 'package:memora_app/theme/app_theme.dart';

class NeoBox extends StatelessWidget {
  final Widget child;
  final bool isDark;
  final Color backgroundColor;
  final double borderRadius;
  final EdgeInsetsGeometry padding;
  final double borderWidth;
  final double shadowOffset;

  const NeoBox({
    super.key,
    required this.child,
    required this.isDark,
    required this.backgroundColor,
    this.borderRadius = 16,
    this.padding = const EdgeInsets.all(16),
    this.borderWidth = 2.0,
    this.shadowOffset = 6.0,
  });

  @override
  Widget build(BuildContext context) {
    final borderColor = isDark ? Colors.white : AppColors.dark;
    final shadowColor = isDark ? AppColors.orange : AppColors.dark;

    return Container(
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(borderRadius),
        border: Border.all(
          color: borderColor,
          width: borderWidth,
        ),
        boxShadow: [
          BoxShadow(
            color: shadowColor,
            offset: Offset(0, shadowOffset),
            blurRadius: 0,
          ),
        ],
      ),
      padding: padding,
      child: child,
    );
  }
}

class NeoButton extends StatefulWidget {
  final Widget child;
  final bool isDark;
  final Color backgroundColor;
  final Color textColor;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;
  final double borderRadius;
  final double shadowOffset;

  const NeoButton({
    super.key,
    required this.child,
    required this.isDark,
    required this.backgroundColor,
    required this.textColor,
    this.padding = const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
    this.onTap,
    this.borderRadius = 16,
    this.shadowOffset = 4.0,
  });

  @override
  State<NeoButton> createState() => _NeoButtonState();
}

class _NeoButtonState extends State<NeoButton> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    final borderColor = widget.isDark ? Colors.white : AppColors.dark;
    final shadowColor = widget.isDark ? AppColors.orange : AppColors.dark;

    return GestureDetector(
      onTapDown: (_) => setState(() => _isPressed = true),
      onTapUp: (_) {
        setState(() => _isPressed = false);
        widget.onTap?.call();
      },
      onTapCancel: () => setState(() => _isPressed = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 100),
        transform: Matrix4.translationValues(
          0,
          _isPressed ? widget.shadowOffset : 0,
          0,
        ),
        decoration: BoxDecoration(
          color: widget.backgroundColor,
          borderRadius: BorderRadius.circular(widget.borderRadius),
          border: Border.all(
            color: borderColor,
            width: 2,
          ),
          boxShadow: _isPressed
              ? []
              : [
                  BoxShadow(
                    color: shadowColor,
                    offset: Offset(0, widget.shadowOffset),
                    blurRadius: 0,
                  ),
                ],
        ),
        padding: widget.padding,
        child: widget.child,
      ),
    );
  }
}
