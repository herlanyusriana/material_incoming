import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/wo_provider.dart';

class WOListScreen extends StatefulWidget {
  const WOListScreen({super.key});

  @override
  WOListScreenState createState() => WOListScreenState();
}

class WOListScreenState extends State<WOListScreen> {
  String _filterStatus = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<WOProvider>().loadWorkOrders();
    });
  }

  Future<void> _confirmLogout() async {
    final navigator = Navigator.of(context);
    final auth = context.read<AuthProvider>();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar?'),
        content: const Text('Kamu akan keluar dari aplikasi.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Batal')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Keluar')),
        ],
      ),
    );
    if (confirmed != true) return;
    await auth.logout();
    if (!mounted) return;
    navigator.pushReplacementNamed('/login');
  }

  @override
  Widget build(BuildContext context) {
    final woProv = context.watch<WOProvider>();
    final auth = context.watch<AuthProvider>();
    if (!auth.isLoggedIn) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) Navigator.pushReplacementNamed(context, '/login');
      });
    }
    return Scaffold(
      appBar: AppBar(
        title: Text(
            _filterStatus.isEmpty ? 'WO Manual' : 'WO Manual · $_filterStatus'),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Filter status WO',
            onSelected: (val) {
              setState(() => _filterStatus = val);
              woProv.loadWorkOrders(status: val.isNotEmpty ? val : null);
            },
            itemBuilder: (_) => [
              _menuItem('Semua', '', _filterStatus),
              _menuItem('PLANNED', 'PLANNED', _filterStatus),
              _menuItem('RELEASED', 'RELEASED', _filterStatus),
              _menuItem('IN_PRODUCTION', 'IN_PRODUCTION', _filterStatus),
              _menuItem('CLOSED', 'CLOSED', _filterStatus),
            ],
          ),
          IconButton(
            tooltip: 'Muat ulang',
            icon: const Icon(Icons.refresh),
            onPressed: woProv.isLoading
                ? null
                : () => woProv.loadWorkOrders(
                    status: _filterStatus.isNotEmpty ? _filterStatus : null),
          ),
          IconButton(
            tooltip: 'Keluar',
            icon: const Icon(Icons.logout),
            onPressed: _confirmLogout,
          ),
        ],
      ),
      body: woProv.isLoading && woProv.workOrders.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : woProv.error != null
              ? _ErrorView(
                  message: woProv.error!,
                  onRetry: () => woProv.loadWorkOrders(
                      status: _filterStatus.isNotEmpty ? _filterStatus : null),
                )
              : RefreshIndicator(
                  onRefresh: () => woProv.loadWorkOrders(
                      status: _filterStatus.isNotEmpty ? _filterStatus : null),
                  child: woProv.workOrders.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [_EmptyView(filterStatus: _filterStatus)],
                        )
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          itemCount: woProv.workOrders.length,
                          itemBuilder: (ctx, i) {
                            final wo = woProv.workOrders[i];
                            final status = (wo['status'] ?? '-').toString();
                            final statusColor = status == 'RELEASED'
                                ? Colors.blue
                                : status == 'IN_PRODUCTION'
                                    ? Colors.orange
                                    : status == 'CLOSED'
                                        ? Colors.green
                                        : Colors.grey;
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: Card(
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(18),
                                  onTap: () => Navigator.pushNamed(
                                      context, '/wo-detail',
                                      arguments: wo['id']),
                                  child: Padding(
                                    padding: const EdgeInsets.all(16),
                                    child: Row(children: [
                                      Container(
                                          width: 4,
                                          height: 58,
                                          decoration: BoxDecoration(
                                              color: statusColor,
                                              borderRadius:
                                                  BorderRadius.circular(8))),
                                      const SizedBox(width: 12),
                                      Expanded(
                                          child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                            Text(wo['work_order_no'] ?? '-',
                                                style: const TextStyle(
                                                    fontSize: 16,
                                                    fontWeight:
                                                        FontWeight.w800)),
                                            const SizedBox(height: 4),
                                            Text(
                                                '${wo['part_no'] ?? '-'} · ${wo['part_name'] ?? ''}',
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                                style: TextStyle(
                                                    color:
                                                        Colors.grey.shade700)),
                                            const SizedBox(height: 8),
                                            Container(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                        horizontal: 9,
                                                        vertical: 4),
                                                decoration: BoxDecoration(
                                                    color: statusColor
                                                        .withValues(alpha: .12),
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                            20)),
                                                child: Text(status,
                                                    style: TextStyle(
                                                        color: statusColor
                                                            .shade700,
                                                        fontSize: 11,
                                                        fontWeight:
                                                            FontWeight.w800))),
                                          ])),
                                      const SizedBox(width: 10),
                                      Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.end,
                                          children: [
                                            Text('${wo['qty_target'] ?? 0}',
                                                style: const TextStyle(
                                                    fontSize: 17,
                                                    fontWeight:
                                                        FontWeight.w800)),
                                            Text(wo['uom'] ?? '',
                                                style: TextStyle(
                                                    color: Colors.grey.shade600,
                                                    fontSize: 12)),
                                            const SizedBox(height: 8),
                                            const Icon(Icons.chevron_right,
                                                size: 20),
                                          ]),
                                    ]),
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Create WO via web untuk sekarang')),
          );
        },
        child: const Icon(Icons.add),
      ),
    );
  }

  PopupMenuItem<String> _menuItem(String label, String value, String current) {
    return PopupMenuItem<String>(
      value: value,
      child: Row(
        children: [
          Text(value.isEmpty ? label : value),
          if (current == value) ...[
            const SizedBox(width: 8),
            const Icon(Icons.check, size: 18),
          ],
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({required this.message, required this.onRetry});

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

class _EmptyView extends StatelessWidget {
  final String filterStatus;

  const _EmptyView({required this.filterStatus});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inventory_2_outlined,
              size: 48, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(filterStatus.isEmpty
              ? 'Belum ada WO'
              : 'Tidak ada WO berstatus $filterStatus'),
          const SizedBox(height: 4),
          Text(
            'Tarik data terbaru lewat tombol refresh',
            style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
          ),
        ],
      ),
    );
  }
}
