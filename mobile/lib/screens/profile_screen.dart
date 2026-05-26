import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../providers/providers.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _hostController = TextEditingController();
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadCurrentHost();
  }

  @override
  void dispose() {
    _hostController.dispose();
    super.dispose();
  }

  Future<void> _loadCurrentHost() async {
    final api = ref.read(apiServiceProvider);
    _hostController.text = api.baseUrl;
  }

  Future<void> _saveHost() async {
    setState(() => _isSaving = true);
    final newUrl = _hostController.text.trim();
    
    // ApiService-ni yangilash
    ref.read(apiServiceProvider).updateBaseUrl(newUrl);

    // SharedPreferences-ga saqlash
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('custom_api_url', newUrl);

    setState(() => _isSaving = false);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('API manzili muvaffaqiyatli yangilandi.'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    final userName = user?['name'] ?? 'Fermer';
    final userPhone = user?['phone'] ?? '';
    final userRegion = user?['region']?['name'] ?? 'Yuklanmoqda...';

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: const Text('Profil va Sozlamalar'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // User card
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.1),
                      child: const Icon(
                        Icons.person_rounded,
                        size: 50,
                        color: Color(0xFF1A3C2A),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      userName,
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Fermer (Dehqon)',
                      style: TextStyle(fontSize: 14, color: Colors.grey[600], fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Profile detail options
            const Text(
              'Ma\'lumotlar',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
            ),
            const SizedBox(height: 10),
            _buildInfoTile(Icons.phone_rounded, 'Telefon raqam', userPhone),
            _buildInfoTile(Icons.location_on_rounded, 'Hudud', userRegion),
            const SizedBox(height: 30),

            // Developer Configurations
            const Text(
              'Tizimni Sozlash (Ishlab chiquvchilar uchun)',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
            ),
            const SizedBox(height: 10),
            Card(
              color: Colors.amber[50],
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.bug_report_rounded, color: Colors.orange),
                        SizedBox(width: 8),
                        Text(
                          'API Server Sozlamalari',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.orange),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      'Mahalliy test serveriga ulanish uchun quyidagi IP manzilni o\'zgartiring.',
                      style: TextStyle(fontSize: 11, color: Colors.grey[800]),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _hostController,
                      decoration: const InputDecoration(
                        labelText: 'API Base URL',
                        hintText: 'http://192.168.1.XX:8000/api',
                        fillColor: Colors.white,
                        filled: true,
                        border: OutlineInputBorder(),
                      ),
                      style: const TextStyle(fontSize: 12, fontFamily: 'monospace'),
                    ),
                    const SizedBox(height: 12),
                    ElevatedButton(
                      onPressed: _isSaving ? null : _saveHost,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1A3C2A),
                        foregroundColor: Colors.white,
                      ),
                      child: _isSaving
                          ? const SizedBox(
                              height: 16,
                              width: 16,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Text('Saqlash va Ulanish', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 40),

            // Logout Button
            ElevatedButton.icon(
              onPressed: () {
                ref.read(authProvider.notifier).logout();
                Navigator.pop(context);
              },
              icon: const Icon(Icons.exit_to_app_rounded),
              label: const Text('Tizimdan Chiqish', style: TextStyle(fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red[800],
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoTile(IconData icon, String label, String value) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(icon, color: const Color(0xFF1A3C2A)),
        title: Text(
          label,
          style: const TextStyle(fontSize: 12, color: Colors.grey),
        ),
        subtitle: Text(
          value,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87),
        ),
      ),
    );
  }
}
