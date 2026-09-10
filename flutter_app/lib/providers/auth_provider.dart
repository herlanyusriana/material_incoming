import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../services/dio_client.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService api;
  String? _token;
  String? _role;
  bool _isLoading = false;
  String? _error;

  AuthProvider({required this.api}) {
    DioClient.onUnauthorized = _forceLogout;
  }

  String? get token => _token;
  String? get role => _role;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _token != null;
  String? get error => _error;

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await api.login(username, password);
      _token = data['token'];
      final user = data['user'];
      _role = user is Map ? (user['role'] ?? 'staff') : 'staff';
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _isLoading = false;
      _error = _mapLoginError(e);
      notifyListeners();
      return false;
    } catch (e) {
      _isLoading = false;
      _error = 'Login gagal. Periksa koneksi internet.';
      notifyListeners();
      return false;
    }
  }

  String _mapLoginError(DioException e) {
    if (e.response?.statusCode == 401 || e.response?.statusCode == 422) {
      final data = e.response?.data;
      final msg = data is Map ? data['message'] : null;
      return (msg is String && msg.isNotEmpty)
          ? msg
          : 'Email atau password salah.';
    }
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.connectionError) {
      return 'Tidak bisa terhubung ke server. Periksa koneksi internet.';
    }
    return 'Login gagal. Coba lagi.';
  }

  Future<void> _forceLogout() async {
    if (_token == null) return;
    _token = null;
    _role = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    notifyListeners();
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    _token = null;
    _role = null;
    _error = null;
    notifyListeners();
  }

  Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');
    notifyListeners();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
