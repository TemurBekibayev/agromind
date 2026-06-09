import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    final userName = user?['name'] ?? 'Fermer';
    final userPhone = user?['phone'] ?? '';
    final userRegion = user?['region']?['name'] ?? 'Yuklanmoqda...';
    final userDistrict = user?['district'];
    final fullRegionDisplay = userDistrict != null && userDistrict.toString().isNotEmpty
        ? '$userRegion, $userDistrict'
        : userRegion;

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: const Text('Profil va Sozlamalar'),
        automaticallyImplyLeading: false,
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
            _buildInfoTile(Icons.location_on_rounded, 'Hudud', fullRegionDisplay),
            const SizedBox(height: 40),

            // Logout Button
            ElevatedButton.icon(
              onPressed: () {
                ref.read(authProvider.notifier).logout();
                if (Navigator.canPop(context)) {
                  Navigator.pop(context);
                }
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
