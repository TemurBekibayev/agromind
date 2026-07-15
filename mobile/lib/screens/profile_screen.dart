import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../providers/providers.dart';
import '../services/localization_service.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  String? _profileImagePath;
  String _nickname = '';
  final TextEditingController _nicknameController = TextEditingController();
  bool _isEditingNickname = false;

  @override
  void initState() {
    super.initState();
    _loadProfileData();
  }

  @override
  void dispose() {
    _nicknameController.dispose();
    super.dispose();
  }

  Future<void> _loadProfileData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      setState(() {
        _profileImagePath = prefs.getString('profile_image_path');
        _nickname = prefs.getString('user_nickname') ?? '';
        _nicknameController.text = _nickname;
      });
    } catch (_) {}
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    try {
      final pickedFile = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
      if (pickedFile != null) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('profile_image_path', pickedFile.path);
        setState(() {
          _profileImagePath = pickedFile.path;
        });
      }
    } catch (_) {}
  }

  Future<void> _removeImage() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('profile_image_path');
      setState(() {
        _profileImagePath = null;
      });
    } catch (_) {}
  }

  void _showImagePickerOptions() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_library_rounded, color: Colors.blue),
                title: const Text('Galereyadan rasm tanlash'),
                onTap: () {
                  Navigator.pop(context);
                  _pickImage();
                },
              ),
              if (_profileImagePath != null)
                ListTile(
                  leading: const Icon(Icons.delete_rounded, color: Colors.red),
                  title: const Text('Rasmni olib tashlash'),
                  onTap: () {
                    Navigator.pop(context);
                    _removeImage();
                  },
                ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _saveNickname() async {
    final text = _nicknameController.text.trim();
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user_nickname', text);
      setState(() {
        _nickname = text;
        _isEditingNickname = false;
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    final userName = user?['name'] ?? ref.tr('user_role');
    final userPhone = user?['phone'] ?? '';
    final userRegion = _getRegionName(user?['region'], user?['region_id']);
    final userDistrict = user?['district'];
    final fullRegionDisplay = userDistrict != null && userDistrict.toString().isNotEmpty
        ? '$userRegion, $userDistrict'
        : userRegion;

    final totalAreaVal = user?['land_area'] ?? user?['total_area'] ?? user?['area'] ?? '0';

    final currentLocale = ref.watch(localeProvider);
    final themeMode = ref.watch(themeModeProvider);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Theme.of(context).colorScheme.onPrimary,
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
                    GestureDetector(
                      onTap: _showImagePickerOptions,
                      child: Stack(
                        children: [
                          CircleAvatar(
                            radius: 45,
                            backgroundColor: Theme.of(context).colorScheme.primary.withOpacity(0.1),
                            backgroundImage: _profileImagePath != null
                                ? FileImage(File(_profileImagePath!))
                                : null,
                            child: _profileImagePath == null
                                ? Icon(
                                    Icons.person_rounded,
                                    size: 55,
                                    color: Theme.of(context).colorScheme.primary,
                                  )
                                : null,
                          ),
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Theme.of(context).colorScheme.primary,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.camera_alt_rounded,
                                size: 14,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ],
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
                    const SizedBox(height: 8),
                    if (!_isEditingNickname)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            _nickname.isNotEmpty ? 'Taxallus: $_nickname' : 'Taxallus qo‘shish',
                            style: TextStyle(
                              fontSize: 13,
                              color: _nickname.isNotEmpty ? Theme.of(context).colorScheme.onSurface : Colors.grey,
                              fontStyle: _nickname.isEmpty ? FontStyle.italic : FontStyle.normal,
                            ),
                          ),
                          const SizedBox(width: 4),
                          GestureDetector(
                            onTap: () {
                              setState(() {
                                _isEditingNickname = true;
                              });
                            },
                            child: Icon(Icons.edit_rounded, size: 14, color: Theme.of(context).colorScheme.primary),
                          ),
                        ],
                      )
                    else
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            width: 140,
                            height: 35,
                            child: TextField(
                              controller: _nicknameController,
                              style: const TextStyle(fontSize: 13),
                              decoration: InputDecoration(
                                hintText: 'Taxallus...',
                                filled: true,
                                fillColor: Theme.of(context).brightness == Brightness.dark ? const Color(0xFF25352A) : Colors.grey[100],
                                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(6),
                                  borderSide: BorderSide.none,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          GestureDetector(
                            onTap: _saveNickname,
                            child: const Icon(Icons.check_circle_rounded, color: Colors.green, size: 22),
                          ),
                          const SizedBox(width: 6),
                          GestureDetector(
                            onTap: () {
                              setState(() {
                                _isEditingNickname = false;
                                _nicknameController.text = _nickname;
                              });
                            },
                            child: const Icon(Icons.cancel_rounded, color: Colors.red, size: 22),
                          ),
                        ],
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Profile detail options
            Text(
              ref.tr('profile_info'),
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.primary),
            ),
            const SizedBox(height: 10),
            _buildInfoTile(Icons.phone_rounded, ref.tr('phone_label'), userPhone),
            _buildInfoTile(Icons.location_on_rounded, ref.tr('region_label'), fullRegionDisplay),
            _buildInfoTile(Icons.landscape_rounded, 'Umumiy yer maydoni', '$totalAreaVal gektar'),
            const SizedBox(height: 20),

            // Settings Section
            Text(
              ref.tr('profile_settings_title'),
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.primary),
            ),
            const SizedBox(height: 10),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: Icon(Icons.language_rounded, color: Theme.of(context).colorScheme.primary),
                title: Text(ref.tr('app_lang')),
                trailing: Text(
                  currentLocale == 'uz'
                      ? 'O\'zbekcha (Lotin)'
                      : currentLocale == 'oz'
                          ? 'Ўзбекcha (Кирилл)'
                          : 'Русский',
                  style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey),
                ),
                onTap: () => _showLanguageSelector(context),
              ),
            ),
            const SizedBox(height: 8),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: Icon(
                  themeMode == ThemeMode.dark ? Icons.dark_mode_rounded : Icons.light_mode_rounded,
                  color: Theme.of(context).colorScheme.primary,
                ),
                title: const Text('Kun / Tun rejimi'),
                trailing: Switch(
                  value: themeMode == ThemeMode.dark,
                  activeColor: Theme.of(context).colorScheme.primary,
                  onChanged: (value) {
                    ref.read(themeModeProvider.notifier).toggleTheme();
                  },
                ),
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
                    trailing: currentLocale == 'uz' ? Icon(Icons.check_circle_rounded, color: Theme.of(context).colorScheme.primary) : null,
                    onTap: () {
                      ref.read(localeProvider.notifier).changeLocale('uz');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('Ўзбекcha (Кирилл)', style: TextStyle(fontWeight: FontWeight.bold)),
                    trailing: currentLocale == 'oz' ? Icon(Icons.check_circle_rounded, color: Theme.of(context).colorScheme.primary) : null,
                    onTap: () {
                      ref.read(localeProvider.notifier).changeLocale('oz');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('Русский', style: TextStyle(fontWeight: FontWeight.bold)),
                    trailing: currentLocale == 'ru' ? Icon(Icons.check_circle_rounded, color: Theme.of(context).colorScheme.primary) : null,
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
        leading: Icon(icon, color: Theme.of(context).colorScheme.primary),
        title: Text(
          label,
          style: const TextStyle(fontSize: 12, color: Colors.grey),
        ),
        subtitle: Text(
          value,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  String _getRegionName(dynamic regionData, dynamic regionId) {
    if (regionData != null && regionData['name'] != null) {
      return regionData['name'].toString();
    }
    final id = int.tryParse('$regionId');
    if (id == null) return '...';
    
    final Map<int, String> regionMap = {
      1: 'Toshkent shahri',
      2: 'Toshkent viloyati',
      3: 'Samarqand viloyati',
      4: 'Farg‘ona viloyati',
      5: 'Andijon viloyati',
      6: 'Namangan viloyati',
      7: 'Buxoro viloyati',
      8: 'Xorazm viloyati',
      9: 'Navoiy viloyati',
      10: 'Qashqadaryo viloyati',
      11: 'Surxondaryo viloyati',
      12: 'Jizzax viloyati',
      13: 'Sirdaryo viloyati',
      14: 'Qoraqalpog‘iston Res.',
    };
    return regionMap[id] ?? '...';
  }
}
