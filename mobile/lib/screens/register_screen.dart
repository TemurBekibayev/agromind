import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  
  int? _selectedRegionId = 1; // Birlamchi: Toshkent viloyati
  bool _obscurePassword = true;
  bool _isLoading = false;

  final List<Map<String, dynamic>> _regions = [
    {'id': 1, 'name': 'Toshkent shahri'},
    {'id': 2, 'name': 'Toshkent viloyati'},
    {'id': 3, 'name': 'Samarqand viloyati'},
    {'id': 4, 'name': 'Farg\'ona viloyati'},
    {'id': 5, 'name': 'Andijon viloyati'},
    {'id': 6, 'name': 'Namangan viloyati'},
    {'id': 7, 'name': 'Buxoro viloyati'},
    {'id': 8, 'name': 'Xorazm viloyati'},
    {'id': 9, 'name': 'Navoiy viloyati'},
    {'id': 10, 'name': 'Qashqadaryo viloyati'},
    {'id': 11, 'name': 'Surxondaryo viloyati'},
    {'id': 12, 'name': 'Jizzax viloyati'},
    {'id': 13, 'name': 'Sirdaryo viloyati'},
    {'id': 14, 'name': 'Qoraqalpog\'iston Respublikasi'},
  ];

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    final success = await ref.read(authProvider.notifier).register(
      name: _nameController.text.trim(),
      phone: _phoneController.text.trim(),
      regionId: _selectedRegionId ?? 1,
      district: 'Amudaryo tumani',
      password: _passwordController.text,
    );

    if (!mounted) return;

    setState(() {
      _isLoading = false;
    });

    if (success) {
      // Muvaffaqiyatli ro'yxatdan o'tish oynasini ko'rsatish
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return Dialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE8F5E9),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_circle_rounded,
                      color: Color(0xFF2E7D32),
                      size: 64,
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Text(
                    'Muvaffaqiyatli!',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Ro\'yxatdan o\'tish muvaffaqiyatli yakunlandi. Endi tizimga kirishingiz mumkin.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 14,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop(); // Dialog yopiladi
                      // Login oynasiga ma'lumotlarni qaytarib pop qiladi
                      Navigator.of(this.context).pop({
                        'phone': _phoneController.text.trim(),
                        'password': _passwordController.text,
                      });
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1A3C2A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                    child: const Text(
                      'OK',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      );
    } else {
      final errorMsg = ref.read(authProvider).errorMessage ?? 'Ro\'yxatdan o\'tishda xatolik yuz berdi.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMsg),
          backgroundColor: Colors.red[800],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 10),
                // Header
                const Text(
                  'Ro\'yxatdan o\'tish',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1A3C2A),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'AgroMind tizimida yangi hisob yaratish',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 36),

                // Name Input
                TextFormField(
                  controller: _nameController,
                  keyboardType: TextInputType.name,
                  decoration: InputDecoration(
                    labelText: 'To\'liq ismingiz',
                    prefixIcon: const Icon(Icons.person_rounded, color: Color(0xFF1A3C2A)),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Ismingizni kiriting';
                    }
                    if (value.trim().length < 3) {
                      return 'Ism kamida 3 ta harfdan iborat bo\'lishi kerak';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Phone Input
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: InputDecoration(
                    labelText: 'Telefon raqam',
                    hintText: '998XXXXXXXXX',
                    prefixIcon: const Icon(Icons.phone_iphone_rounded, color: Color(0xFF1A3C2A)),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Telefon raqamingizni kiriting';
                    }
                    if (!RegExp(r'^\d{9,12}$').hasMatch(value)) {
                      return 'Noto\'g\'ri format. Masalan: 998901234567';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Region Dropdown
                DropdownButtonFormField<int>(
                  value: _selectedRegionId,
                  decoration: InputDecoration(
                    labelText: 'Hudud (Viloyat)',
                    prefixIcon: const Icon(Icons.location_on_rounded, color: Color(0xFF1A3C2A)),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                    ),
                  ),
                  items: _regions.map((region) {
                    return DropdownMenuItem<int>(
                      value: region['id'],
                      child: Text(region['name']),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setState(() {
                      _selectedRegionId = value;
                    });
                  },
                  validator: (value) {
                    if (value == null) {
                      return 'Hududingizni tanlang';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Password Input
                TextFormField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  decoration: InputDecoration(
                    labelText: 'Parol',
                    prefixIcon: const Icon(Icons.lock_rounded, color: Color(0xFF1A3C2A)),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword ? Icons.visibility_off_rounded : Icons.visibility_rounded,
                        color: Colors.grey,
                      ),
                      onPressed: () {
                        setState(() {
                          _obscurePassword = !_obscurePassword;
                        });
                      },
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Parol kiriting';
                    }
                    if (value.length < 6) {
                      return 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 36),

                // Register Button
                ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1A3C2A),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 2,
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2.5,
                          ),
                        )
                      : const Text(
                          'Ro\'yxatdan O\'tish',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                ),
                const SizedBox(height: 24),

                // Link to Login
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      'Sizda allaqachon hisob bormi? ',
                      style: TextStyle(color: Colors.grey[600], fontSize: 14),
                    ),
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: const Text(
                        'Kirish',
                        style: TextStyle(
                          color: Color(0xFF1A3C2A),
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
