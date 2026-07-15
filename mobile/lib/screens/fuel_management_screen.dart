import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';

class FuelManagementScreen extends ConsumerStatefulWidget {
  const FuelManagementScreen({super.key});

  @override
  ConsumerState<FuelManagementScreen> createState() => _FuelManagementScreenState();
}

class _FuelManagementScreenState extends ConsumerState<FuelManagementScreen> {
  int? _selectedVehicleId;
  bool _isLoadingReport = false;
  Map<String, dynamic>? _fuelReport;
  List<dynamic> _fuelEntries = [];
  List<dynamic> _fuelAlerts = [];
  String? _errorMessage;

  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialVehicle();
    });
  }

  @override
  void dispose() {
    _amountController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _loadInitialVehicle() {
    final vehiclesState = ref.read(vehiclesProvider);
    vehiclesState.whenData((vehicles) {
      if (vehicles.isNotEmpty) {
        setState(() {
          _selectedVehicleId = vehicles.first['id'];
        });
        _fetchFuelReport(vehicles.first['id']);
      }
    });
  }

  Future<void> _fetchFuelReport(int vehicleId) async {
    setState(() {
      _isLoadingReport = true;
      _errorMessage = null;
    });
    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.getFuelReport(vehicleId);
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _fuelReport = res.data['report'];
          _fuelEntries = res.data['fuel_entries'] ?? [];
          _fuelAlerts = res.data['fuel_alerts'] ?? [];
          _isLoadingReport = false;
        });
      } else {
        setState(() {
          _errorMessage = "Hisobotni yuklab bo'lmadi.";
          _isLoadingReport = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = "Tarmoq xatoligi yuz berdi.";
          _isLoadingReport = false;
        });
      }
    }
  }

  Future<void> _submitFuelEntry() async {
    if (!_formKey.currentState!.validate() || _selectedVehicleId == null) return;

    setState(() {
      _isSubmitting = true;
    });

    try {
      final api = ref.read(apiServiceProvider);
      final amount = double.parse(_amountController.text);
      final notes = _notesController.text;

      final res = await api.addFuelEntry(
        _selectedVehicleId!,
        amount,
        notes: notes.isNotEmpty ? notes : null,
      );

      if (res.statusCode == 201 && mounted) {
        final data = res.data;
        _amountController.clear();
        _notesController.clear();

        // Refresh vehicles list so home dashboard updates
        ref.read(vehiclesProvider.notifier).fetchVehicles();
        
        // Reload fuel report
        await _fetchFuelReport(_selectedVehicleId!);

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Yoqilg'i quyish muvaffaqiyatli saqlandi!"),
            backgroundColor: Colors.green,
          ),
        );

        // Show warning dialog if present
        if (data['warning'] != null) {
          _showWarningDialog(data['warning']);
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Xatolik yuz berdi. Qayta urinib ko'ring."),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  void _showWarningDialog(String warningText) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 28),
            SizedBox(width: 8),
            Text(
              "Tizim Ogohlantirishi",
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
        content: Text(
          warningText,
          style: const TextStyle(fontSize: 14, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text("Tushunarli", style: TextStyle(color: Color(0xFF1A3C2A), fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final vehiclesState = ref.watch(vehiclesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Yoqilg'i Hisobi", style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Theme.of(context).colorScheme.onPrimary,
        elevation: 0,
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Vehicle selector header
          Container(
            color: const Color(0xFF1A3C2A),
            padding: const EdgeInsets.only(bottom: 16, top: 4),
            child: vehiclesState.when(
              data: (vehicles) {
                if (vehicles.isEmpty) {
                  return const Center(
                    child: Text(
                      "Sizda ro'yxatdan o'tgan texnikalar yo'q.",
                      style: TextStyle(color: Colors.white70),
                    ),
                  );
                }
                return SizedBox(
                  height: 48,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: vehicles.length,
                    itemBuilder: (context, index) {
                      final v = vehicles[index];
                      final isSelected = v['id'] == _selectedVehicleId;
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(
                          label: Text(v['name']),
                          labelStyle: TextStyle(
                            color: isSelected ? const Color(0xFF1A3C2A) : Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                          selected: isSelected,
                          selectedColor: Colors.white,
                          backgroundColor: Colors.white.withOpacity(0.2),
                          showCheckmark: false,
                          onSelected: (selected) {
                            if (selected) {
                              setState(() {
                                _selectedVehicleId = v['id'];
                              });
                              _fetchFuelReport(v['id']);
                            }
                          },
                        ),
                      );
                    },
                  ),
                );
              },
              error: (_, __) => const SizedBox(),
              loading: () => const Center(
                child: SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                ),
              ),
            ),
          ),

          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                if (_selectedVehicleId != null) {
                  await _fetchFuelReport(_selectedVehicleId!);
                }
              },
              color: const Color(0xFF1A3C2A),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                child: _selectedVehicleId == null
                    ? const Center(
                        child: Text("Iltimos, avval texnikani tanlang."),
                      )
                    : _isLoadingReport
                        ? const Center(
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 40),
                              child: CircularProgressIndicator(color: Color(0xFF1A3C2A)),
                            ),
                          )
                        : _errorMessage != null
                            ? Center(
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(vertical: 40),
                                  child: Column(
                                    children: [
                                      Text(
                                        _errorMessage!,
                                        style: const TextStyle(color: Colors.red),
                                      ),
                                      const SizedBox(height: 12),
                                      ElevatedButton(
                                        onPressed: () => _fetchFuelReport(_selectedVehicleId!),
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: const Color(0xFF1A3C2A),
                                        ),
                                        child: const Text("Qayta urinish"),
                                      )
                                    ],
                                  ),
                                ),
                              )
                            : _buildReportBody(),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReportBody() {
    if (_fuelReport == null) return const SizedBox();

    final report = _fuelReport!;
    final fuelPercent = (report['current_fuel_percent'] as num?)?.toDouble() ?? 0.0;
    final fuelLiters = (report['current_fuel_liters'] as num?)?.toDouble() ?? 0.0;
    final totalRefilled = (report['total_refilled_liters'] as num?)?.toDouble() ?? 0.0;
    final plateNumber = report['plate_number'] ?? '';
    final distance = (report['distance_traveled_km'] as num?)?.toDouble() ?? 0.0;
    final expectedConsumed = (report['expected_consumed_liters'] as num?)?.toDouble() ?? 0.0;
    final trustScore = report['trust_score'] ?? 100;
    final warningMessage = report['warning_message'];
    final fuelStatus = report['fuel_status'] ?? 'ok';

    Color fuelColor = Colors.green;
    String statusText = "Normal";

    if (fuelStatus == 'low') {
      fuelColor = Colors.orange;
      statusText = "Kam qoldi";
    } else if (fuelStatus == 'empty') {
      fuelColor = Colors.red;
      statusText = "Tugagan";
    } else if (fuelStatus == 'missing_refill') {
      fuelColor = Colors.purple;
      statusText = "Yoqilg'i kiritilmagan";
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (warningMessage != null) ...[
          Container(
            margin: const EdgeInsets.only(bottom: 16),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.red[50],
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.red[200]!),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.warning_amber_rounded, color: Colors.red),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    warningMessage,
                    style: TextStyle(color: Colors.red[900], fontSize: 13, height: 1.4),
                  ),
                ),
              ],
            ),
          ),
        ],

        // Gauge / Circle Card
        Card(
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Row(
              children: [
                // Circular Gauge
                Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 90,
                      height: 90,
                      child: CircularProgressIndicator(
                        value: fuelPercent / 100.0,
                        strokeWidth: 10,
                        backgroundColor: Colors.grey[200],
                        valueColor: AlwaysStoppedAnimation<Color>(fuelColor),
                      ),
                    ),
                    Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          "${fuelPercent.toStringAsFixed(0)}%",
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const Text(
                          "bak",
                          style: TextStyle(fontSize: 10, color: Colors.grey),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(width: 20),
                // Text details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        report['vehicle_name'] ?? 'Belarus 82.1',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        plateNumber,
                        style: const TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: fuelColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              statusText,
                              style: TextStyle(
                                fontSize: 11,
                                color: fuelColor,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            "${fuelLiters.toStringAsFixed(1)} Litr qoldi",
                            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Statistics Cards
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.8,
          children: [
            _buildStatBox(
              "Bosib o'tilgan yo'l",
              "${distance.toStringAsFixed(1)} km",
              Icons.directions_run_rounded,
              Colors.blue,
            ),
            _buildStatBox(
              "Quyilgan yoqilg'i",
              "${totalRefilled.toStringAsFixed(0)} L",
              Icons.local_gas_station_rounded,
              Colors.green,
            ),
            _buildStatBox(
              "Kutilgan sarf",
              "${expectedConsumed.toStringAsFixed(1)} L",
              Icons.trending_down_rounded,
              Colors.orange,
            ),
            _buildStatBox(
              "Ishonch reytingi",
              "$trustScore%",
              Icons.verified_user_rounded,
              Colors.purple,
            ),
          ],
        ),
        const SizedBox(height: 24),

        // Log refilled amount form
        const Text(
          "Yoqilg'i quyishni kiritish",
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
        ),
        const SizedBox(height: 10),
        Card(
          elevation: 1,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextFormField(
                    controller: _amountController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: "Yoqilg'i miqdori (Litrda)",
                      prefixIcon: Icon(Icons.local_gas_station_rounded),
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) {
                      if (val == null || val.isEmpty) {
                        return "Iltimos, yoqilg'i miqdorini kiriting";
                      }
                      final amt = double.tryParse(val);
                      if (amt == null || amt <= 0) {
                        return "Noto'g'ri miqdor kiritildi";
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _notesController,
                    decoration: const InputDecoration(
                      labelText: "Izoh (ixtiyoriy)",
                      prefixIcon: Icon(Icons.notes_rounded),
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _isSubmitting ? null : _submitFuelEntry,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1A3C2A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Text(
                            "Saqlash",
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(height: 24),

        // Recent entries list
        if (_fuelEntries.isNotEmpty) ...[
          const Text(
            "Oxirgi quyishlar tarixi",
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
          ),
          const SizedBox(height: 10),
          Card(
            elevation: 1,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _fuelEntries.length,
              separatorBuilder: (c, i) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final entry = _fuelEntries[index];
                final amt = (entry['fuel_amount'] as num?)?.toDouble() ?? 0.0;
                final date = DateTime.tryParse(entry['refilled_at'] ?? '')?.toLocal();
                final dateStr = date != null
                    ? "${date.day.toString().padLeft(2, '0')}.${date.month.toString().padLeft(2, '0')}.${date.year} ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}"
                    : '';

                return ListTile(
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFE8F5E9),
                    child: Icon(Icons.add_road_rounded, color: Colors.green),
                  ),
                  title: Text(
                    "+${amt.toStringAsFixed(1)} Litr",
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text(entry['notes'] ?? 'Izohsiz'),
                  trailing: Text(
                    dateStr,
                    style: const TextStyle(fontSize: 11, color: Colors.grey),
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 24),
        ],

        // Anomaly warnings/Alerts list
        if (_fuelAlerts.isNotEmpty) ...[
          const Text(
            "Aniqlangan ogohlantirishlar",
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
          ),
          const SizedBox(height: 10),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _fuelAlerts.length,
            itemBuilder: (context, index) {
              final alert = _fuelAlerts[index];
              final status = alert['status'] ?? 'pending_check';
              Color statusColor = Colors.orange;
              String statusLabel = "Kutilmoqda";

              if (status == 'confirmed') {
                statusColor = Colors.red;
                statusLabel = "Tasdiqlangan";
              } else if (status == 'rejected') {
                statusColor = Colors.green;
                statusLabel = "Rad etilgan";
              }

              return Card(
                elevation: 0,
                color: Colors.red[50]?.withOpacity(0.5),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(color: Colors.red[100]!),
                ),
                margin: const EdgeInsets.only(bottom: 8),
                child: Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.warning_amber_rounded, color: Colors.red, size: 18),
                              const SizedBox(width: 6),
                              Text(
                                "Shubhali farq (${alert['type']})",
                                style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: statusColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              statusLabel,
                              style: TextStyle(fontSize: 10, color: statusColor, fontWeight: FontWeight.bold),
                            ),
                          )
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        alert['description'] ?? '',
                        style: TextStyle(fontSize: 12, color: Colors.grey[800], height: 1.4),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 24),
        ]
      ],
    );
  }

  Widget _buildStatBox(String title, String val, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontSize: 10, color: Colors.grey),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  val,
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
