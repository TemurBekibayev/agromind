import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';
import 'dart:async';

class GpsMapScreen extends ConsumerStatefulWidget {
  const GpsMapScreen({super.key});

  @override
  ConsumerState<GpsMapScreen> createState() => _GpsMapScreenState();
}

class _GpsMapScreenState extends ConsumerState<GpsMapScreen> {
  int? _selectedVehicleId;
  Timer? _timer;
  List<dynamic> _history = [];
  Map<String, dynamic>? _currentLocation;
  bool _isLoadingHistory = false;
  bool _showMockTerminal = true; // Simulyatsiya konsoli holati

  @override
  void initState() {
    super.initState();
    // Har 10 soniyada transport koordinatalarini yangilab turamiz
    _timer = Timer.periodic(const Duration(seconds: 10), (timer) {
      if (_selectedVehicleId != null) {
        _fetchLocation(_selectedVehicleId!);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _fetchLocation(int id) async {
    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getVehicleLocation(id);
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _currentLocation = res.data;
        });
      }
    } catch (_) {}
  }

  Future<void> _fetchHistory(int id) async {
    setState(() {
      _isLoadingHistory = true;
      _history = [];
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getVehicleHistory(id);
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _history = res.data['history'] as List<dynamic>;
          _isLoadingHistory = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoadingHistory = false;
        });
      }
    }
  }

  void _onVehicleSelected(int id) {
    setState(() {
      _selectedVehicleId = id;
      _currentLocation = null;
      _history = [];
    });
    _fetchLocation(id);
    _fetchHistory(id);
  }

  @override
  Widget build(BuildContext context) {
    final vehiclesState = ref.watch(vehiclesProvider);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: const Text('GPS Texnika Xaritasi'),
        actions: [
          IconButton(
            icon: Icon(_showMockTerminal ? Icons.map_rounded : Icons.terminal_rounded),
            tooltip: _showMockTerminal ? 'Xaritani ko\'rish' : 'Konsolni ko\'rish',
            onPressed: () {
              setState(() {
                _showMockTerminal = !_showMockTerminal;
              });
            },
          )
        ],
      ),
      body: Column(
        children: [
          // 1. Vehicle Selector Row
          _buildVehicleSelector(vehiclesState),

          // 2. Main Content (Map placeholder or Telemetry Console)
          Expanded(
            child: _selectedVehicleId == null
                ? _buildNoSelectionPrompt()
                : (_showMockTerminal ? _buildTelemetryConsole() : _buildMapFallbackView()),
          ),
        ],
      ),
    );
  }

  Widget _buildVehicleSelector(AsyncValue<List<dynamic>> vehiclesState) {
    return vehiclesState.when(
      data: (vehicles) {
        if (vehicles.isEmpty) {
          return const Padding(
            padding: EdgeInsets.all(16.0),
            child: Text('Hozircha texnikalar mavjud emas.'),
          );
        }

        return Container(
          height: 70,
          decoration: const BoxDecoration(
            color: Colors.white,
            border: Border(bottom: BorderSide(color: Colors.black12)),
          ),
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            itemCount: vehicles.length,
            itemBuilder: (context, index) {
              final v = vehicles[index];
              final isSelected = v['id'] == _selectedVehicleId;
              final isOnline = v['status_label'] == 'online';

              return Padding(
                padding: const EdgeInsets.only(right: 10),
                child: ChoiceChip(
                  label: Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          color: isOnline ? Colors.green : Colors.red,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text('${v['name']} (${v['plate_number']})'),
                    ],
                  ),
                  selected: isSelected,
                  onSelected: (_) => _onVehicleSelected(v['id']),
                  selectedColor: const Color(0xFF1A3C2A).withOpacity(0.15),
                  labelStyle: TextStyle(
                    color: isSelected ? const Color(0xFF1A3C2A) : Colors.black87,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                    side: BorderSide(
                      color: isSelected ? const Color(0xFF1A3C2A) : Colors.grey[300]!,
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
      error: (_, __) => const SizedBox(),
      loading: () => const LinearProgressIndicator(),
    );
  }

  Widget _buildNoSelectionPrompt() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.directions_car_filled_rounded, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Kuzatish uchun texnikani tanlang',
            style: TextStyle(fontSize: 16, color: Colors.grey[600], fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 8),
          Text(
            'Yuqoridagi ro\'yxatdan birini bosing',
            style: TextStyle(fontSize: 12, color: Colors.grey[400]),
          ),
        ],
      ),
    );
  }

  Widget _buildMapFallbackView() {
    // Mobil telefonda Google Maps API yo'qligi sababli simulyatsiya qilingan xarita ko'rinishi
    return Container(
      color: const Color(0xFF0F172A),
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.map_rounded, color: Colors.cyan, size: 80),
          const SizedBox(height: 20),
          const Text(
            'Google Xaritalar Integratsiyasi',
            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Text(
              'Haqiqiy qurilmalarda Google Maps ishga tushadi. Mahalliy sinov rejimi uchun o\'ng burchakdagi Terminal tugmasini bosing va koordinatalar simulyatsiyasini kuzating.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[400], fontSize: 12, height: 1.5),
            ),
          ),
          const SizedBox(height: 30),
          ElevatedButton.icon(
            onPressed: () {
              setState(() {
                _showMockTerminal = true;
              });
            },
            icon: const Icon(Icons.terminal_rounded),
            label: const Text('Simulyatsiya Konsolini Ko\'rish'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.cyan,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildTelemetryConsole() {
    if (_currentLocation == null) {
      return const Center(child: CircularProgressIndicator());
    }

    final loc = _currentLocation!['location'];
    final isInside = loc['is_inside_geofence'] == 1;

    return Container(
      color: Colors.grey[900],
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header with Geofence alert
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isInside ? Colors.green[950] : Colors.red[950],
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: isInside ? Colors.green[800]! : Colors.red[800]!),
            ),
            child: Row(
              children: [
                Icon(
                  isInside ? Icons.verified_user_rounded : Icons.gpp_bad_rounded,
                  color: isInside ? Colors.greenAccent : Colors.redAccent,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isInside ? 'XAVFSIZLIK: Hudud ichida' : 'DIQQAT: Hududdan tashqarida!',
                        style: TextStyle(
                          color: isInside ? Colors.greenAccent : Colors.redAccent,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                      Text(
                        isInside
                            ? 'Texnika ruxsat etilgan dala maydonida harakatlanmoqda.'
                            : 'Texnika ruxsat etilmagan hududga chiqdi! SMS yuborildi.',
                        style: const TextStyle(color: Colors.white70, fontSize: 11),
                      ),
                    ],
                  ),
                )
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Telemetry board
          const Text(
            'TELEMETRIYA MA\'LUMOTLARI',
            style: TextStyle(color: Colors.white54, fontSize: 11, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          _buildTelemetryRow('Latitude', '${loc['latitude']}'),
          _buildTelemetryRow('Longitude', '${loc['longitude']}'),
          _buildTelemetryRow('Tezlik', '${loc['speed']} km/s'),
          _buildTelemetryRow('Yoqilg\'i qoldig\'i', '${loc['fuel_level']}%'),
          _buildTelemetryRow('Vaqt', '${loc['recorded_at']}'),
          const SizedBox(height: 16),

          // Coordinate logs (last 24h history)
          const Text(
            'OXIRGI GPS TAYANCH NUQTALARI (24 SOAT)',
            style: TextStyle(color: Colors.white54, fontSize: 11, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _isLoadingHistory
                ? const Center(child: CircularProgressIndicator())
                : _history.isEmpty
                    ? const Center(
                        child: Text(
                          'Tarixiy ma\'lumot topilmadi.',
                          style: TextStyle(color: Colors.white38, fontSize: 12),
                        ),
                      )
                    : ListView.builder(
                        itemCount: _history.length,
                        itemBuilder: (context, index) {
                          final h = _history[index];
                          return Container(
                            padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
                            margin: const EdgeInsets.only(bottom: 4),
                            decoration: BoxDecoration(
                              color: Colors.black26,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  '#${index + 1} | Lat: ${h['latitude']} Lng: ${h['longitude']}',
                                  style: const TextStyle(color: Colors.white70, fontSize: 10, fontFamily: 'monospace'),
                                ),
                                Text(
                                  '${h['speed']} km/s | ${h['fuel_level']}%',
                                  style: const TextStyle(color: Colors.cyanAccent, fontSize: 10, fontFamily: 'monospace'),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildTelemetryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.white70, fontSize: 13)),
          Text(
            value,
            style: const TextStyle(color: Colors.greenAccent, fontSize: 13, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
          ),
        ],
      ),
    );
  }
}
