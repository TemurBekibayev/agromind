import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
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
  final _farmNameController = TextEditingController();
  final _innController = TextEditingController();
  final _commentController = TextEditingController();
  
  int? _selectedRegionId = 1; // Birlamchi: Toshkent viloyati
  String? _selectedDistrict;
  bool _isFarmer = false;
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

  final Map<int, List<String>> _districts = {
    1: ['Bektemir tumani', 'Mirobod tumani', 'Mirzo Ulug‘bek tumani', 'Olmazor tumani', 'Sergeli tumani', 'Uchtepa tumani', 'Yashnobod tumani', 'Yakkasaroy tumani', 'Yunusobod tumani', 'Chilonzor tumani', 'Shayxontohur tumani', 'Yangihayot tumani'],
    2: ['Bekobod tumani', 'Bo‘ka tumani', 'Bo‘stonliq tumani', 'Zangiota tumani', 'Qibray tumani', 'Quyichirchiq tumani', 'Oqqorg‘on tumani', 'Ohangaron tumani', 'Parkent tumani', 'Piskent tumani', 'Toshkent tumani', 'Chinoz tumani', 'Yangiyo‘l tumani', 'O‘rtachirchiq tumani', 'Yuqorichirchiq tumani'],
    3: ['Samarqand shahri', 'Bulung‘ur tumani', 'Jomboy tumani', 'Ishtixon tumani', 'Kattaqo‘rg‘on tumani', 'Narpay tumani', 'Nurobod tumani', 'Oqdaryo tumani', 'Payariq tumani', 'Pastdarg‘om tumani', 'Paxtachi tumani', 'Samarqand tumani', 'Toyloq tumani', 'Urgut tumani', 'Qo‘shrabot tumani'],
    4: ['Farg‘ona shahri', 'Qo‘qon shahri', 'Marg‘ilon shahri', 'Oltiariq tumani', 'Bag‘dod tumani', 'Beshariq tumani', 'Buvayda tumani', 'Dang‘ara tumani', 'Qo‘shtepa tumani', 'Quva tumani', 'Rishton tumani', 'So‘x tumani', 'Toshloq tumani', 'Uchko‘prik tumani', 'O‘zbekiston tumani', 'Farg‘ona tumani', 'Furqat tumani', 'Yozyovon tumani'],
    5: ['Andijon shahri', 'Andijon tumani', 'Asaka tumani', 'Baliqchi tumani', 'Buloqboshi tumani', 'Bo‘ston tumani', 'Jalaquduq tumani', 'Izboskan tumani', 'Qo‘rg‘ontepa tumani', 'Marhamat tumani', 'Oltinko‘l tumani', 'Paxtaobod tumani', 'Ulug‘nor tumani', 'Xo‘jaobod tumani', 'Shahrixon tumani'],
    6: ['Namangan shahri', 'Davlatobod tumani', 'Mingbuloq tumani', 'Kosonsoy tumani', 'Namangan tumani', 'Norin tumani', 'Pop tumani', 'To‘raqorg‘on tumani', 'Uychi tumani', 'Uchqo‘rg‘on tumani', 'Chortoq tumani', 'Chust tumani', 'Yangiqo‘rg‘on tumani'],
    7: ['Buxoro shahri', 'Olot tumani', 'Buxoro tumani', 'Vobkent tumani', 'G‘ijduvon tumani', 'Jondor tumani', 'Kogon tumani', 'Qorako‘l tumani', 'Qorovulbozor tumani', 'Peshku tumani', 'Romitan tumani', 'Shofirkon tumani'],
    8: ['Urganch shahri', 'Xiva shahri', 'Bog‘ot tumani', 'Gurlan tumani', 'Qo‘shko‘pir tumani', 'Urganch tumani', 'Xazorasp tumani', 'Xiva tumani', 'Shovot tumani', 'Yangiariq tumani', 'Yangibozor tumani', 'Tuproqqal‘a tumani'],
    9: ['Navoiy shahri', 'Karmana tumani', 'Konimex tumani', 'Navbahor tumani', 'Nurota tumani', 'Tomdi tumani', 'Uchquduq tumani', 'Xatirchi tumani', 'Qiziltepa tumani'],
    10: ['Qarshi shahri', 'Shahrisabz shahri', 'G‘uzor tumani', 'Dehqonobod tumani', 'Kamashi tumani', 'Karshi tumani', 'Kasbi tumani', 'Kitob tumani', 'Koson tumani', 'Mirishkor tumani', 'Muborak tumani', 'Nishon tumani', 'Shahrisabz tumani', 'Yakkabog‘ tumani', 'Chiroqchi tumani', 'Ko‘kdala tumani'],
    11: ['Termiz shahri', 'Angor tumani', 'Bandixon tumani', 'Boysun tumani', 'Denov tumani', 'Jarqo‘rg‘on tumani', 'Qiziriq tumani', 'Qumqo‘rg‘on tumani', 'Muzrabot tumani', 'Oltinsoy tumani', 'Sariosiyo tumani', 'Sherobod tumani', 'Sho‘rchi tumani', 'Termiz tumani', 'Uzun tumani'],
    12: ['Jizzax shahri', 'Arnasoy tumani', 'Baxmal tumani', 'G‘allaorol tumani', 'Do‘stlik tumani', 'Sharof Rashidov tumani', 'Zafarobod tumani', 'Zarbdor tumani', 'Zomin tumani', 'Mirzacho‘l tumani', 'Paxtakor tumani', 'Forish tumani', 'Yangiobod tumani'],
    13: ['Guliston shahri', 'Boyovut tumani', 'Guliston tumani', 'Mirzaobod tumani', 'Oqoltin tumani', 'Sardoba tumani', 'Sayxunobod tumani', 'Sirdaryo tumani', 'Xovos tumani'],
    14: ['Nukus shahri', 'Amudaryo tumani', 'Beruniy tumani', 'Bo‘zatov tumani', 'Kegeyli tumani', 'Qonliko‘l tumani', 'Qorao‘zak tumani', 'Qo‘ng‘irot tumani', 'Mo‘ynoq tumani', 'Nukus tumani', 'Taxtako‘pir tumani', 'Turtkul tumani', 'Xo‘jayli tumani', 'Chimboy tumani', 'Shumanay tumani', 'Taxiatosh tumani', 'Ellikqal‘a tumani'],
  };

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _farmNameController.dispose();
    _innController.dispose();
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    final api = ref.read(apiServiceProvider);
    try {
      await api.sendAppeal(
        name: _nameController.text.trim(),
        phone: _phoneController.text.trim(),
        farmName: _farmNameController.text.trim(),
        inn: _innController.text.trim(),
        message: _commentController.text.trim(),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ulanish xatoligi: server bilan bog‘lanib bo‘lmadi.')),
      );
      return;
    }

    if (!mounted) return;

    setState(() {
      _isLoading = false;
    });

    // Muvaffaqiyatli ariza topshirish oynasini ko'rsatish
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
                  decoration: const BoxDecoration(
                    color: Color(0xFFE8F5E9),
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
                  'Ariza yuborildi!',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E293B),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Sizning arizangiz adminga muvaffaqiyatli yuborildi. Admin hisobingizni faollashtirgandan so‘ng tizimga kirishingiz mumkin.\n\nSavollar bo‘yicha:\nTel: +998907010875\nTelegram: @Akbar_0703',
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
                    Navigator.of(this.context).pop(); // Login sahifasiga qaytadi
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
                    'Tushunarli',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: Theme.of(context).brightness == Brightness.dark ? Colors.white : const Color(0xFF1E293B)),
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
                  'Ariza topshirish',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1A3C2A),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'AgroMind tizimidan foydalanish uchun ariza qoldiring',
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
                    hintText: '901234567',
                    prefixText: '+998 ',
                    prefixStyle: const TextStyle(color: Colors.black87, fontSize: 16),
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
                    if (!RegExp(r'^\d{9}$').hasMatch(value)) {
                      return 'Noto\'g\'ri format. 9 ta raqam kiriting (Masalan: 901234567)';
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
                      _selectedDistrict = null; // tuman qayta tanlanishi uchun tozalanadi
                    });
                  },
                  validator: (value) {
                    if (value == null) {
                      return 'Hududingizni tanlang';
                    }
                    return null;
                  },
                ),
                
                // District Dropdown
                if (_selectedRegionId != null) ...[
                  const SizedBox(height: 20),
                  DropdownButtonFormField<String>(
                    value: _selectedDistrict,
                    decoration: InputDecoration(
                      labelText: 'Tuman',
                      prefixIcon: const Icon(Icons.location_city_rounded, color: Color(0xFF1A3C2A)),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                      ),
                    ),
                    items: (_districts[_selectedRegionId] ?? []).map((district) {
                      return DropdownMenuItem<String>(
                        value: district,
                        child: Text(district),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        _selectedDistrict = value;
                      });
                    },
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Tumaningizni tanlang';
                      }
                      return null;
                    },
                  ),
                ],

                const SizedBox(height: 20),
                
                // Farmer Checkbox / Switch
                SwitchListTile(
                  title: const Text(
                    'Men fermerman',
                    style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                  ),
                  subtitle: const Text('Fermer xo‘jaligi ma’lumotlarini kiritish uchun yoqing'),
                  value: _isFarmer,
                  activeColor: const Color(0xFF1A3C2A),
                  onChanged: (value) {
                    setState(() {
                      _isFarmer = value;
                    });
                  },
                ),

                if (_isFarmer) ...[
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _farmNameController,
                    decoration: InputDecoration(
                      labelText: 'Fermer xo‘jaligi nomi',
                      prefixIcon: const Icon(Icons.agriculture_rounded, color: Color(0xFF1A3C2A)),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                      ),
                    ),
                    validator: (value) {
                      if (_isFarmer && (value == null || value.isEmpty)) {
                        return 'Fermer xo‘jaligi nomini kiriting';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 20),
                  TextFormField(
                    controller: _innController,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: 'INN (STIR)',
                      hintText: '123456789',
                      prefixIcon: const Icon(Icons.badge_rounded, color: Color(0xFF1A3C2A)),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                      ),
                    ),
                    validator: (value) {
                      if (_isFarmer) {
                        if (value == null || value.isEmpty) {
                          return 'INN kiriting';
                        }
                        if (!RegExp(r'^\d{9}$').hasMatch(value)) {
                          return 'INN 9 ta raqamdan iborat bo‘lishi kerak';
                        }
                      }
                      return null;
                    },
                  ),
                ],
                const SizedBox(height: 20),
                
                // Comment Input
                TextFormField(
                  controller: _commentController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: 'Izoh (Ixtiyoriy)',
                    alignLabelWithHint: true,
                    prefixIcon: const Padding(
                      padding: EdgeInsets.only(bottom: 40.0),
                      child: Icon(Icons.comment_rounded, color: Color(0xFF1A3C2A)),
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 2),
                    ),
                  ),
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
                          'Adminga ariza qoldirish',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                ),
                
                const SizedBox(height: 24),
                
                // Admin contacts card
                Card(
                  color: Colors.green[50],
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: BorderSide(color: Colors.green[200]!),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.contact_support_outlined, color: Color(0xFF1B5E20)),
                            SizedBox(width: 8),
                            Text(
                              'Adminga bog‘lanish',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF1B5E20),
                                fontSize: 16,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          'Ilovadan foydalanish uchun ariza qoldiring. Savollar yuzasidan admin bilan bog‘lanishingiz mumkin:',
                          style: TextStyle(fontSize: 13, color: Colors.black87),
                        ),
                        const SizedBox(height: 12),
                        InkWell(
                          onTap: () async {
                            final Uri phoneUri = Uri(scheme: 'tel', path: '+998907010875');
                            try {
                              if (await canLaunchUrl(phoneUri)) {
                                await launchUrl(phoneUri);
                              } else {
                                throw 'Could not launch';
                              }
                            } catch (_) {
                              Clipboard.setData(const ClipboardData(text: '+998907010875'));
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Telefon raqam nusxalandi! (Qo‘ng‘iroq qilib bo‘lmadi)')),
                              );
                            }
                          },
                          child: const Padding(
                            padding: EdgeInsets.symmetric(vertical: 4.0),
                            child: Row(
                              children: [
                                Icon(Icons.phone, size: 16, color: Color(0xFF1B5E20)),
                                SizedBox(width: 8),
                                Text('+998 90 701 08 75', style: TextStyle(fontWeight: FontWeight.bold)),
                                Spacer(),
                                Icon(Icons.copy, size: 14, color: Colors.grey),
                              ],
                            ),
                          ),
                        ),
                        const Divider(),
                        InkWell(
                          onTap: () async {
                            final Uri tgUri = Uri.parse('https://t.me/Akbar_0703');
                            try {
                              await launchUrl(tgUri, mode: LaunchMode.externalApplication);
                            } catch (_) {
                              Clipboard.setData(const ClipboardData(text: 'https://t.me/Akbar_0703'));
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Telegram havola nusxalandi! (Ilovani ochib bo‘lmadi)')),
                              );
                            }
                          },
                          child: const Padding(
                            padding: EdgeInsets.symmetric(vertical: 4.0),
                            child: Row(
                              children: [
                                Icon(Icons.telegram, size: 16, color: Color(0xFF1B5E20)),
                                SizedBox(width: 8),
                                Text('@Akbar_0703 (Telegram)', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
                                Spacer(),
                                Icon(Icons.copy, size: 14, color: Colors.grey),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
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
