import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'screens/login_screen.dart';
import 'screens/wo_list_screen.dart';
import 'screens/wo_detail_screen.dart';
import 'screens/incoming_scan_screen.dart';
import 'screens/qr_scan_screen.dart';
import 'services/api_service.dart';
import 'services/dio_client.dart';
import 'providers/auth_provider.dart';
import 'providers/wo_provider.dart';

void main() => runApp(const MyApp());

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    final apiService = ApiService();
    return MultiProvider(
      providers: [
        Provider<ApiService>.value(value: apiService),
        ChangeNotifierProvider(create: (_) => AuthProvider(api: apiService)),
        ChangeNotifierProvider(create: (_) => WOProvider(api: apiService)),
      ],
      child: _AuthListener(
        child: MaterialApp(
          title: 'Material Tracker',
          navigatorKey: navigatorKey,
          theme: ThemeData(
            useMaterial3: true,
            colorScheme:
                ColorScheme.fromSeed(seedColor: const Color(0xFFEA580C)),
            scaffoldBackgroundColor: const Color(0xFFF8FAFC),
            appBarTheme: const AppBarTheme(
              backgroundColor: Color(0xFFF8FAFC),
              foregroundColor: Color(0xFF0F172A),
              elevation: 0,
              centerTitle: false,
            ),
            inputDecorationTheme: const InputDecorationTheme(
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.all(Radius.circular(14))),
              enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: Color(0xFFE2E8F0)),
                  borderRadius: BorderRadius.all(Radius.circular(14))),
              focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: Color(0xFFEA580C), width: 2),
                  borderRadius: BorderRadius.all(Radius.circular(14))),
            ),
            cardTheme: const CardThemeData(
              color: Colors.white,
              elevation: 0,
              margin: EdgeInsets.zero,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.all(Radius.circular(18)),
                  side: BorderSide(color: Color(0xFFE2E8F0))),
            ),
          ),
          initialRoute: '/login',
          routes: {
            '/login': (context) => const LoginScreen(),
            '/wo-list': (context) => const WOListScreen(),
            '/wo-detail': (context) => const WODetailScreen(),
            '/incoming-scan': (context) => const IncomingScanScreen(),
            '/qr-scan': (context) => const QrScanScreen(),
          },
        ),
      ),
    );
  }
}

class _AuthListener extends StatelessWidget {
  final Widget child;

  const _AuthListener({required this.child});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.isLoggedIn) {
      final ctx = navigatorKey.currentContext;
      if (ctx != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          final route = ModalRoute.of(ctx)?.settings.name;
          if (route != null && route != '/login') {
            Navigator.of(ctx, rootNavigator: true)
                .pushNamedAndRemoveUntil('/login', (r) => false);
          }
        });
      }
    }
    return child;
  }
}
