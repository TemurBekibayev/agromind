import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../providers/providers.dart';
import 'ai_chat_screen.dart';

class SoilAnalysisScreen extends ConsumerStatefulWidget {
  const SoilAnalysisScreen({super.key});

  @override
  ConsumerState<SoilAnalysisScreen> createState() => _SoilAnalysisScreenState();
}

class _SoilAnalysisScreenState extends ConsumerState<SoilAnalysisScreen> {
  int? _selectedFarmId;
  List<dynamic> _analyses = [];
  bool _isLoadingAnalyses = false;
  Map<String, dynamic>? _selectedAnalysisDetails;
  bool _isLoadingDetails = false;
  final String _targetText = '24/7 yordamchi';
  final Set<int> _selectedAnalysisIds = {};
  bool _isSelectionMode = false;
  bool _isDeleting = false;
  String _displayedText = '';

  Future<void> _deleteSelectedAnalyses() async {
    if (_selectedAnalysisIds.isEmpty) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Tahlillarni o\'chirish'),
          content: Text('Haqiqatan ham tanlangan ${_selectedAnalysisIds.length} ta tahlil hisobotini butunlay o\'chirib tashlamoqchimisiz?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Bekor qilish'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, true),
              style: TextButton.styleFrom(foregroundColor: Colors.red),
              child: const Text('O\'chirish'),
            ),
          ],
        );
      },
    );

    if (confirm != true) return;

    setState(() {
      _isDeleting = true;
    });

    final api = ref.read(apiServiceProvider);
    int successCount = 0;
    
    try {
      for (final id in _selectedAnalysisIds) {
        final res = await api.deleteSoilAnalysis(id);
        if (res.data['status'] == 'success') {
          successCount++;
        }
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$successCount ta tahlil muvaffaqiyatli o\'chirildi.'),
            backgroundColor: Colors.green,
          ),
        );
        setState(() {
          _isSelectionMode = false;
          _selectedAnalysisIds.clear();
          _selectedAnalysisDetails = null; // Clear details if selected item was deleted
          _isDeleting = false;
        });
        if (_selectedFarmId != null) {
          _fetchAnalyses(_selectedFarmId!);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isDeleting = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Tahlillarni o\'chirishda xatolik yuz berdi.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _startTypewriterAnimation();
  }

  void _startTypewriterAnimation() async {
    while (mounted) {
      // 1. Textni tozalash
      if (mounted) {
        setState(() {
          _displayedText = '';
        });
      }
      await Future.delayed(const Duration(milliseconds: 500));

      // 2. Yozilish fazasi (har bir belgi uchun ~150ms, jami ~2.1 soniya)
      for (int i = 1; i <= _targetText.length; i++) {
        if (!mounted) return;
        await Future.delayed(const Duration(milliseconds: 150));
        setState(() {
          _displayedText = _targetText.substring(0, i);
        });
      }

      // 3. 15 soniya qimirlamay turish fazasi
      if (!mounted) return;
      await Future.delayed(const Duration(seconds: 15));
    }
  }

  // Auto-selection is handled reactively inside the build method.

  Future<void> _fetchAnalyses(int farmId) async {
    setState(() {
      _isLoadingAnalyses = true;
      _selectedAnalysisDetails = null;
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getSoilAnalyses(farmId);
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _analyses = res.data['analyses'] as List<dynamic>;
          _isLoadingAnalyses = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoadingAnalyses = false;
        });
      }
    }
  }

  Future<void> _fetchAnalysisDetails(int id) async {
    setState(() {
      _isLoadingDetails = true;
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getSoilAnalysis(id);
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _selectedAnalysisDetails = res.data['analysis'];
          _isLoadingDetails = false;
        });
      } else if (mounted) {
        setState(() {
          _isLoadingDetails = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.data['message'] ?? 'Tafsilotlarni yuklashda xatolik yuz berdi.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoadingDetails = false;
        });
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
          ),
        );
      }
    }
  }

  Future<void> _generateRecommendation(int id) async {
    setState(() {
      _isLoadingDetails = true;
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.requestSoilRecommendation(id);
      if (res.data['status'] == 'success' && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('AI tavsiyasi muvaffaqiyatli shakllantirildi.')),
        );
        _fetchAnalysisDetails(id);
        if (_selectedFarmId != null) {
          _fetchAnalyses(_selectedFarmId!);
        }
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoadingDetails = false;
        });
      }
    }
  }

  void _showAddAnalysisSheet() {
    final formKey = GlobalKey<FormState>();
    final cropController = TextEditingController();
    final phController = TextEditingController();
    final fertilityController = TextEditingController();
    final moistureController = TextEditingController();
    final tempController = TextEditingController();
    final sunlightController = TextEditingController();
    final humidityController = TextEditingController();

    // Load geofences for the selected farm
    final farmsState = ref.read(farmsProvider);
    List<dynamic> geofences = [];
    farmsState.whenData((farms) {
      final selectedFarm = farms.firstWhere(
        (f) => f['id'] == _selectedFarmId,
        orElse: () => null,
      );
      if (selectedFarm != null && selectedFarm['geofences'] != null) {
        geofences = selectedFarm['geofences'] as List<dynamic>;
      }
    });

    int? selectedGeofenceId;
    if (geofences.isNotEmpty) {
      selectedGeofenceId = geofences[0]['id'];
    }

    double convertEcToFertilityPercentage(double ec) {
      if (ec <= 0) return 0.0;
      if (ec >= 2000) return 100.0;
      if (ec < 400) {
        return (ec / 400.0) * 30.0;
      } else if (ec < 1200) {
        return 30.0 + ((ec - 400.0) / 800.0) * 50.0;
      } else {
        return 80.0 + ((ec - 1200.0) / 800.0) * 20.0;
      }
    }



    Widget buildLcdRow({
      required String label,
      required TextEditingController controller,
      String? unit,
      String hint = '0',
    }) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 4.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              label,
              style: const TextStyle(
                color: Color(0xFF0F3A0F),
                fontSize: 15,
                fontWeight: FontWeight.bold,
                fontFamily: 'monospace',
              ),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(
                  width: 80,
                  height: 24,
                  child: TextFormField(
                    controller: controller,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    textAlign: TextAlign.right,
                    style: const TextStyle(
                      color: Color(0xFF0F3A0F),
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'monospace',
                    ),
                    decoration: InputDecoration(
                      hintText: hint,
                      hintStyle: TextStyle(color: const Color(0xFF0F3A0F).withOpacity(0.3)),
                      border: InputBorder.none,
                      isDense: true,
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                ),
                if (unit != null) ...[
                  const SizedBox(width: 4),
                  SizedBox(
                    width: 45,
                    child: Text(
                      unit,
                      style: const TextStyle(
                        color: Color(0xFF0F3A0F),
                        fontSize: 13,
                        fontFamily: 'monospace',
                      ),
                    ),
                  ),
                ] else
                  const SizedBox(width: 49),
              ],
            ),
          ],
        ),
      );
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
                left: 20,
                right: 20,
                top: 20,
              ),
              child: SingleChildScrollView(
                child: Form(
                  key: formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Yangi Tahlil Qo\'shish',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close),
                            onPressed: () => Navigator.pop(context),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),

                      // Geofence (Yer maydoni) Selector
                      if (geofences.isNotEmpty) ...[
                        DropdownButtonFormField<int>(
                          value: selectedGeofenceId,
                          decoration: const InputDecoration(
                            labelText: 'Tahlil qilinadigan yer maydoni',
                            prefixIcon: Icon(Icons.landscape_rounded),
                            border: OutlineInputBorder(),
                          ),
                          items: geofences.map<DropdownMenuItem<int>>((g) {
                            return DropdownMenuItem<int>(
                              value: g['id'],
                              child: Text(g['name'] ?? 'Nomsiz yer'),
                            );
                          }).toList(),
                          validator: (v) => v == null ? 'Yer maydonini tanlang' : null,
                          onChanged: (val) {
                            setModalState(() {
                              selectedGeofenceId = val;
                            });
                          },
                        ),
                        const SizedBox(height: 15),
                      ] else ...[
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.amber[50],
                            border: Border.all(color: Colors.amber[300]!),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.warning_amber_rounded, color: Colors.orange),
                              SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'Diqqat: Ushbu fermada yer maydonlari (geofence) belgilanmagan. Tahlilni saqlashingiz mumkin, lekin xaritada yer rangini ko\'rish uchun avval yer maydonini belgilashingiz kerak.',
                                  style: TextStyle(fontSize: 11, color: Colors.black87),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 15),
                      ],

                      // Crop Input
                      TextFormField(
                        controller: cropController,
                        decoration: InputDecoration(
                          labelText: 'Yetishtiriladigan ekin turi',
                          hintText: 'Masalan: G\'o\'za (Paxta)',
                          prefixIcon: const Icon(Icons.eco_rounded),
                          border: const OutlineInputBorder(),
                          suffixIcon: PopupMenuButton<String>(
                            icon: const Icon(Icons.arrow_drop_down_rounded, size: 28),
                            onSelected: (String value) {
                              cropController.text = value;
                            },
                            itemBuilder: (BuildContext context) {
                              return <String>[
                                'G\'o\'za (Paxta)',
                                'Bug\'doy',
                                'Arpa',
                                'Makkajo\'xori',
                                'Sholi',
                                'Kartoshka',
                                'Sabzi',
                                'Piyoz',
                                'Pomidor',
                                'Bodring',
                                'Qovun',
                                'Tarvuz',
                                'Bedavor',
                                'Soya',
                                'Kunjut',
                                'Kungaboqar',
                                'Olma',
                                'Uzum',
                                'O\'rik',
                                'Shaftoli'
                              ].map<PopupMenuItem<String>>((String value) {
                                return PopupMenuItem<String>(
                                  value: value,
                                  child: Text(value),
                                );
                              }).toList();
                            },
                          ),
                        ),
                        validator: (v) => v == null || v.isEmpty ? 'Ekin turini kiriting' : null,
                      ),
                      const SizedBox(height: 20),

                      // DEVICE MOCKUP CONTAINER
                      const Text(
                        'Detektor ko\'rsatkichlarini kiriting:',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Colors.black54),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFF2E2E2E), // Matte black plastic body
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFF1F1F1F), width: 4),
                          boxShadow: const [
                            BoxShadow(
                              color: Colors.black26,
                              blurRadius: 8,
                              offset: Offset(0, 4),
                            )
                          ],
                        ),
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                        child: Column(
                          children: [
                            // 1. Purple IR lens / sensor block at top center
                            Container(
                              width: 44,
                              height: 12,
                              decoration: const BoxDecoration(
                                color: Color(0xFFB030B0), // Purple lens
                                borderRadius: BorderRadius.vertical(bottom: Radius.circular(6)),
                              ),
                            ),
                            const SizedBox(height: 12),

                            // 2. Green LCD Screen
                            Container(
                              decoration: BoxDecoration(
                                color: const Color(0xFF4CFF4C), // Light green LCD backlight
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: Colors.black, width: 2),
                              ),
                              padding: const EdgeInsets.all(12),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  // Top row with battery status
                                  const Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      SizedBox(),
                                      Icon(Icons.battery_4_bar_rounded, size: 18, color: Color(0xFF0F3A0F)),
                                    ],
                                  ),
                                  
                                  // — Soil —
                                  const Text(
                                    '—— Soil ——',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      color: Color(0xFF0F3A0F),
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      fontStyle: FontStyle.italic,
                                      fontFamily: 'monospace',
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  buildLcdRow(label: 'Fertility', controller: fertilityController, unit: 'µs/cm', hint: '0'),
                                  buildLcdRow(label: 'Moisture', controller: moistureController, unit: '%', hint: '0'),
                                  buildLcdRow(label: 'PH', controller: phController, unit: null, hint: '7.0'),
                                  buildLcdRow(label: 'Temp', controller: tempController, unit: '°C', hint: '25.0'),
                                  
                                  const SizedBox(height: 8),
                                  
                                  // — Environment —
                                  const Text(
                                    '—— Environment ——',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      color: Color(0xFF0F3A0F),
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      fontStyle: FontStyle.italic,
                                      fontFamily: 'monospace',
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  buildLcdRow(label: 'Sunlight', controller: sunlightController, unit: 'LUX', hint: '12000'),
                                  buildLcdRow(label: 'Humidity', controller: humidityController, unit: '%', hint: '45'),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // 4. Stencil device label
                            const Text(
                              'INTELLIGENT SOIL DETECTOR',
                              style: TextStyle(
                                color: Colors.white60,
                                fontWeight: FontWeight.bold,
                                fontSize: 11,
                                letterSpacing: 1.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),

                      // Tooltip explaining conversions
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.blue[50],
                          border: Border.all(color: Colors.blue[100]!),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(Icons.info_outline_rounded, color: Colors.blue, size: 20),
                            SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Qurilma ekranidagi Fertility ko\'rsatkichini µs/cm birligida kiriting. Tizim uni avtomatik tarzda foizga o\'girib saqlaydi.',
                                style: TextStyle(fontSize: 11, color: Colors.black87),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      ElevatedButton(
                        onPressed: () async {
                          if (!formKey.currentState!.validate()) return;

                          // Parse inputs
                          final enteredPh = double.tryParse(phController.text.trim());
                          final enteredFertility = double.tryParse(fertilityController.text.trim());
                          final enteredMoisture = double.tryParse(moistureController.text.trim());
                          final enteredTemp = double.tryParse(tempController.text.trim());
                          final enteredSunlight = double.tryParse(sunlightController.text.trim());
                          final enteredHumidity = double.tryParse(humidityController.text.trim());

                          if (enteredPh == null || enteredPh < 0 || enteredPh > 14) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('PH qiymati 0 va 14 oralig\'ida bo\'lishi kerak.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }
                          if (enteredFertility == null || enteredFertility < 0) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Fertility qiymati noldan kam bo\'lmasligi kerak.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }
                          if (enteredMoisture == null || enteredMoisture < 0 || enteredMoisture > 100) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Namlik (Moisture) foizi 0% va 100% oralig\'ida bo\'lishi kerak.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }
                          if (enteredTemp == null || enteredTemp < -20 || enteredTemp > 60) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Harorat (Temp) -20 va 60 daraja oralig\'ida bo\'lishi kerak.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }
                          if (enteredSunlight == null || enteredSunlight < 0) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Quyosh nuri (Sunlight) qiymatini kiriting.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }
                          if (enteredHumidity == null || enteredHumidity < 0 || enteredHumidity > 100) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Havo namligi (Humidity) foizi 0% va 100% oralig\'ida bo\'lishi kerak.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                            return;
                          }

                          // Convert fertility to percentage
                          final convertedFertility = convertEcToFertilityPercentage(enteredFertility);
                          
                          final api = ref.read(apiServiceProvider);
                          try {
                            final res = await api.createSoilAnalysis(
                              farmId: _selectedFarmId!,
                              geofenceId: selectedGeofenceId,
                              targetCrop: cropController.text.trim(),
                              ph: enteredPh,
                              fertility: convertedFertility,
                              moisture: enteredMoisture,
                              temperature: enteredTemp,
                              sunlight: enteredSunlight,
                              humidity: enteredHumidity,
                              analysisDate: DateTime.now().toIso8601String(),
                            );

                            if (res.data['status'] == 'success' && context.mounted) {
                              Navigator.pop(context);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Yangi tahlil yaratildi. AI tavsiyasini so\'rashingiz mumkin.'),
                                  backgroundColor: Colors.green,
                                ),
                              );
                              _fetchAnalyses(_selectedFarmId!);
                            }
                          } catch (_) {}
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1A3C2A),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        child: const Text('Saqlash', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final farmsState = ref.watch(farmsProvider);
    final isMobile = MediaQuery.of(context).size.width < 600;

    // Auto-select first farm reactively once farms list is loaded
    farmsState.whenData((farms) {
      if (farms.isNotEmpty && _selectedFarmId == null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted && _selectedFarmId == null) {
            setState(() {
              _selectedFarmId = farms[0]['id'];
            });
            _fetchAnalyses(farms[0]['id']);
          }
        });
      }
    });

    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        backgroundColor: _isSelectionMode ? Colors.red[900] : const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: Text(_isSelectionMode 
            ? 'Tanlandi: ${_selectedAnalysisIds.length} ta' 
            : 'Tuproq AI Tahlili'),
        leading: _isSelectionMode
            ? IconButton(
                icon: const Icon(Icons.close_rounded),
                onPressed: () {
                  setState(() {
                    _isSelectionMode = false;
                    _selectedAnalysisIds.clear();
                  });
                },
              )
            : IconButton(
                icon: const Icon(Icons.arrow_back_ios_new_rounded),
                onPressed: () {
                  if (isMobile && _selectedAnalysisDetails != null) {
                    setState(() {
                      _selectedAnalysisDetails = null;
                    });
                  } else {
                    Navigator.pop(context);
                  }
                },
              ),
        actions: [
          if (_isSelectionMode)
            IconButton(
              icon: const Icon(Icons.delete_outline_rounded),
              tooltip: 'Tanlanganlarni o\'chirish',
              onPressed: _isDeleting ? null : _deleteSelectedAnalyses,
            ),
        ],
      ),
      body: Column(
        children: [
          // 1. Farm dropdown selector
          _buildFarmSelector(farmsState),

          // 2. Main body split
          Expanded(
            child: isMobile
                ? (_isLoadingDetails
                    ? const Center(
                        child: CircularProgressIndicator(
                          color: Color(0xFF1A3C2A),
                        ),
                      )
                    : (_selectedAnalysisDetails != null
                        ? _buildAdvisoryPanel(true)
                        : (_isLoadingAnalyses
                            ? const Center(
                                child: CircularProgressIndicator(
                                  color: Color(0xFF1A3C2A),
                                ),
                              )
                            : _buildAnalysesList())))
                : Row(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Left Panel: Analyses List
                      Expanded(
                        flex: 2,
                        child: Container(
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            border: Border(right: BorderSide(color: Colors.black12)),
                          ),
                          child: _isLoadingAnalyses
                              ? const Center(child: CircularProgressIndicator())
                              : _buildAnalysesList(),
                        ),
                      ),

                      // Right Panel: Advisory content
                      Expanded(
                        flex: 3,
                        child: _isLoadingDetails
                            ? const Center(child: CircularProgressIndicator())
                            : _buildAdvisoryPanel(false),
                      ),
                    ],
                  ),
          )
        ],
      ),
      floatingActionButton: (_selectedFarmId != null && isMobile && _selectedAnalysisDetails != null && !_isSelectionMode)
          ? FloatingActionButton.extended(
              heroTag: 'ai_chat_advisor_btn',
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const AiChatScreen(),
                  ),
                );
              },
              backgroundColor: const Color(0xFFFFC107), // Sariq rang
              foregroundColor: const Color(0xFF1A3C2A), // Dark green for great contrast
              icon: const Icon(Icons.smart_toy_rounded, size: 28), // Robot ikonkasi
              label: AnimatedSize(
                duration: const Duration(milliseconds: 200),
                child: _displayedText.isEmpty
                    ? const SizedBox.shrink()
                    : Padding(
                        padding: const EdgeInsets.only(left: 4.0),
                        child: Text(
                          _displayedText,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 11,
                            letterSpacing: 0.5,
                          ),
                        ),
                      ),
              ),
            )
          : null,
    );
  }

  Widget _buildFarmSelector(AsyncValue<List<dynamic>> farmsState) {
    return farmsState.when(
      data: (farms) {
        return Container(
          width: double.infinity,
          color: const Color(0xFF1A3C2A),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: DropdownButton<int>(
            value: _selectedFarmId,
            dropdownColor: const Color(0xFF1A3C2A),
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            underline: const SizedBox(),
            icon: const Icon(Icons.arrow_drop_down_rounded, color: Colors.white),
            items: farms.map<DropdownMenuItem<int>>((f) {
              return DropdownMenuItem<int>(
                value: f['id'],
                child: Text(f['name']),
              );
            }).toList(),
            onChanged: (val) {
              if (val != null) {
                setState(() => _selectedFarmId = val);
                _fetchAnalyses(val);
              }
            },
          ),
        );
      },
      error: (_, __) => const SizedBox(),
      loading: () => const LinearProgressIndicator(),
    );
  }

  Widget _buildAnalysesList() {
    Widget listWidget;
    if (_analyses.isEmpty) {
      listWidget = Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Text(
            'Hozircha tahlillar yo\'q.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey[500], fontSize: 13),
          ),
        ),
      );
    } else {
      listWidget = ListView.builder(
        itemCount: _analyses.length,
        itemBuilder: (context, index) {
          final a = _analyses[index];
          final date = DateTime.tryParse(a['analysis_date']) ?? DateTime.now();
          final formattedDate = '${date.day}.${date.month}.${date.year}';
          final isCompleted = a['status'] == 'completed';
          final isSelectedForDelete = _selectedAnalysisIds.contains(a['id']);

          return ListTile(
            selected: _isSelectionMode 
                ? isSelectedForDelete 
                : (_selectedAnalysisDetails?['id'] == a['id']),
            selectedTileColor: _isSelectionMode 
                ? Colors.red[50] 
                : Colors.grey[100],
            leading: _isSelectionMode
                ? Checkbox(
                    value: isSelectedForDelete,
                    activeColor: Colors.red[900],
                    onChanged: (val) {
                      setState(() {
                        if (val == true) {
                          _selectedAnalysisIds.add(a['id']);
                        } else {
                          _selectedAnalysisIds.remove(a['id']);
                          if (_selectedAnalysisIds.isEmpty) {
                            _isSelectionMode = false;
                          }
                        }
                      });
                    },
                  )
                : null,
            title: Text(
              a['target_crop'],
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(formattedDate, style: const TextStyle(fontSize: 10)),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: isCompleted ? Colors.green[50] : Colors.orange[50],
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    isCompleted ? 'Tayyor' : 'Kutilmoqda',
                    style: TextStyle(
                      fontSize: 8,
                      color: isCompleted ? Colors.green[800] : Colors.orange[800],
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                )
              ],
            ),
            onTap: () {
              if (_isSelectionMode) {
                setState(() {
                  if (isSelectedForDelete) {
                    _selectedAnalysisIds.remove(a['id']);
                    if (_selectedAnalysisIds.isEmpty) {
                      _isSelectionMode = false;
                    }
                  } else {
                    _selectedAnalysisIds.add(a['id']);
                  }
                });
              } else {
                _fetchAnalysisDetails(a['id']);
              }
            },
            onLongPress: () {
              if (!_isSelectionMode) {
                setState(() {
                  _isSelectionMode = true;
                  _selectedAnalysisIds.add(a['id']);
                });
              }
            },
          );
        },
      );
    }

    return Column(
      children: [
        Expanded(child: listWidget),
        if (_selectedFarmId != null && !_isSelectionMode)
          Padding(
            padding: const EdgeInsets.only(left: 16.0, right: 16.0, top: 8.0, bottom: 16.0),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // 1. AI yordamchi tugmasi (Sariq rang)
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const AiChatScreen(),
                        ),
                      );
                    },
                    icon: const Icon(Icons.smart_toy_rounded, size: 24),
                    label: Text(
                      _displayedText.isEmpty ? '24/7 yordamchi' : _displayedText,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.5,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFFC107), // Yellow
                      foregroundColor: const Color(0xFF1A3C2A), // Dark green text
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 2,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                
                // 2. Yangi tahlil qo'shish tugmasi (To'q yashil)
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _showAddAnalysisSheet,
                    icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white),
                    label: const Text(
                      'Yangi tahlil qo\'shish',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.5,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1A3C2A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 2,
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _buildAdvisoryPanel(bool isMobile) {
    if (_selectedAnalysisDetails == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.science_outlined, size: 48, color: Colors.grey[400]),
              const SizedBox(height: 12),
              Text(
                'Maslahat olish uchun chap paneldan tahlil hisobotini tanlang',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey[600], fontSize: 12),
              ),
            ],
          ),
        ),
      );
    }

    final isCompleted = _selectedAnalysisDetails!['status'] == 'completed';
    final rec = _selectedAnalysisDetails!['recommendation'];

    Widget detailContent;
    if (!isCompleted || rec == null) {
      detailContent = Center(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.psychology_rounded, size: 48, color: Colors.orange),
              const SizedBox(height: 12),
              const Text(
                'AI Tavsiyasi Kutilmoqda',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
              ),
              const SizedBox(height: 8),
              Text(
                'Groq Llama3 orqali agronomik tavsiyalarni shakllantiring.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey[500], fontSize: 12),
              ),
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: () => _generateRecommendation(_selectedAnalysisDetails!['id']),
                icon: const Icon(Icons.bolt_rounded),
                label: const Text('AI Maslahatini Olish'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.orange[800],
                  foregroundColor: Colors.white,
                ),
              )
            ],
          ),
        ),
      );
    } else {
      // Parse recommendations
      final crops = rec['recommended_crops'] as List<dynamic>? ?? [];
      final plan = rec['fertilizer_plan'] as Map<String, dynamic>? ?? {};

      detailContent = SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Crop & Status card
            Card(
              color: Colors.green[50],
              child: Padding(
                padding: const EdgeInsets.all(14.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Ekin turi: ${_selectedAnalysisDetails!['target_crop']}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1A3C2A)),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'pH: ${_selectedAnalysisDetails!['ph']} | Unumdorlik: ${_selectedAnalysisDetails!['fertility']}% | Namlik: ${_selectedAnalysisDetails!['moisture']}%',
                      style: const TextStyle(fontSize: 11, color: Colors.black54),
                    )
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Main text advice
            const Text(
              'Agronomik Tavsiyalar',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1A3C2A)),
            ),
            const SizedBox(height: 8),
            Text(
              rec['content'],
              style: const TextStyle(fontSize: 12, height: 1.5, color: Colors.black87),
            ),
            const SizedBox(height: 20),

            // Recommended alternatives
            if (crops.isNotEmpty) ...[
              const Text(
                'Almashlab ekish uchun muqobil ekinlar',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1A3C2A)),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: crops.map<Widget>((c) {
                  return Chip(
                    label: Text('$c', style: const TextStyle(fontSize: 11)),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFF1A3C2A)),
                  );
                }).toList(),
              ),
              const SizedBox(height: 20),
            ],

            // Seasonal fertilizer schedule
            if (plan.isNotEmpty) ...[
              const Text(
                'Mavsumiy O\'g\'itlash Rejasi',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1A3C2A)),
              ),
              const SizedBox(height: 8),
              ...plan.entries.map((entry) {
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          entry.key.toUpperCase(),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.orange),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${entry.value}',
                          style: const TextStyle(fontSize: 11, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                );
              }),
            ],
            // Spacing to allow scrolling content completely past the floating yellow AI button
            const SizedBox(height: 80),
          ],
        ),
      );
    }

    if (isMobile) {
      return Container(
        color: Colors.grey[50],
        child: Column(
          children: [
            // Mobile back bar
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              color: Colors.white,
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Color(0xFF1A3C2A)),
                    onPressed: () {
                      setState(() {
                        _selectedAnalysisDetails = null;
                      });
                    },
                  ),
                  const Text(
                    'Tahlil Tafsilotlari',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1A3C2A)),
                  ),
                ],
              ),
            ),
            Expanded(child: detailContent),
          ],
        ),
      );
    }

    return detailContent;
  }
}
