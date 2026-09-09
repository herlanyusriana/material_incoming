import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

class DioClient {
  /// Set by main.dart; called on 401 so screens can react (e.g. clear token).
  static void Function()? onUnauthorized;

  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: const String.fromEnvironment('API_URL', defaultValue: 'https://incoming.nooneasku.online'),
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
    ),
  );

  static Dio get instance {
    _dio.interceptors.clear();
    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (DioException e, handler) {
          if (e.response?.statusCode == 401) {
            onUnauthorized?.call();
          }
          String message;
          final data = e.response?.data;
          final serverMessage = data is Map
              ? (data['message'] ?? data['error'])
              : null;
          if (e.type == DioExceptionType.connectionTimeout ||
              e.type == DioExceptionType.receiveTimeout ||
              e.type == DioExceptionType.connectionError) {
            message = 'Gagal terhubung ke server. Periksa koneksi internet.';
          } else if (serverMessage is String && serverMessage.isNotEmpty) {
            message = serverMessage;
          } else {
            switch (e.response?.statusCode) {
              case 401:
                message = 'Sesi habis. Silakan login ulang.';
                break;
              case 403:
                message = 'Akses ditolak.';
                break;
              case 404:
                message = 'Data tidak ditemukan.';
                break;
              case 422:
                message = 'Data tidak valid.';
                break;
              case 500:
                message = 'Terjadi kesalahan pada server.';
                break;
              default:
                message = 'Terjadi kesalahan (${e.response?.statusCode}).';
            }
          }
          _showError(message);
          handler.next(e);
        },
      ),
    );
    return _dio;
  }

  static void _showError(String message) {
    // Use a global key from main to show snackbar
    final context = navigatorKey.currentContext;
    if (context != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: Colors.red.shade700,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }
}

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();