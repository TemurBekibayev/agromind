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
  String _displayedText = '';

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

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Default farm tanlash
    final farmsState = ref.watch(farmsProvider);
    farmsState.whenData((farms) {
      if (farms.isNotEmpty && _selectedFarmId == null) {
        setState(() {
          _selectedFarmId = farms[0]['id'];
        });
        _fetchAnalyses(farms[0]['id']);
      }
    });
  }

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
    final cropController = TextEditingController(text: 'G\'o\'za (Paxta)');
    double ph = 6.5;
    double fertility = 55.0;
    double moisture = 60.0;
    double temperature = 25.0;
    double sunlight = 12000.0;
    double humidity = 45.0;

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
                      const Text(
                        'Yangi Tuproq Tahlili Qo\'shish',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                      ),
                      const SizedBox(height: 15),

                      // Crop Input
                      TextFormField(
                        controller: cropController,
                        decoration: const InputDecoration(
                          labelText: 'Yetishtiriladigan ekin turi',
                          prefixIcon: Icon(Icons.eco_rounded),
                        ),
                        validator: (v) => v == null || v.isEmpty ? 'Ekin turini kiriting' : null,
                      ),
                      const SizedBox(height: 15),

                      // pH Slider
                      Text('Tuproq pH darajasi: ${ph.toStringAsFixed(1)}'),
                      Slider(
                        value: ph,
                        min: 0,
                        max: 14,
                        divisions: 140,
                        activeColor: const Color(0xFF1A3C2A),
                        onChanged: (val) => setModalState(() => ph = val),
                      ),

                      // Fertility Slider
                      Text('Unumdorlik darajasi (N,P,K): ${fertility.toStringAsFixed(0)}%'),
                      Slider(
                        value: fertility,
                        min: 0,
                        max: 100,
                        divisions: 100,
                        activeColor: const Color(0xFF1A3C2A),
                        onChanged: (val) => setModalState(() => fertility = val),
                      ),

                      // Moisture Slider
                      Text('Tuproq namligi: ${moisture.toStringAsFixed(0)}%'),
                      Slider(
                        value: moisture,
                        min: 0,
                        max: 100,
                        divisions: 100,
                        activeColor: const Color(0xFF1A3C2A),
                        onChanged: (val) => setModalState(() => moisture = val),
                      ),

                      // Temperature Input
                      TextFormField(
                        initialValue: '25.0',
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Harorat (°C)',
                          prefixIcon: Icon(Icons.thermostat_rounded),
                        ),
                        onChanged: (val) => temperature = double.tryParse(val) ?? 25.0,
                      ),
                      const SizedBox(height: 20),

                      ElevatedButton(
                        onPressed: () async {
                          if (!formKey.currentState!.validate()) return;
                          
                          final api = ref.read(apiServiceProvider);
                          try {
                            final res = await api.createSoilAnalysis(
                              farmId: _selectedFarmId!,
                              targetCrop: cropController.text.trim(),
                              ph: ph,
                              fertility: fertility,
                              moisture: moisture,
                              temperature: temperature,
                              sunlight: sunlight,
                              humidity: humidity,
                              analysisDate: DateTime.now().toIso8601String(),
                            );

                            if (res.data['status'] == 'success' && context.mounted) {
                              Navigator.pop(context);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Yangi tahlil yaratildi. AI tavsiyasini so\'rashingiz mumkin.')),
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

    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: const Text('Tuproq AI Tahlili'),
        leading: IconButton(
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
      floatingActionButton: (_selectedFarmId != null && isMobile && _selectedAnalysisDetails != null)
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

          return ListTile(
            selected: _selectedAnalysisDetails?['id'] == a['id'],
            selectedTileColor: Colors.grey[100],
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
            onTap: () => _fetchAnalysisDetails(a['id']),
          );
        },
      );
    }

    return Column(
      children: [
        Expanded(child: listWidget),
        if (_selectedFarmId != null)
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
            ]
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
