import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';
import '../services/localization_service.dart';

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

    final userName = user?['name'] ?? ref.tr('user_role');
    final userPhone = user?['phone'] ?? '';
    final userRegion = user?['region']?['name'] ?? '...';
    final userDistrict = user?['district'];
    final fullRegionDisplay = userDistrict != null && userDistrict.toString().isNotEmpty
        ? '$userRegion, $userDistrict'
        : userRegion;

    final currentLocale = ref.watch(localeProvider);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        title: Text(ref.tr('profile_settings')),
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
                      ref.tr('user_role'),
                      style: TextStyle(fontSize: 14, color: Colors.grey[600], fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Profile detail options
            Text(
              ref.tr('profile_info'),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
            ),
            const SizedBox(height: 10),
            _buildInfoTile(Icons.phone_rounded, ref.tr('phone_label'), userPhone),
            _buildInfoTile(Icons.location_on_rounded, ref.tr('region_label'), fullRegionDisplay),
            const SizedBox(height: 20),

            // Settings Section
            Text(
              ref.tr('profile_settings_title'),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
            ),
            const SizedBox(height: 10),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: const Icon(Icons.language_rounded, color: Color(0xFF1A3C2A)),
                title: Text(ref.tr('app_lang')),
                trailing: Text(
                  currentLocale == 'uz'
                      ? 'O\'zbekcha (Lotin)'
                      : currentLocale == 'oz'
                          ? 'Ўзбекча (Кирилл)'
                          : 'Русский',
                  style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey),
                ),
                onTap: () => _showLanguageSelector(context),
              ),
            ),
            const SizedBox(height: 40),

            // Logout Button
            ElevatedButton.icon(
              onPressed: () {
                ref.read(authProvider.notifier).logout();
                ref.invalidate(farmsProvider);
                ref.invalidate(vehiclesProvider);
                ref.invalidate(alertsProvider);
                ref.invalidate(chatMessagesProvider);
                ref.invalidate(listingsProvider);
                if (Navigator.canPop(context)) {
                  Navigator.pop(context);
                }
              },
              icon: const Icon(Icons.exit_to_app_rounded),
              label: Text(ref.tr('logout'), style: const TextStyle(fontWeight: FontWeight.bold)),
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

  void _showLanguageSelector(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(20),
          topRight: Radius.circular(20),
        ),
      ),
      builder: (context) {
        return Consumer(
          builder: (context, ref, _) {
            final currentLocale = ref.watch(localeProvider);
            return Container(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  ListTile(
                    title: const Text('O\'zbekcha (Lotin)', style: TextStyle(fontWeight: FontWeight.bold)),
                    trailing: currentLocale == 'uz' ? const Icon(Icons.check_circle_rounded, color: Color(0xFF1A3C2A)) : null,
                    onTap: () {
                      ref.read(localeProvider.notifier).changeLocale('uz');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('Ўзбекча (Кирилл)', style: TextStyle(fontWeight: FontWeight.bold)),
                    trailing: currentLocale == 'oz' ? const Icon(Icons.check_circle_rounded, color: Color(0xFF1A3C2A)) : null,
                    onTap: () {
                      ref.read(localeProvider.notifier).changeLocale('oz');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('Русский', style: TextStyle(fontWeight: FontWeight.bold)),
                    trailing: currentLocale == 'ru' ? const Icon(Icons.check_circle_rounded, color: Color(0xFF1A3C2A)) : null,
                    onTap: () {
                      ref.read(localeProvider.notifier).changeLocale('ru');
                      Navigator.pop(context);
                    },
                  ),
                ],
              ),
            );
          },
        );
      },
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
