import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:material_tracker/providers/auth_provider.dart';
import 'package:material_tracker/providers/wo_provider.dart';
import 'package:material_tracker/services/api_service.dart';
import 'package:material_tracker/screens/login_screen.dart';

void main() {
  testWidgets('Login screen renders form', (WidgetTester tester) async {
    final api = ApiService();
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          Provider<ApiService>.value(value: api),
          ChangeNotifierProvider(create: (_) => AuthProvider(api: api)),
          ChangeNotifierProvider(create: (_) => WOProvider(api: api)),
        ],        child: const MaterialApp(home: LoginScreen()),
      ),
    );

    expect(find.text('Material Tracker'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
  });
}
