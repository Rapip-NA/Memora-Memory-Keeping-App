// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';
import 'package:memora_app/main.dart';

void main() {
  testWidgets('MemoraApp login screen smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const MemoraApp(initialDark: false));

    // Verify that the login page is loaded by checking if "Selamat Datang" or "Masuk Sekarang" exists
    expect(find.text('Selamat Datang'), findsOneWidget);
    expect(find.text('Masuk Sekarang'), findsOneWidget);
  });
}

