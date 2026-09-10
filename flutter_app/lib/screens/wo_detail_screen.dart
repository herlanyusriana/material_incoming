import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/wo_provider.dart';
import '../services/api_service.dart';

class WODetailScreen extends StatefulWidget {
  const WODetailScreen({super.key});

  @override
  WODetailScreenState createState() => WODetailScreenState();
}

class WODetailScreenState extends State<WODetailScreen> {
  final _tagCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController();
  final _goodCtrl = TextEditingController();
  final _ngCtrl = TextEditingController();
  int? _woId;
  Map<String, dynamic>? _tagInfo;
  bool _busy = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final args = ModalRoute.of(context)?.settings.arguments;
    if (args != null && args is int) {
      _woId = args;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        context.read<WOProvider>().loadDetail(args);
      });
    }
  }

  @override
  void dispose() {
    _tagCtrl.dispose();
    _qtyCtrl.dispose();
    _goodCtrl.dispose();
    _ngCtrl.dispose();
    super.dispose();
  }

  Future<bool> _confirm(String title, String message) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Batal')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Ya, Lanjut')),
        ],
      ),
    );
    return confirmed == true;
  }

  Future<void> _scanTag() async {
    if (_woId == null || _busy) return;
    final tag = _tagCtrl.text.trim();
    if (tag.isEmpty) return;
    setState(() => _busy = true);
    final api = context.read<ApiService>();
    final messenger = ScaffoldMessenger.of(context);
    try {
      final data = await api.locateTag(_woId!, tag);
      if (!mounted) return;
      setState(() => _tagInfo = data);
      final suggestion = data['suggestion'] as Map<String, dynamic>?;
      final picks = suggestion?['picks'] as List<dynamic>?;
      if (picks != null && picks.isNotEmpty) {
        _qtyCtrl.text = picks[0]['qty'].toString();
      }
    } catch (e) {
      if (!mounted) return;
      messenger
          .showSnackBar(const SnackBar(content: Text('Tag tidak ditemukan')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _allocate() async {
    if (_woId == null || _tagInfo == null || _busy) return;
    final reqId = _tagInfo!['requirement_id'] as int?;
    if (reqId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Tag tidak terhubung ke requirement WO ini')),
      );
      return;
    }
    final qty = double.tryParse(_qtyCtrl.text) ?? 0;
    if (qty <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Isi qty alokasi lebih dari 0')),
      );
      return;
    }
    setState(() => _busy = true);
    final provider = context.read<WOProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final locations = (_tagInfo!['locations'] as List<dynamic>?) ?? const [];
    final locationCode = locations.isNotEmpty
        ? locations.first['location_code']?.toString()
        : null;
    final ok = await provider.allocate(_woId!, reqId, _tagCtrl.text, qty,
        locationCode: locationCode);
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) {
      messenger.showSnackBar(const SnackBar(content: Text('Alokasi berhasil')));
      setState(() => _tagInfo = null);
      _qtyCtrl.clear();
      _tagCtrl.clear();
    } else {
      messenger.showSnackBar(const SnackBar(content: Text('Alokasi gagal')));
    }
  }

  Future<void> _postResult() async {
    if (_woId == null || _busy) return;
    final good = double.tryParse(_goodCtrl.text) ?? 0;
    final ng = double.tryParse(_ngCtrl.text) ?? 0;
    if (good <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Isi qty Good minimal 1 untuk posting hasil')),
      );
      return;
    }
    setState(() => _busy = true);
    final provider = context.read<WOProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final ok = await provider.postResult(_woId!, good, ng);
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) {
      messenger.showSnackBar(const SnackBar(content: Text('Hasil diposting')));
      _goodCtrl.clear();
      _ngCtrl.clear();
    } else {
      messenger
          .showSnackBar(const SnackBar(content: Text('Gagal posting hasil')));
    }
  }

  Future<void> _closeWO() async {
    if (_woId == null || _busy) return;
    final okConfirm = await _confirm(
        'Tutup WO?', 'WO yang sudah ditutup tidak bisa diproses lagi.');
    if (!okConfirm || !mounted) return;
    setState(() => _busy = true);
    final provider = context.read<WOProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final ok = await provider.closeWO(_woId!);
    if (!mounted) return;
    setState(() => _busy = false);
    messenger.showSnackBar(
        SnackBar(content: Text(ok ? 'WO ditutup' : 'Gagal menutup WO')));
  }

  Future<void> _releaseWO() async {
    if (_woId == null || _busy) return;
    setState(() => _busy = true);
    final provider = context.read<WOProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final ok = await provider.releaseWO(_woId!);
    if (!mounted) return;
    setState(() => _busy = false);
    messenger.showSnackBar(
        SnackBar(content: Text(ok ? 'WO direlease' : 'Gagal release WO')));
  }

  Future<void> _returnAllocation(Map<String, dynamic> a) async {
    if (_woId == null || _busy) return;
    final okConfirm = await _confirm(
      'Kembalikan alokasi?',
      'Stok ${a['tag'] ?? ''} dikembalikan ke lokasi semula.',
    );
    if (!okConfirm || !mounted) return;
    setState(() => _busy = true);
    final provider = context.read<WOProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final ok = await provider.returnAllocation(_woId!, a['id'] as int);
    if (!mounted) return;
    setState(() => _busy = false);
    messenger.showSnackBar(SnackBar(
        content: Text(ok ? 'Alokasi dikembalikan' : 'Gagal return alokasi')));
  }

  Future<void> _openScanner() async {
    final result = await Navigator.pushNamed(context, '/qr-scan');
    if (!mounted) return;
    if (result is String && result.isNotEmpty) {
      setState(() => _tagCtrl.text = result);
      await _scanTag();
    }
  }

  @override
  Widget build(BuildContext context) {
    final woProv = context.watch<WOProvider>();
    final wo = woProv.selectedWO;
    return Scaffold(
      appBar: AppBar(
          title:
              Text(wo != null ? wo['work_order_no'] ?? 'WO Detail' : 'Detail')),
      body: woProv.isLoading
          ? const Center(child: CircularProgressIndicator())
          : wo == null
              ? _DetailErrorView(
                  message: woProv.error ?? 'WO tidak ditemukan',
                  canRetry: _woId != null,
                  onRetry: () => context.read<WOProvider>().loadDetail(_woId!),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Part: ${wo['part_no'] ?? '-'}',
                        style: const TextStyle(
                            fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text('Status: ${wo['status']}'),
                      Text('Target: ${wo['qty_target']} ${wo['uom'] ?? ''}'),
                      Text('Actual: ${wo['qty_actual'] ?? 0}'),
                      const SizedBox(height: 16),
                      const Divider(),
                      const Text('Scan & Alokasi',
                          style: TextStyle(fontWeight: FontWeight.bold)),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _tagCtrl,
                              decoration: const InputDecoration(
                                  labelText: 'Tag', hintText: 'Scan QR'),
                            ),
                          ),
                          IconButton(
                            tooltip: 'Cari tag',
                            onPressed: _busy ? null : _scanTag,
                            icon: const Icon(Icons.search),
                          ),
                          IconButton(
                            tooltip: 'Scan QR dengan kamera',
                            onPressed: _busy ? null : _openScanner,
                            icon: const Icon(Icons.qr_code_scanner),
                          ),
                        ],
                      ),
                      if (_tagInfo != null) ...[
                        Card(
                          color: Colors.orange.shade50,
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(children: [
                                  const Icon(Icons.check_circle,
                                      color: Colors.orange),
                                  const SizedBox(width: 8),
                                  Expanded(
                                      child: Text(
                                          '${_tagInfo!['part_no'] ?? 'Substitute'}',
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold))),
                                  const Chip(
                                      label: Text('FIFO',
                                          style: TextStyle(fontSize: 11))),
                                ]),
                                const SizedBox(height: 6),
                                Text('TAG ${_tagInfo!['tag'] ?? '-'}'),
                                Text(
                                    'Lokasi: ${((_tagInfo!['locations'] as List<dynamic>?) ?? const []).isNotEmpty ? ((_tagInfo!['locations'] as List<dynamic>?)!.first['location_code'] ?? '-') : '-'}'),
                                Text(
                                    'Stok: ${((_tagInfo!['locations'] as List<dynamic>?) ?? const []).isNotEmpty ? ((_tagInfo!['locations'] as List<dynamic>?)!.first['qty_on_hand'] ?? 0) : 0}'),
                              ],
                            ),
                          ),
                        ),
                        TextField(
                          controller: _qtyCtrl,
                          decoration: const InputDecoration(
                            labelText: 'Qty alokasi',
                            helperText: 'Default terisi dari stok tag',
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        FilledButton(
                          onPressed: _busy ? null : _allocate,
                          child: const Text('Alokasikan'),
                        ),
                      ],
                      const SizedBox(height: 16),
                      const Divider(),
                      const Text('Posting Hasil',
                          style: TextStyle(fontWeight: FontWeight.bold)),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _goodCtrl,
                              decoration: const InputDecoration(
                                labelText: 'Good',
                                helperText: 'Qty hasil bagus',
                              ),
                              keyboardType: TextInputType.number,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: TextField(
                              controller: _ngCtrl,
                              decoration: const InputDecoration(
                                labelText: 'NG',
                                helperText: 'Qty cacah (boleh 0)',
                              ),
                              keyboardType: TextInputType.number,
                            ),
                          ),
                        ],
                      ),
                      FilledButton(
                        onPressed: _busy ? null : _postResult,
                        child: const Text('Post Hasil'),
                      ),
                      const SizedBox(height: 16),
                      const Divider(),
                      const Text('Alokasi',
                          style: TextStyle(fontWeight: FontWeight.bold)),
                      ...?wo['allocations']
                          ?.map<Widget>((a) => ListTile(
                                title: Text(a['tag'] ?? '-'),
                                subtitle: Text(
                                    '${a['qty_reserved']} — ${a['status']}'),
                                trailing: a['status'] == 'RESERVED'
                                    ? IconButton(
                                        tooltip: 'Kembalikan stok alokasi',
                                        icon: const Icon(Icons.undo,
                                            color: Colors.orange),
                                        onPressed: _busy
                                            ? null
                                            : () => _returnAllocation(a),
                                      )
                                    : null,
                              ))
                          ?.toList(),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          if (wo['status'] != 'CLOSED' &&
                              wo['status'] != 'CANCELLED')
                            Expanded(
                              child: OutlinedButton(
                                onPressed: _busy ? null : _releaseWO,
                                child: const Text('Release'),
                              ),
                            ),
                          if (wo['status'] != 'CLOSED' &&
                              wo['status'] != 'CANCELLED') ...[
                            const SizedBox(width: 8),
                            Expanded(
                              child: FilledButton(
                                onPressed: _busy ? null : _closeWO,
                                child: const Text('Close'),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
    );
  }
}

class _DetailErrorView extends StatelessWidget {
  final String message;
  final bool canRetry;
  final VoidCallback onRetry;

  const _DetailErrorView({
    required this.message,
    required this.canRetry,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 48, color: Colors.grey),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            if (canRetry)
              FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
              ),
          ],
        ),
      ),
    );
  }
}
