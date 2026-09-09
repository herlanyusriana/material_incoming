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
          theme: ThemeData(primarySwatch: Colors.indigo, useMaterial3: true),
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
            Navigator.of(ctx, rootNavigator: true).pushNamedAndRemoveUntil('/login', (r) => false);
          }
        });
      }
    }
    return child;
  }
}
