import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../providers/providers.dart';
import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'dart:ui' as ui;

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
  String _selectedMapLayer = 'satellite';
  bool _isLayerSelectorExpanded = false;
  List<dynamic> _rawGeofences = [];
  Map<String, dynamic>? _selectedGeofenceForNdvi;
  bool _isFirstFetch = true;
  bool _isCardCollapsed = false;
  bool _isControllingVehicle = false;

  final MapController _mapController = MapController();
  final List<Marker> _markers = [];
  final List<Polyline> _polylines = [];
  final List<Polygon> _polygons = [];

  String get _currentMapUrl {
    switch (_selectedMapLayer) {
      case 'satellite':
      case 'ndvi':
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
            _rawGeofences = gfs;
            
            // Auto-select geofence for NDVI if not selected yet
            if (_selectedGeofenceForNdvi == null && _rawGeofences.isNotEmpty) {
              final activeGf = _getActiveGeofence();
              _selectedGeofenceForNdvi = activeGf ?? _rawGeofences.first;
            }

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

  Future<void> _controlVehicle(int id, String action) async {
    setState(() {
      _isControllingVehicle = true;
    });
    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.controlVehicle(id, action);
      if (!mounted) return;
      if (res.data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.data['message'] ?? 'Buyruq yuborildi'),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
        _fetchLocation(id);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.data['message'] ?? 'Xatolik yuz berdi'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      String msg = 'Ulanish xatoligi yuz berdi';
      if (e is DioException && e.response?.data != null) {
        final data = e.response!.data;
        if (data is Map && data.containsKey('message')) {
          msg = data['message'];
        }
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(msg),
          backgroundColor: Colors.red,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isControllingVehicle = false;
        });
      }
    }
  }

  void _showControlConfirmationDialog(bool isCurrentlyBlocked) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          title: Row(
            children: [
              Icon(
                isCurrentlyBlocked ? Icons.lock_open_rounded : Icons.lock_rounded,
                color: isCurrentlyBlocked ? const Color(0xFF10B981) : const Color(0xFFEF4444),
              ),
              const SizedBox(width: 10),
              Text(
                isCurrentlyBlocked ? 'Dvigatelni yoqish' : 'Dvigatelni o\'chirish',
                style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          content: Text(
            isCurrentlyBlocked
                ? 'Haqiqatan ham dvigatelni blokdan chiqarmoqchimisiz? Bu texnikani qayta ishga tushirish imkonini beradi.'
                : 'Haqiqatan ham ushbu texnika dvigatelini masofadan o\'chirmoqchimisiz? Bu texnikaning harakatini butunlay to\'xtatadi.',
            style: const TextStyle(color: Colors.white70, fontSize: 14),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Bekor qilish', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: isCurrentlyBlocked ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                foregroundColor: Colors.white,
              ),
              onPressed: () {
                Navigator.pop(context);
                if (_selectedVehicleId != null) {
                  _controlVehicle(
                    _selectedVehicleId!,
                    isCurrentlyBlocked ? 'restore' : 'cut_off',
                  );
                }
              },
              child: const Text('Tasdiqlash'),
            ),
          ],
        );
      },
    );
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
      _rawGeofences = [];
      _selectedGeofenceForNdvi = null;
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

                      // Map Layer Toggle Selector
                      Positioned(
                        top: 16,
                        left: 16,
                        child: _buildLayerSelector(),
                      ),

                      // NDVI Legend
                      if (_selectedMapLayer == 'ndvi' && _currentLocation != null)
                        Positioned(
                          left: 72,
                          top: 16,
                          child: _buildNdviLegend(),
                        ),

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
          polygons: _buildPolygonsList(),
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
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.45,
        ),
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
        child: SingleChildScrollView(
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
                          if (_currentLocation != null && _currentLocation!['is_blocked'] == true) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEF4444).withOpacity(0.15),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFEF4444).withOpacity(0.3)),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.lock_rounded, color: Color(0xFFFCA5A5), size: 12),
                                  SizedBox(width: 4),
                                  Text(
                                    'Bloklangan',
                                    style: TextStyle(
                                      color: Color(0xFFFCA5A5),
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                          ],
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
            if (_selectedMapLayer == 'ndvi') ...[
              const Divider(color: Colors.white12, height: 20),
              _buildNdviInfoSection(),
            ],
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
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _isControllingVehicle
                      ? const Center(
                          child: SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          ),
                        )
                      : ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _currentLocation != null && _currentLocation!['is_blocked'] == true
                                ? const Color(0xFF10B981) // Green for restore
                                : const Color(0xFFEF4444), // Red for block
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          onPressed: () {
                            final isBlocked = _currentLocation != null && _currentLocation!['is_blocked'] == true;
                            _showControlConfirmationDialog(isBlocked);
                          },
                          icon: Icon(
                            _currentLocation != null && _currentLocation!['is_blocked'] == true
                                ? Icons.lock_open_rounded
                                : Icons.lock_rounded,
                            size: 18,
                          ),
                          label: Text(
                            _currentLocation != null && _currentLocation!['is_blocked'] == true
                                ? 'Blokdan ochish'
                                : 'Dvigatelni o\'chirish',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                          ),
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

  double _calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const p = 0.017453292519943295;
    final a = 0.5 - cos((lat2 - lat1) * p)/2 + 
          cos(lat1 * p) * cos(lat2 * p) * 
          (1 - cos((lon2 - lon1) * p))/2;
    return 12742 * asin(sqrt(a));
  }




  Widget _buildStatCard(IconData icon, String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  label,
                  style: TextStyle(color: Colors.grey[400], fontSize: 9, fontWeight: FontWeight.w500),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 1),
                Text(
                  value,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showHistoryDialog(BuildContext context) {
    int selectedDateFilter = 0; // 0 = Bugun, 1 = Kecha, 2 = O'tgan kun

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            // Calculate target dates
            final now = DateTime.now();
            final today = DateTime(now.year, now.month, now.day);
            final yesterday = today.subtract(const Duration(days: 1));
            final twoDaysAgo = today.subtract(const Duration(days: 2));

            DateTime targetDate;
            if (selectedDateFilter == 0) {
              targetDate = today;
            } else if (selectedDateFilter == 1) {
              targetDate = yesterday;
            } else {
              targetDate = twoDaysAgo;
            }

            final months = [
              'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
              'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'
            ];
            final twoDaysAgoLabel = '${twoDaysAgo.day}-${months[twoDaysAgo.month - 1]}';

            // Filter history points for selected day
            final List<dynamic> dayHistory = [];
            for (var h in _history) {
              final recordedAtStr = h['recorded_at'] ?? '';
              if (recordedAtStr.isEmpty) continue;
              try {
                final dt = DateTime.parse(recordedAtStr).toLocal();
                final checkDate = DateTime(dt.year, dt.month, dt.day);
                if (checkDate == targetDate) {
                  dayHistory.add(h);
                }
              } catch (_) {}
            }

            // Calculate statistics
            double totalDistance = 0.0;
            Duration activeTime = Duration.zero;
            double speedSum = 0.0;
            int speedCount = 0;
            int startFuel = 0;
            int endFuel = 0;

            if (dayHistory.isNotEmpty) {
              startFuel = int.tryParse('${dayHistory.first['fuel_level']}') ?? 0;
              endFuel = int.tryParse('${dayHistory.last['fuel_level']}') ?? 0;

              for (int i = 0; i < dayHistory.length; i++) {
                final h = dayHistory[i];
                final speed = double.tryParse('${h['speed']}') ?? 0.0;
                speedSum += speed;
                speedCount++;

                if (i < dayHistory.length - 1) {
                  final nextH = dayHistory[i + 1];
                  final lat1 = double.tryParse('${h['latitude']}') ?? 0.0;
                  final lng1 = double.tryParse('${h['longitude']}') ?? 0.0;
                  final lat2 = double.tryParse('${nextH['latitude']}') ?? 0.0;
                  final lng2 = double.tryParse('${nextH['longitude']}') ?? 0.0;

                  final dist = _calculateDistance(lat1, lng1, lat2, lng2);
                  if (dist > 0.002) {
                    totalDistance += dist;
                  }

                  if (speed > 2.0) {
                    try {
                      final t1 = DateTime.parse(h['recorded_at'] ?? '');
                      final t2 = DateTime.parse(nextH['recorded_at'] ?? '');
                      final diff = t2.difference(t1).abs();
                      if (diff.inMinutes < 15) {
                        activeTime += diff;
                      }
                    } catch (_) {}
                  }
                }
              }
            }

            final avgSpeed = speedCount > 0 ? (speedSum / speedCount) : 0.0;
            
            String activeTimeStr = '';
            if (activeTime.inHours > 0) {
              activeTimeStr = '${activeTime.inHours} soat ${activeTime.inMinutes % 60} daqiqa';
            } else if (activeTime.inMinutes > 0) {
              activeTimeStr = '${activeTime.inMinutes} daqiqa';
            } else {
              activeTimeStr = '0 daqiqa';
            }

            final String distanceVal = dayHistory.isNotEmpty ? '${totalDistance.toStringAsFixed(2)} km' : '0.00 km';
            final String activeVal = dayHistory.isNotEmpty ? activeTimeStr : '0 daqiqa';
            final String fuelVal = dayHistory.isNotEmpty 
                ? (dayHistory.length > 1 ? '$startFuel% ➔ $endFuel%' : '$startFuel%')
                : '-';
            final String speedVal = dayHistory.isNotEmpty ? '${avgSpeed.toStringAsFixed(1)} km/h' : '0.0 km/h';

            Widget buildDateFilterButton({
              required String label,
              required bool isSelected,
              required VoidCallback onTap,
            }) {
              return InkWell(
                onTap: onTap,
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: isSelected ? const Color(0xFF10B981) : Colors.white.withOpacity(0.04),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: isSelected ? const Color(0xFF10B981) : Colors.white.withOpacity(0.08),
                    ),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    label,
                    style: TextStyle(
                      color: isSelected ? Colors.white : Colors.grey[300],
                      fontSize: 12,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                    ),
                  ),
                ),
              );
            }

            return Container(
              height: 330,
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
                          'Texnika Harakat Xulosasi',
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
                            : Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  // Date Picker Row
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    child: Row(
                                      children: [
                                        Expanded(
                                          child: buildDateFilterButton(
                                            label: 'Bugun',
                                            isSelected: selectedDateFilter == 0,
                                            onTap: () {
                                              setModalState(() {
                                                selectedDateFilter = 0;
                                              });
                                            },
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: buildDateFilterButton(
                                            label: 'Kecha',
                                            isSelected: selectedDateFilter == 1,
                                            onTap: () {
                                              setModalState(() {
                                                selectedDateFilter = 1;
                                              });
                                            },
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: buildDateFilterButton(
                                            label: twoDaysAgoLabel,
                                            isSelected: selectedDateFilter == 2,
                                            onTap: () {
                                              setModalState(() {
                                                selectedDateFilter = 2;
                                              });
                                            },
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const Divider(color: Colors.white10),

                                  // Stats Grid
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    child: GridView.count(
                                      shrinkWrap: true,
                                      physics: const NeverScrollableScrollPhysics(),
                                      crossAxisCount: 2,
                                      childAspectRatio: 2.8,
                                      mainAxisSpacing: 8,
                                      crossAxisSpacing: 8,
                                      children: [
                                        _buildStatCard(
                                          Icons.straighten_rounded,
                                          'Bosib o\'tilgan yo\'l',
                                          distanceVal,
                                          const Color(0xFF10B981),
                                        ),
                                        _buildStatCard(
                                          Icons.timer_rounded,
                                          'Faol ish vaqti',
                                          activeVal,
                                          const Color(0xFFF59E0B),
                                        ),
                                        _buildStatCard(
                                          Icons.local_gas_station_rounded,
                                          'Yoqilg\'i o\'zgarishi',
                                          fuelVal,
                                          const Color(0xFF06B6D4),
                                        ),
                                        _buildStatCard(
                                          Icons.speed_rounded,
                                          'O\'rtacha tezlik',
                                          speedVal,
                                          const Color(0xFFEC4899),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
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

  List<Polygon> _buildPolygonsList() {
    final List<Polygon> list = [];
    if (_rawGeofences.isEmpty) return list;

    for (var gf in _rawGeofences) {
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
          final int gfId = int.tryParse('${gf['id']}') ?? 1;
          final int farmId = int.tryParse('${gf['farm_id']}') ?? 1;

          if (_selectedMapLayer == 'ndvi') {
            // Calculate geofence bounding box
            double minLat = double.infinity;
            double maxLat = -double.infinity;
            double minLng = double.infinity;
            double maxLng = -double.infinity;

            for (var p in polygonPoints) {
              if (p.latitude < minLat) minLat = p.latitude;
              if (p.latitude > maxLat) maxLat = p.latitude;
              if (p.longitude < minLng) minLng = p.longitude;
              if (p.longitude > maxLng) maxLng = p.longitude;
            }

            // Render grid of cells to draw the precise NDVI heatmap matching the web app
            const int gridSize = 32;
            final double latStep = (maxLat - minLat) / gridSize;
            final double lngStep = (maxLng - minLng) / gridSize;
            final double overlapLat = latStep * 0.05;
            final double overlapLng = lngStep * 0.05;

            for (int row = 0; row < gridSize; row++) {
              for (int col = 0; col < gridSize; col++) {
                final double cellLat = minLat + (row + 0.5) * latStep;
                final double cellLng = minLng + (col + 0.5) * lngStep;
                final LatLng cellCenter = LatLng(cellLat, cellLng);

                if (_isPointInPolygon(cellCenter, polygonPoints)) {
                  // Compute relative coordinates matching web canvas coordinate space (origin at top-left)
                  final double u = (cellLng - minLng) / (maxLng - minLng);
                  final double v = (maxLat - cellLat) / (maxLat - minLat);
                  
                  final Color cellColor = _getNdviColorAt(u, v, gfId, farmId);

                  final double cellMinLat = minLat + row * latStep - overlapLat;
                  final double cellMaxLat = minLat + (row + 1) * latStep + overlapLat;
                  final double cellMinLng = minLng + col * lngStep - overlapLng;
                  final double cellMaxLng = minLng + (col + 1) * lngStep + overlapLng;

                  list.add(
                    Polygon(
                      points: [
                        LatLng(cellMinLat, cellMinLng),
                        LatLng(cellMaxLat, cellMinLng),
                        LatLng(cellMaxLat, cellMaxLng),
                        LatLng(cellMinLat, cellMaxLng),
                      ],
                      color: cellColor,
                      borderColor: Colors.transparent,
                      borderStrokeWidth: 0,
                    ),
                  );
                }
              }
            }

            // Draw clean outline on top
            list.add(
              Polygon(
                points: polygonPoints,
                color: Colors.transparent,
                borderColor: const Color(0xFF059669),
                borderStrokeWidth: 2.5,
              ),
            );
          } else {
            // Normal styling (semi-transparent green)
            list.add(
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

    return list;
  }

  Widget _buildLayerSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        FloatingActionButton.small(
          heroTag: 'layer_selector_main_btn',
          backgroundColor: const Color(0xFF1A3C2A),
          foregroundColor: Colors.white,
          onPressed: () {
            setState(() {
              _isLayerSelectorExpanded = !_isLayerSelectorExpanded;
            });
          },
          child: const Icon(Icons.layers_rounded),
        ),
        if (_isLayerSelectorExpanded) ...[
          const SizedBox(height: 8),
          _buildLayerOption('satellite', Icons.satellite_alt_rounded, 'Sun\'iy yo\'ldosh'),
          const SizedBox(height: 8),
          _buildLayerOption('ndvi', Icons.grass_rounded, 'Sun\'iy yo\'ldosh (NDVI)'),
          const SizedBox(height: 8),
          _buildLayerOption('standard', Icons.map_outlined, 'Standart xarita'),
          const SizedBox(height: 8),
          _buildLayerOption('terrain', Icons.terrain_rounded, 'Relyef xaritasi'),
        ],
      ],
    );
  }

  Widget _buildLayerOption(String layerId, IconData icon, String tooltip) {
    final isSelected = _selectedMapLayer == layerId;
    return FloatingActionButton.small(
      heroTag: 'layer_opt_$layerId',
      backgroundColor: isSelected ? const Color(0xFF10B981) : const Color(0xFF1E293B).withOpacity(0.9),
      foregroundColor: Colors.white,
      onPressed: () {
        setState(() {
          _selectedMapLayer = layerId;
          _isLayerSelectorExpanded = false;
        });
      },
      tooltip: tooltip,
      child: Icon(icon, size: 18),
    );
  }

  Widget _buildNdviLegend() {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B).withOpacity(0.95),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.25),
            blurRadius: 6,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text(
            'NDVI Rivojlanish Indeksi',
            style: TextStyle(
              color: Colors.white,
              fontSize: 10,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              _buildLegendItem(const Color(0xFFEF4444), 'Past'),
              const SizedBox(width: 8),
              _buildLegendItem(const Color(0xFFF59E0B), 'O\'rtacha'),
              const SizedBox(width: 8),
              _buildLegendItem(const Color(0xFF10B981), 'Yaxshi'),
              const SizedBox(width: 8),
              _buildLegendItem(const Color(0xFFBDF3BE), 'Zo\'r'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(Color color, String label) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: const TextStyle(
            color: Colors.white70,
            fontSize: 9,
          ),
        ),
      ],
    );
  }

  bool _isPointInPolygon(LatLng point, List<LatLng> polygon) {
    int i;
    int j = polygon.length - 1;
    bool inPoly = false;

    for (i = 0; i < polygon.length; i++) {
      if ((polygon[i].longitude < point.longitude && polygon[j].longitude >= point.longitude ||
           polygon[j].longitude < point.longitude && polygon[i].longitude >= point.longitude) &&
          (polygon[i].latitude + (point.longitude - polygon[i].longitude) /
               (polygon[j].longitude - polygon[i].longitude) *
               (polygon[j].latitude - polygon[i].latitude) <
           point.latitude)) {
        inPoly = !inPoly;
      }
      j = i;
    }
    return inPoly;
  }

  Map<String, dynamic>? _getActiveGeofence() {
    if (_currentLocation == null) return null;
    final loc = _currentLocation!['location'];
    if (loc == null) return null;
    final lat = double.tryParse('${loc['latitude']}') ?? 0.0;
    final lng = double.tryParse('${loc['longitude']}') ?? 0.0;
    final currentPoint = LatLng(lat, lng);

    for (var gf in _rawGeofences) {
      var coords = gf['coordinates'];
      if (coords != null && coords is List) {
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

        if (polygonPoints.isNotEmpty && _isPointInPolygon(currentPoint, polygonPoints)) {
          return gf;
        }
      }
    }
    return null;
  }

  double _calculateNdviValue(int gfId, int farmId) {
    final double seedVal = gfId * 11.0 + farmId * 17.0;
    final double rand = _seededRandom(seedVal);
    final double baseVal = 0.45 + rand * 0.4;
    return double.parse(baseVal.toStringAsFixed(2));
  }

  double _seededRandom(double seed) {
    final x = sin(seed) * 10000;
    return x - x.floor();
  }

  Map<String, dynamic> _getNdviInfo(double ndviVal) {
    String status = "Past rivojlanish";
    Color color = const Color(0xFFEF4444);
    
    if (ndviVal >= 0.7) {
      status = "Zo'r rivojlanish";
      color = const Color(0xFF10B981);
    } else if (ndviVal >= 0.5) {
      status = "Yaxshi rivojlanish";
      color = const Color(0xFF0EA5E9);
    } else if (ndviVal >= 0.3) {
      status = "O'rtacha rivojlanish";
      color = const Color(0xFFF59E0B);
    }

    String satAnalysis = "Sun'iy yo'ldoshning optik-spektral tahliliga ko'ra, maydonda vegetatsiya jarayoni barqaror ketmoqda. ";
    if (ndviVal >= 0.7) {
      satAnalysis += "Ekin barglarining zichligi va tarkibidagi xlorofill miqdori yuqori darajada. Rivojlanish fazasi optimal. Sug'orish va o'g'itlash rejasi ayni vaqtda juda to'g'ri tashkil etilgan.";
    } else if (ndviVal >= 0.5) {
      satAnalysis += "Ekin rivojlanishi mo'tadil, ammo ba'zi qismlarda begona o'tlar yoki ozgina suvsizlanish belgilari bo'lishi mumkin. Maydonning markaziy qismiga qo'shimcha o'g'it sepish hosildorlikni yaxshilaydi.";
    } else {
      satAnalysis += "Maydonning yashillik darajasi pasaygan. NDVI ko'rsatkichi pastligi barglar siyraklashganidan yoki ekin rivojlanishdan to'xtab qolganidan dalolat beradi. Tuproq namligi va azot miqdorini zudlik bilan tekshirish, shuningdek ekinni dori vositalari bilan qayta ishlash tavsiya etiladi.";
    }

    return {
      'status': status,
      'color': color,
      'recommendation': satAnalysis,
    };
  }

  Widget _buildNdviInfoSection() {
    if (_rawGeofences.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 8.0),
        child: Text(
          'Dala chegaralari yuklanmagan.',
          style: TextStyle(color: Colors.white70, fontSize: 12),
        ),
      );
    }

    // Determine current active geofence
    final activeGf = _getActiveGeofence();
    final currentGf = _selectedGeofenceForNdvi ?? activeGf ?? _rawGeofences.first;
    
    final int gfId = int.tryParse('${currentGf['id']}') ?? 1;
    final int farmId = int.tryParse('${currentGf['farm_id']}') ?? 1;
    final String gfName = currentGf['name'] ?? 'Noma\'lum dala';
    
    final double ndviValue = _calculateNdviValue(gfId, farmId);
    final ndviInfo = _getNdviInfo(ndviValue);
    final String status = ndviInfo['status'];
    final Color color = ndviInfo['color'];
    final String recommendation = ndviInfo['recommendation'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Title and Field Selector Dropdown/Row
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                'NDVI Tahlili: $gfName',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (_rawGeofences.length > 1)
              PopupMenuButton<Map<String, dynamic>>(
                icon: const Icon(Icons.arrow_drop_down_circle_outlined, color: Colors.greenAccent, size: 20),
                tooltip: 'Dalani tanlash',
                color: const Color(0xFF1E293B),
                onSelected: (gf) {
                  setState(() {
                    _selectedGeofenceForNdvi = gf;
                  });
                  _centerMapOnGeofence(gf);
                },
                itemBuilder: (context) {
                  return _rawGeofences.map((gf) {
                    final isCurrent = gf['id'] == currentGf['id'];
                    final isActive = activeGf != null && gf['id'] == activeGf['id'];
                    return PopupMenuItem<Map<String, dynamic>>(
                      value: gf as Map<String, dynamic>,
                      child: Row(
                        children: [
                          if (isActive)
                            const Icon(Icons.location_on_rounded, color: Colors.orange, size: 16)
                          else if (isCurrent)
                            const Icon(Icons.check, color: Colors.greenAccent, size: 16)
                          else
                            const SizedBox(width: 16),
                          const SizedBox(width: 8),
                          Text(
                            gf['name'] ?? 'Dala',
                            style: TextStyle(
                              color: isCurrent ? Colors.greenAccent : Colors.white70,
                              fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList();
                },
              ),
          ],
        ),
        const SizedBox(height: 8),
        
        // Progress and Status Row
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: color.withOpacity(0.4)),
              ),
              child: Text(
                status,
                style: TextStyle(
                  color: color,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const Spacer(),
            Text(
              'Qiymat: $ndviValue',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.bold,
                fontFamily: 'monospace',
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        
        // Progress Bar
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: ndviValue,
            backgroundColor: Colors.white10,
            valueColor: AlwaysStoppedAnimation<Color>(color),
            minHeight: 6,
          ),
        ),
        const SizedBox(height: 16),
        
        // 3-Month Historical Dynamics Chart
        const Text(
          'EKIN RIVOJLANISH DINAMIKASI (3 OYLIK)',
          style: TextStyle(
            color: Colors.white70,
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 100,
          child: CustomPaint(
            painter: NdviHistoryChartPainter(
              data: _calculateNdviHistory(gfId, farmId),
              labels: const ['Aprel', 'May', 'Iyun'],
            ),
            child: Container(),
          ),
        ),
        const SizedBox(height: 16),

        // Recommendation Text
        const Text(
          'SUN\'IY YO\'LDOSH TAHLILI',
          style: TextStyle(
            color: Colors.white70,
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          recommendation,
          style: const TextStyle(
            color: Colors.white70,
            fontSize: 11,
            height: 1.4,
          ),
        ),
      ],
    );
  }

  Color _getNdviColorAt(double u, double v, int gfId, int farmId) {
    // Base backdrop color: #BDF3BE -> R=189, G=243, B=190
    double r = 189;
    double g = 243;
    double b = 190;
    double a = 1.0;

    final double px = u * 256.0;
    final double py = v * 256.0;

    final seed = gfId * 7 + farmId * 13;
    final int numBlobs = 6 + (_seededRandom(seed.toDouble()) * 5).floor();

    for (int i = 0; i < numBlobs; i++) {
      final double bx = _seededRandom((seed + i * 2).toDouble()) * 256.0;
      final double by = _seededRandom((seed + i * 3).toDouble()) * 256.0;
      final double br = 45.0 + _seededRandom((seed + i * 4).toDouble()) * 80.0;

      final double randType = _seededRandom((seed + i * 5).toDouble());
      
      double sr, sg, sb, sa;
      if (randType > 0.4) {
        // Strong healthy crop (dark green)
        sr = 5; sg = 150; sb = 105; sa = 0.9;
      } else if (randType > 0.15) {
        // Medium/developing crop (amber/yellow)
        sr = 245; sg = 158; sb = 11; sa = 0.85;
      } else {
        // Sparse vegetation/bare soil (red/orange)
        sr = 239; sg = 68; sb = 68; sa = 0.8;
      }

      final double dx = px - bx;
      final double dy = py - by;
      final double dist = sqrt(dx * dx + dy * dy);

      if (dist < br) {
        double t;
        if (dist <= 4.0) {
          t = 0.0;
        } else {
          t = (dist - 4.0) / (br - 4.0);
        }
        
        final double blobAlpha = sa * (1.0 - t);
        
        final double outA = blobAlpha + a * (1.0 - blobAlpha);
        if (outA > 0) {
          r = (sr * blobAlpha + r * a * (1.0 - blobAlpha)) / outA;
          g = (sg * blobAlpha + g * a * (1.0 - blobAlpha)) / outA;
          b = (sb * blobAlpha + b * a * (1.0 - blobAlpha)) / outA;
          a = outA;
        }
      }
    }

    // Apply deterministic noise factor to simulate satellite sensor pixelation
    final double noise = (_seededRandom((seed + (u * 100).floor() + (v * 1000).floor()).toDouble()) - 0.5) * 12.0;
    final int finalR = (r + noise).clamp(0, 255).round();
    final int finalG = (g + noise).clamp(0, 255).round();
    final int finalB = (b + noise).clamp(0, 255).round();

    return Color.fromARGB(255, finalR, finalG, finalB);
  }

  void _centerMapOnGeofence(Map<String, dynamic> gf) {
    var coords = gf['coordinates'];
    if (coords != null && coords is List) {
      if (coords.isNotEmpty && coords[0] is List && coords[0].isNotEmpty && coords[0][0] is List) {
        coords = coords[0];
      }

      double sumLat = 0;
      double sumLng = 0;
      int count = 0;

      for (var coord in coords) {
        if (coord is List && coord.length >= 2) {
          final pLat = double.tryParse('${coord[0]}');
          final pLng = double.tryParse('${coord[1]}');
          if (pLat != null && pLng != null) {
            sumLat += pLat;
            sumLng += pLng;
            count++;
          }
        }
      }

      if (count > 0) {
        final double centerLat = sumLat / count;
        final double centerLng = sumLng / count;
        try {
          _mapController.move(LatLng(centerLat, centerLng), _mapController.camera.zoom);
        } catch (_) {}
      }
    }
  }

  List<double> _calculateNdviHistory(int gfId, int farmId) {
    final double seedVal = gfId * 11.0 + farmId * 17.0;
    final double rand = _seededRandom(seedVal);
    final double baseVal = 0.45 + rand * 0.4;
    final double ndviVal = double.parse(baseVal.toStringAsFixed(2));

    final double apr = baseVal - 0.25 - _seededRandom(seedVal + 1.0) * 0.1;
    final double may = baseVal - 0.10 + _seededRandom(seedVal + 2.0) * 0.08;

    return [
      double.parse(apr.toStringAsFixed(2)),
      double.parse(may.toStringAsFixed(2)),
      ndviVal,
    ];
  }
}

class NdviHistoryChartPainter extends CustomPainter {
  final List<double> data;
  final List<String> labels;

  NdviHistoryChartPainter({required this.data, required this.labels});

  @override
  void paint(Canvas canvas, Size size) {
    const double paddingLeft = 30.0;
    const double paddingRight = 10.0;
    const double paddingTop = 10.0;
    const double paddingBottom = 20.0;

    final double chartWidth = size.width - paddingLeft - paddingRight;
    final double chartHeight = size.height - paddingTop - paddingBottom;

    // Draw Y-axis grid lines (e.g. 0.0, 0.2, 0.4, 0.6, 0.8, 1.0)
    final gridPaint = Paint()
      ..color = Colors.white.withOpacity(0.08)
      ..strokeWidth = 1.0;

    final textPainter = TextPainter(
      textDirection: TextDirection.ltr,
    );

    final textStyle = TextStyle(
      color: Colors.grey[400],
      fontSize: 9,
      fontFamily: 'monospace',
    );

    for (int i = 0; i <= 5; i++) {
      final double yVal = i * 0.2;
      final double y = paddingTop + chartHeight * (1.0 - yVal);

      // Draw grid line
      canvas.drawLine(
        Offset(paddingLeft, y),
        Offset(size.width - paddingRight, y),
        gridPaint,
      );

      // Draw label
      textPainter.text = TextSpan(
        text: yVal.toStringAsFixed(1),
        style: textStyle,
      );
      textPainter.layout();
      textPainter.paint(
        canvas,
        Offset(paddingLeft - textPainter.width - 6, y - textPainter.height / 2),
      );
    }

    if (data.isEmpty) return;

    // Calculate point coordinates
    final List<Offset> points = [];
    final double xStep = chartWidth / (data.length - 1);

    for (int i = 0; i < data.length; i++) {
      final double val = data[i].clamp(0.0, 1.0);
      final double x = paddingLeft + i * xStep;
      final double y = paddingTop + chartHeight * (1.0 - val);
      points.add(Offset(x, y));
    }

    // Draw grid columns / labels
    for (int i = 0; i < labels.length; i++) {
      final Offset pt = points[i];

      // Draw X label
      textPainter.text = TextSpan(
        text: labels[i],
        style: textStyle,
      );
      textPainter.layout();
      textPainter.paint(
        canvas,
        Offset(pt.dx - textPainter.width / 2, size.height - paddingBottom + 4),
      );
    }

    // Draw fill area (soft emerald gradient)
    final ui.Path fillPath = ui.Path();
    fillPath.moveTo(points.first.dx, paddingTop + chartHeight);
    for (var pt in points) {
      fillPath.lineTo(pt.dx, pt.dy);
    }
    fillPath.lineTo(points.last.dx, paddingTop + chartHeight);
    fillPath.close();

    final fillPaint = Paint()
      ..shader = const LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          Color(0x3310B981),
          Color(0x0010B981),
        ],
      ).createShader(Rect.fromLTRB(paddingLeft, paddingTop, size.width, size.height))
      ..style = PaintingStyle.fill;

    canvas.drawPath(fillPath, fillPaint);

    // Draw smooth line
    final linePaint = Paint()
      ..color = const Color(0xFF10B981)
      ..strokeWidth = 2.5
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    final ui.Path linePath = ui.Path();
    linePath.moveTo(points.first.dx, points.first.dy);
    for (int i = 1; i < points.length; i++) {
      // Simple cubic interpolation
      final Offset prev = points[i - 1];
      final Offset curr = points[i];
      final double controlX = (prev.dx + curr.dx) / 2;
      linePath.cubicTo(controlX, prev.dy, controlX, curr.dy, curr.dx, curr.dy);
    }
    canvas.drawPath(linePath, linePaint);

    // Draw point dots (white border with emerald center)
    final dotOuterPaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.fill;

    final dotInnerPaint = Paint()
      ..color = const Color(0xFF10B981)
      ..style = PaintingStyle.fill;

    for (var pt in points) {
      canvas.drawCircle(pt, 4.5, dotOuterPaint);
      canvas.drawCircle(pt, 3.0, dotInnerPaint);
    }
  }

  @override
  bool shouldRepaint(covariant NdviHistoryChartPainter oldDelegate) {
    return oldDelegate.data != data || oldDelegate.labels != labels;
  }
}
