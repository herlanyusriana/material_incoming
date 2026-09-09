import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dio_client.dart';

class ApiService {
  final Dio _dio = DioClient.instance;

  ApiService() {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));
  }

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  // Login
  Future<Map<String, dynamic>> login(String username, String password) async {
    final response = await _dio.post('/api/auth/login', data: {
      'username': username,
      'password': password,
    });
    final data = response.data;
    if (data['token'] != null) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', data['token']);
    }
    return data;
  }

  // Incoming: list departures
  Future<List<dynamic>> getDepartures() async {
    final response = await _dio.get('/api/incoming/departures');
    return response.data['data'] ?? [];
  }

  // Incoming: scan receive
  Future<void> scanReceive(int arrivalItemId, String tag, double qty, String qcStatus) async {
    await _dio.post('/api/incoming/arrival-items/$arrivalItemId/receive', data: {
      'receive_date': DateTime.now().toIso8601String().split('T').first,
      'tag': tag,
      'qty': qty,
      'qc_status': qcStatus,
    });
  }

  // WO list
  Future<List<dynamic>> getWorkOrders({String? status}) async {
    final response = await _dio.get('/api/wo-tracking', queryParameters: {
      if (status != null && status.isNotEmpty) 'status': status,
    });
    return response.data['data'] ?? [];
  }

  // WO detail
  Future<Map<String, dynamic>> getWorkOrderDetail(int id) async {
    final response = await _dio.get('/api/wo-tracking/$id');
    return response.data['data'] ?? {};
  }

  // Locate tag
  Future<Map<String, dynamic>> locateTag(int woId, String tag) async {
    final response = await _dio.post('/api/wo-tracking/$woId/locate-tag', data: {
      'tag': tag,
    });
    return response.data;
  }

  // Allocate
  Future<void> allocate(int woId, int requirementId, String tag, double qty) async {
    await _dio.post('/api/wo-tracking/$woId/allocate', data: {
      'requirement_id': requirementId,
      'tag': tag,
      'qty': qty,
    });
  }

  // Return allocation
  Future<void> returnAllocation(int woId, int allocationId) async {
    await _dio.post('/api/wo-tracking/$woId/deallocate', data: {
      'allocation_id': allocationId,
    });
  }

  // Post result
  Future<void> postResult(int woId, double qtyGood, double qtyNg) async {
    await _dio.post('/api/wo-tracking/$woId/result', data: {
      'qty_good': qtyGood,
      'qty_ng': qtyNg,
    });
  }

  // Close WO
  Future<void> closeWO(int woId) async {
    await _dio.post('/api/wo-tracking/$woId/close');
  }

  // Release WO
  Future<void> releaseWO(int woId) async {
    await _dio.post('/api/wo-tracking/$woId/release');
  }
}
