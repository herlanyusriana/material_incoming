import 'package:flutter/material.dart';
import '../services/api_service.dart';

class WOProvider extends ChangeNotifier {
  final ApiService api;
  List<dynamic> _workOrders = [];
  Map<String, dynamic>? _selectedWO;
  bool _isLoading = false;
  String? _error;

  WOProvider({required this.api});

  List<dynamic> get workOrders => _workOrders;
  Map<String, dynamic>? get selectedWO => _selectedWO;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadWorkOrders({String? status}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      _workOrders = await api.getWorkOrders(status: status);
    } catch (e) {
      _workOrders = [];
      _error = 'Gagal memuat daftar WO. Periksa koneksi lalu coba lagi.';
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadDetail(int id) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      _selectedWO = await api.getWorkOrderDetail(id);
    } catch (e) {
      _selectedWO = null;
      _error = 'Gagal memuat detail WO.';
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> allocate(int woId, int reqId, String tag, double qty) async {
    try {
      await api.allocate(woId, reqId, tag, qty);
      await loadDetail(woId);
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<bool> postResult(int woId, double good, double ng) async {
    try {
      await api.postResult(woId, good, ng);
      await loadDetail(woId);
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<bool> closeWO(int woId) async {
    try {
      await api.closeWO(woId);
      await loadDetail(woId);
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<bool> releaseWO(int woId) async {
    try {
      await api.releaseWO(woId);
      await loadDetail(woId);
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<bool> returnAllocation(int woId, int allocId) async {
    try {
      await api.returnAllocation(woId, allocId);
      await loadDetail(woId);
      return true;
    } catch (e) {
      return false;
    }
  }
}