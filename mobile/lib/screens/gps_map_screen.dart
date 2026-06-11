import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../providers/providers.dart';
import 'dart:async';
import 'dart:convert';

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
  bool _isLoadingLocation = false;
  String? _errorMessage;
  final String _selectedMapLayer = 'satellite';
  bool _isFirstFetch = true;
  bool _isCardCollapsed = false;

  final MapController _mapController = MapController();
  final List<Marker> _markers = [];
  final List<Polyline> _polylines = [];
  final List<Polygon> _polygons = [];

  String get _currentMapUrl {
    switch (_selectedMapLayer) {
      case 'satellite':
        return 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
      case 'terrain':
        return 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}';
      case 'standard':
      default:
        return 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    }
  }

  @override
  void initState() {
    super.initState();
    // Har 5 soniyada transport koordinatalarini yangilab turamiz
    _timer = Timer.periodic(const Duration(seconds: 5), (timer) {
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
    setState(() {
      _isLoadingLocation = true;
      _errorMessage = null;
    });
    try {
      final res = await api.getVehicleLocation(id);
      if (res.data['status'] == 'success' && mounted) {
        final loc = res.data['location'];
        final lat = double.tryParse('${loc['latitude']}') ?? 41.38;
        final lng = double.tryParse('${loc['longitude']}') ?? 69.45;

        setState(() {
          _currentLocation = res.data;
          _isLoadingLocation = false;

          _markers.clear();
          _markers.add(
            Marker(
              point: LatLng(lat, lng),
              width: 50,
              height: 50,
              child: GestureDetector(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        '${res.data['vehicle_name'] ?? 'Texnika'} (${res.data['plate_number'] ?? ''}): Tezlik: ${loc['speed']} km/h | Yoqilg\'i: ${loc['fuel_level']}%',
                      ),
                      duration: const Duration(seconds: 4),
                    ),
                  );
                },
                child: const Icon(
                  Icons.location_on_rounded,
                  color: Colors.orange,
                  size: 45,
                ),
              ),
            ),
          );

          _polygons.clear();
          if (res.data['geofences'] != null) {
            final gfs = res.data['geofences'] as List<dynamic>;
            for (var gf in gfs) {
              var coords = gf['coordinates'];
              if (coords != null && coords is List) {
                // Normalize 3D coordinates arrays to 2D
                if (coords.isNotEmpty && coords[0] is List && coords[0].isNotEmpty && coords[0][0] is List) {
                  coords = coords[0];
                }

                final List<LatLng> polygonPoints = [];
                for (var coord in coords) {
                  if (coord is List && coord.length >= 2) {
                    final pLat = double.tryParse('${coord[0]}');
                    final pLng = double.tryParse('${coord[1]}');
                    if (pLat != null && pLng != null) {
                      polygonPoints.add(LatLng(pLat, pLng));
                    }
                  }
                }
                if (polygonPoints.isNotEmpty) {
                  _polygons.add(
                    Polygon(
                      points: polygonPoints,
                      color: const Color(0x3310B981),
                      borderColor: const Color(0xFF10B981),
                      borderStrokeWidth: 2.5,
                    ),
                  );
                }
              }
            }
          }
        });

        try {
          if (_isFirstFetch) {
            _mapController.move(LatLng(lat, lng), 15.0);
            _isFirstFetch = false;
          }
        } catch (_) {}
      } else {
        setState(() {
          _errorMessage = res.data['message'] ?? 'Xatolik yuz berdi';
          _isLoadingLocation = false;
        });
      }
    } catch (e) {
      if (mounted) {
        String msg = 'Ulanish xatoligi yuz berdi';
        if (e is DioException && e.response?.data != null) {
          final data = e.response!.data;
          if (data is Map && data.containsKey('message')) {
            msg = data['message'];
          } else if (data is String) {
            try {
              final parsed = jsonDecode(data);
              if (parsed is Map && parsed.containsKey('message')) {
                msg = parsed['message'];
              }
            } catch (_) {
              if (data.isNotEmpty) msg = data;
            }
          }
        }
        setState(() {
          _errorMessage = msg;
          _isLoadingLocation = false;
        });
      }
    }
  }

  Future<void> _fetchHistory(int id) async {
    setState(() {
      _isLoadingHistory = true;
      _history = [];
      _polylines.clear();
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getVehicleHistory(id);
      if (res.data['status'] == 'success' && mounted) {
        final historyList = res.data['history'] as List<dynamic>;
        final List<LatLng> points = [];

        for (var h in historyList) {
          final lat = double.tryParse('${h['latitude']}');
          final lng = double.tryParse('${h['longitude']}');
          if (lat != null && lng != null) {
            points.add(LatLng(lat, lng));
          }
        }

        setState(() {
          _history = historyList;
          _isLoadingHistory = false;

          if (points.isNotEmpty) {
            _polylines.add(
              Polyline(
                points: points,
                color: Colors.blue,
                strokeWidth: 5,
              ),
            );
          }
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
      _errorMessage = null;
      _markers.clear();
      _polylines.clear();
      _polygons.clear();
      _isFirstFetch = true;
      _isCardCollapsed = false;
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
      ),
      body: Column(
        children: [
          // 1. Vehicle Selector Row
          _buildVehicleSelector(vehiclesState),

          // 2. Main Content Stack
          Expanded(
            child: _selectedVehicleId == null
                ? _buildNoSelectionPrompt()
                : Stack(
                    children: [
                      // Map Background
                      _buildMapFallbackView(),

                      // Map Controls Column (Recenter, Zoom In, Zoom Out)
                      if (_currentLocation != null)
                        Positioned(
                          top: 16,
                          right: 16,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              FloatingActionButton.small(
                                heroTag: 'recenter_gps_btn',
                                backgroundColor: const Color(0xFF1A3C2A),
                                foregroundColor: Colors.white,
                                onPressed: () {
                                  final loc = _currentLocation!['location'];
                                  if (loc != null) {
                                    final lat = double.tryParse('${loc['latitude']}') ?? 41.38;
                                    final lng = double.tryParse('${loc['longitude']}') ?? 69.45;
                                    _mapController.move(LatLng(lat, lng), _mapController.camera.zoom);
                                  }
                                },
                                child: const Icon(Icons.my_location_rounded),
                              ),
                              const SizedBox(height: 12),
                              FloatingActionButton.small(
                                heroTag: 'zoom_in_btn',
                                backgroundColor: const Color(0xFF1A3C2A),
                                foregroundColor: Colors.white,
                                onPressed: () {
                                  try {
                                    final double currentZoom = _mapController.camera.zoom;
                                    final double newZoom = (currentZoom + 1.0).clamp(3.0, 18.0);
                                    _mapController.move(_mapController.camera.center, newZoom);
                                  } catch (_) {}
                                },
                                child: const Icon(Icons.add_rounded),
                              ),
                              const SizedBox(height: 12),
                              FloatingActionButton.small(
                                heroTag: 'zoom_out_btn',
                                backgroundColor: const Color(0xFF1A3C2A),
                                foregroundColor: Colors.white,
                                onPressed: () {
                                  try {
                                    final double currentZoom = _mapController.camera.zoom;
                                    final double newZoom = (currentZoom - 1.0).clamp(3.0, 18.0);
                                    _mapController.move(_mapController.camera.center, newZoom);
                                  } catch (_) {}
                                },
                                child: const Icon(Icons.remove_rounded),
                              ),
                            ],
                          ),
                        ),

                      // Loading indicator overlay (only when there's no data yet)
                      if (_isLoadingLocation && _currentLocation == null)
                        const Center(
                          child: CircularProgressIndicator(
                            color: Color(0xFF1A3C2A),
                          ),
                        ),

                      // Error Overlay
                      if (_errorMessage != null)
                        _buildErrorOverlay(_errorMessage!),

                      // Live Floating Telemetry Card
                      if (_currentLocation != null)
                        _buildFloatingTelemetryCard(),
                    ],
                  ),
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
    final hasLoc = _currentLocation != null;
    final loc = hasLoc ? _currentLocation!['location'] : null;
    final lat = loc != null ? (double.tryParse('${loc['latitude']}') ?? 41.311081) : 41.311081;
    final lng = loc != null ? (double.tryParse('${loc['longitude']}') ?? 69.240562) : 69.240562;

    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: LatLng(lat, lng),
        initialZoom: 14,
        maxZoom: 18.0,
        minZoom: 3.0,
      ),
      children: [
        TileLayer(
          urlTemplate: _currentMapUrl,
          userAgentPackageName: 'com.temurbekibayev.agromind',
          maxZoom: 18.0,
        ),
        PolylineLayer(
          polylines: _polylines,
        ),
        PolygonLayer(
          polygons: _polygons,
        ),
        MarkerLayer(
          markers: _markers,
        ),
      ],
    );
  }

  Widget _buildErrorOverlay(String message) {
    return Positioned(
      top: 16,
      left: 16,
      right: 80, // Leave room for floating layer toggle button!
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.amber[900]!.withOpacity(0.95),
          borderRadius: BorderRadius.circular(12),
          boxShadow: const [
            BoxShadow(
              color: Colors.black26,
              blurRadius: 8,
              offset: Offset(0, 4),
            ),
          ],
          border: Border.all(color: Colors.amber[700]!),
        ),
        child: Row(
          children: [
            const Icon(Icons.signal_wifi_off_rounded, color: Colors.white, size: 24),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    message,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 2),
                  const Text(
                    'Texnika yoqilgach yoki GPS signal yuborilgach, yangilanadi.',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }


  Widget _buildFloatingTelemetryCard() {
    if (_currentLocation == null) return const SizedBox.shrink();

    final loc = _currentLocation!['location'];
    final isInside = loc['is_inside_geofence'] == 1;
    final vehicleName = _currentLocation!['vehicle_name'] ?? 'Texnika';
    final plateNumber = _currentLocation!['plate_number'] ?? '';
    final speed = loc['speed'] ?? 0;
    final fuel = loc['fuel_level'] ?? 0;
    final lat = loc['latitude'] ?? 0.0;
    final lng = loc['longitude'] ?? 0.0;

    if (_isCardCollapsed) {
      return Positioned(
        left: 16,
        right: 16,
        bottom: 16,
        child: GestureDetector(
          onTap: () {
            setState(() {
              _isCardCollapsed = false;
            });
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B).withOpacity(0.95),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
              border: Border.all(
                color: Colors.white.withOpacity(0.1),
                width: 1,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      const Icon(Icons.agriculture_rounded, color: Colors.greenAccent, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '$vehicleName ($plateNumber)',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: const BoxDecoration(
                        color: Color(0xFF34D399),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      '$speed km/h',
                      style: const TextStyle(
                        color: Color(0xFF34D399),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.white.withOpacity(0.1)),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Batafsil',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(width: 4),
                          Icon(Icons.keyboard_double_arrow_up_rounded, color: Colors.white, size: 16),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Positioned(
      left: 16,
      right: 16,
      bottom: 16,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B).withOpacity(0.95), // Premium Slate Dark Glassmorphism
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.3),
              blurRadius: 10,
              offset: const Offset(0, 5),
            ),
          ],
          border: Border.all(
            color: Colors.white.withOpacity(0.1),
            width: 1,
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Geofence alert banner
            if (!isInside) ...[
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.red[900]!.withOpacity(0.8),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red[700]!),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.gpp_bad_rounded, color: Colors.redAccent, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'DIQQAT: Hududdan tashqarida!',
                        style: TextStyle(
                          color: Colors.red[100],
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            // Tap Header to Collapse (Grab Handle + Header Row)
            GestureDetector(
              onTap: () {
                setState(() {
                  _isCardCollapsed = true;
                });
              },
              behavior: HitTestBehavior.opaque,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Top Grab Handle to signal swipe/collapse usability (prominent Yig'ish pill badge)
                  Center(
                    child: Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white.withOpacity(0.08)),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.keyboard_double_arrow_down_rounded,
                            color: Colors.white70,
                            size: 14,
                          ),
                          SizedBox(width: 4),
                          Text(
                            'Yig\'ish',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Vehicle Name and Status Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              vehicleName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              plateNumber,
                              style: TextStyle(
                                color: Colors.grey[400],
                                fontSize: 12,
                                fontFamily: 'monospace',
                              ),
                            ),
                          ],
                        ),
                      ),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF10B981).withOpacity(0.15),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Container(
                                  width: 8,
                                  height: 8,
                                  decoration: const BoxDecoration(
                                    color: Color(0xFF34D399),
                                    shape: BoxShape.circle,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                const Text(
                                  'Online',
                                  style: TextStyle(
                                    color: Color(0xFF34D399),
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          // Styled circular chevron collapse button (more prominent double chevron)
                          Container(
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.12),
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white.withOpacity(0.1)),
                            ),
                            child: IconButton(
                              icon: const Icon(Icons.keyboard_double_arrow_down_rounded, color: Colors.white, size: 22),
                              onPressed: () {
                                setState(() {
                                  _isCardCollapsed = true;
                                });
                              },
                              constraints: const BoxConstraints(),
                              padding: const EdgeInsets.all(6),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const Divider(color: Colors.white12, height: 20),

            // Live Telemetry Grid
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Speed Column
                Expanded(
                  child: _buildMetricTile(
                    Icons.speed_rounded,
                    'Tezlik',
                    '$speed km/h',
                    Colors.orangeAccent,
                  ),
                ),
                // Fuel Column
                Expanded(
                  child: _buildMetricTile(
                    Icons.local_gas_station_rounded,
                    'Yoqilg\'i',
                    '$fuel%',
                    Colors.cyanAccent,
                  ),
                ),
                // Coordinates Column
                Expanded(
                  child: _buildMetricTile(
                    Icons.location_on_rounded,
                    'Joylashuv',
                    '${lat.toStringAsFixed(4)}, ${lng.toStringAsFixed(4)}',
                    Colors.pinkAccent,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1A3C2A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                    onPressed: () => _showHistoryDialog(context),
                    icon: const Icon(Icons.history_rounded, size: 18),
                    label: const Text(
                      'Yo\'nalish tarixi (24s)',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricTile(IconData icon, String label, String value, Color iconColor) {
    return Column(
      children: [
        Icon(icon, color: iconColor, size: 24),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(color: Colors.grey[400], fontSize: 11),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 13,
            fontWeight: FontWeight.bold,
            fontFamily: 'monospace',
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  void _showHistoryDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              height: MediaQuery.of(context).size.height * 0.6,
              decoration: const BoxDecoration(
                color: Color(0xFF1E293B),
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Grab handle bar
                  Center(
                    child: Container(
                      margin: const EdgeInsets.symmetric(vertical: 12),
                      width: 40,
                      height: 5,
                      decoration: BoxDecoration(
                        color: Colors.grey[600],
                        borderRadius: BorderRadius.circular(2.5),
                      ),
                    ),
                  ),
                  // Title and Close
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          '24 Soatlik Harakat Tarixi',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, color: Colors.white70),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                  ),
                  const Divider(color: Colors.white12),
                  // History list
                  Expanded(
                    child: _isLoadingHistory
                        ? const Center(child: CircularProgressIndicator(color: Colors.greenAccent))
                        : _history.isEmpty
                            ? const Center(
                                child: Text(
                                  'Tarixiy ma\'lumotlar topilmadi.',
                                  style: TextStyle(color: Colors.white38, fontSize: 14),
                                ),
                              )
                            : ListView.builder(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                itemCount: _history.length,
                                itemBuilder: (context, index) {
                                  final h = _history[index];
                                  final recordedAt = h['recorded_at'] ?? '';
                                  final lat = h['latitude'] ?? 0.0;
                                  final lng = h['longitude'] ?? 0.0;
                                  final speed = h['speed'] ?? 0;
                                  final fuel = h['fuel_level'] ?? 0;

                                  return Container(
                                    padding: const EdgeInsets.all(12),
                                    margin: const EdgeInsets.only(bottom: 8),
                                    decoration: BoxDecoration(
                                      color: Colors.black26,
                                      borderRadius: BorderRadius.circular(10),
                                      border: Border.all(color: Colors.white10),
                                    ),
                                    child: Row(
                                      children: [
                                        // Number badge
                                        Container(
                                          width: 28,
                                          height: 28,
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF1A3C2A),
                                            shape: BoxShape.circle,
                                            border: Border.all(color: Colors.white24),
                                          ),
                                          alignment: Alignment.center,
                                          child: Text(
                                            '${index + 1}',
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 11,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        // Details
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                'Lat: $lat | Lng: $lng',
                                                style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 12,
                                                  fontWeight: FontWeight.bold,
                                                  fontFamily: 'monospace',
                                                ),
                                              ),
                                              const SizedBox(height: 2),
                                              Text(
                                                recordedAt,
                                                style: TextStyle(
                                                  color: Colors.grey[400],
                                                  fontSize: 10,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                        // Speed & Fuel
                                        Column(
                                          crossAxisAlignment: CrossAxisAlignment.end,
                                          children: [
                                            Text(
                                              '$speed km/h',
                                              style: const TextStyle(
                                                color: Colors.orangeAccent,
                                                fontSize: 12,
                                                fontWeight: FontWeight.bold,
                                                fontFamily: 'monospace',
                                              ),
                                            ),
                                            const SizedBox(height: 2),
                                            Text(
                                              'Yoqilg\'i: $fuel%',
                                              style: const TextStyle(
                                                color: Colors.cyanAccent,
                                                fontSize: 11,
                                                fontFamily: 'monospace',
                                              ),
                                            ),
                                          ],
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
          },
        );
      },
    );
  }
}
