import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';

class IncomingScanScreen extends StatefulWidget {
  const IncomingScanScreen({super.key});

  @override
  IncomingScanScreenState createState() => IncomingScanScreenState();
}

class IncomingScanScreenState extends State<IncomingScanScreen> {
  final _tagCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController();
  int? _selectedArrivalId;
  int? _selectedItemId;
  Map<String, dynamic>? _selectedItem;
  List<dynamic> _departures = [];
  bool _loading = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _loadDepartures();
  }

  @override
  void dispose() {
    _tagCtrl.dispose();
    _qtyCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadDepartures() async {
    setState(() => _loading = true);
    final api = context.read<ApiService>();
    final messenger = ScaffoldMessenger.of(context);
    try {
      final departures = await api.getDepartures();
      if (!mounted) return;
      setState(() {
        _departures = departures;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      messenger.showSnackBar(
          const SnackBar(content: Text('Gagal memuat daftar departure')));
    }
  }

  void _onArrivalChanged(Map<String, dynamic>? arrival) {
    setState(() {
      _selectedArrivalId = arrival != null ? arrival['id'] as int : null;
      _selectedItemId = null;
      _selectedItem = null;
    });
  }

  void _onItemChanged(Map<String, dynamic>? item) {
    setState(() {
      _selectedItem = item;
      _selectedItemId = item != null ? item['id'] as int : null;
      if (item != null) {
        final remaining = (item['qty_remaining'] as num?)?.toDouble() ?? 0;
        if (remaining > 0) {
          _qtyCtrl.text = _formatQty(remaining);
        }
      }
    });
  }

  String _formatQty(double value) {
    return value == value.roundToDouble()
        ? value.toInt().toString()
        : value.toString();
  }

  Future<void> _scanReceive() async {
    if (_busy) return;
    final tag = _tagCtrl.text.trim();
    final qty = double.tryParse(_qtyCtrl.text) ?? 0;
    if (_selectedItemId == null || tag.isEmpty || qty <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lengkapi item, tag, dan qty > 0')),
      );
      return;
    }
    setState(() => _busy = true);
    final api = context.read<ApiService>();
    final messenger = ScaffoldMessenger.of(context);
    try {
      await api.scanReceive(_selectedItemId!, tag, qty, 'pass');
      if (!mounted) return;
      setState(() => _busy = false);
      _tagCtrl.clear();
      _qtyCtrl.clear();
      messenger.showSnackBar(const SnackBar(content: Text('Receive berhasil')));
    } catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
    }
  }

  Future<void> _openScanner() async {
    final result = await Navigator.pushNamed(context, '/qr-scan');
    if (!mounted) return;
    if (result is String && result.isNotEmpty) {
      setState(() => _tagCtrl.text = result);
    }
  }

  @override
  Widget build(BuildContext context) {
    final arrivals = _departures.cast<Map<String, dynamic>>();
    final selectedArrival =
        arrivals.where((a) => a['id'] == _selectedArrivalId).firstOrNull;
    final items =
        selectedArrival?['items']?.cast<Map<String, dynamic>>() ?? const [];

    return Scaffold(
      appBar: AppBar(title: const Text('Scan Incoming')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadDepartures,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  DropdownButtonFormField<Map<String, dynamic>>(
                    decoration: const InputDecoration(
                        labelText: 'Pilih Invoice (Departure)'),
                    items: arrivals.map((a) {
                      return DropdownMenuItem<Map<String, dynamic>>(
                        value: a,
                        child: Text(
                            '${a['invoice_no'] ?? a['arrival_no'] ?? a['id']} — ${a['vendor_name'] ?? '-'}'),
                      );
                    }).toList(),
                    initialValue: selectedArrival,
                    onChanged: _onArrivalChanged,
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<Map<String, dynamic>>(
                    key: ValueKey('item-$_selectedArrivalId'),
                    decoration: const InputDecoration(labelText: 'Pilih Item'),
                    items: items.map((item) {
                      final remaining =
                          (item['qty_remaining'] as num?)?.toDouble() ?? 0;
                      return DropdownMenuItem<Map<String, dynamic>>(
                        value: item,
                        enabled: remaining > 0,
                        child: Text(
                          '${item['part_no'] ?? '-'} — sisa ${_formatQty(remaining)} ${item['unit_goods'] ?? ''}',
                        ),
                      );
                    }).toList(),
                    initialValue: _selectedItem,
                    onChanged: _onItemChanged,
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _tagCtrl,
                    decoration: InputDecoration(
                      labelText: 'Tag / QR',
                      helperText: 'Ketik manual atau scan kamera',
                      suffixIcon: IconButton(
                        tooltip: 'Scan QR dengan kamera',
                        icon: const Icon(Icons.qr_code_scanner),
                        onPressed: _openScanner,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _qtyCtrl,
                    decoration: const InputDecoration(
                        labelText: 'Qty', helperText: 'Default sisa qty item'),
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _busy ? null : _scanReceive,
                    style: FilledButton.styleFrom(
                        minimumSize: const Size(double.infinity, 48)),
                    child: const Text('Receive & QC PASS'),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'QC status default PASS. Untuk REJECT/HOLD gunakan web.',
                    style: TextStyle(fontSize: 12),
                  ),
                ],
              ),
            ),
    );
  }
}
