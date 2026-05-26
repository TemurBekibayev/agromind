import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/main.dart';

void main() {
  testWidgets('AgroMind splash screen smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(
      const ProviderScope(
        child: AgroMindApp(),
      ),
    );

    // Verify that splash screen loading icon is shown.
    expect(find.byIcon(Icons.agriculture_rounded), findsOneWidget);
  });
}
