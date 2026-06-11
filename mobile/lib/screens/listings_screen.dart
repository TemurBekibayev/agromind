import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';

class ListingsScreen extends ConsumerStatefulWidget {
  const ListingsScreen({super.key});

  @override
  ConsumerState<ListingsScreen> createState() => _ListingsScreenState();
}

class _ListingsScreenState extends ConsumerState<ListingsScreen> {
  String _selectedCategory = 'Barchasi';
  final List<String> _categories = [
    'Barchasi',
    'Traktor',
    'Plug',
    'Kultivator',
    'Kombayn',
    'Tirkama',
    'Sevalka',
    'Boshqa'
  ];

  IconData _getCategoryIcon(String type) {
    switch (type.toLowerCase()) {
      case 'traktor':
        return Icons.agriculture_rounded;
      case 'plug':
        return Icons.grid_4x4_rounded;
      case 'kultivator':
        return Icons.grass_rounded;
      case 'kombayn':
        return Icons.commute_rounded;
      case 'tirkama':
        return Icons.local_shipping_rounded;
      case 'sevalka':
        return Icons.grain_rounded;
      default:
        return Icons.handyman_rounded;
    }
  }

  void _showAddListingBottomSheet(BuildContext context, Map<String, dynamic>? currentUser) {
    final titleController = TextEditingController();
    final descriptionController = TextEditingController();
    final priceController = TextEditingController();
    final phoneController = TextEditingController(text: currentUser?['phone'] ?? '');
    String selectedType = 'Traktor';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(24),
              topRight: Radius.circular(24),
            ),
          ),
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Yangi E\'lon Joylashtirish',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                DropdownButtonFormField<String>(
                  value: selectedType,
                  decoration: const InputDecoration(
                    labelText: 'Texnika/Uskuna turi',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.category_outlined),
                  ),
                  items: _categories
                      .where((cat) => cat != 'Barchasi')
                      .map((cat) => DropdownMenuItem(value: cat, child: Text(cat)))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) selectedType = val;
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: titleController,
                  decoration: const InputDecoration(
                    labelText: 'E\'lon sarlavhasi',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.title_rounded),
                    hintText: 'Masalan: Chizel ijaraga beriladi',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: priceController,
                  decoration: const InputDecoration(
                    labelText: 'Ijara narxi',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.payments_outlined),
                    hintText: 'Masalan: 150 000 so\'m/kun yoki Kelishuv asosida',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Bog\'lanish uchun telefon',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.phone_rounded),
                    hintText: '998901234567',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: descriptionController,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Batafsil tavsif',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                    hintText: 'Texnika holati, yetkazib berish shartlari va boshqa ma\'lumotlarni kiriting...',
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () async {
                    final title = titleController.text.trim();
                    final price = priceController.text.trim();
                    final phone = phoneController.text.trim();
                    final description = descriptionController.text.trim();

                    if (title.isEmpty || price.isEmpty || phone.isEmpty || description.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Iltimos, barcha maydonlarni to\'ldiring.'),
                          backgroundColor: Colors.orange,
                        ),
                      );
                      return;
                    }

                    Navigator.pop(context);
                    
                    final success = await ref.read(listingsProvider.notifier).addListing(
                          title: title,
                          description: description,
                          equipmentType: selectedType,
                          price: price,
                          contactPhone: phone,
                        );

                    if (success) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('E\'lon muvaffaqiyatli qo\'shildi!'),
                            backgroundColor: Colors.green,
                          ),
                        );
                      }
                    } else {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('E\'lon qo\'shishda xatolik yuz berdi.'),
                            backgroundColor: Colors.red,
                          ),
                        );
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1A3C2A),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('E\'lonni chop etish', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _contactSeller(BuildContext context, String phone, String ownerName) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(ownerName),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Fermer bilan bog\'lanish uchun telefon raqami:'),
              const SizedBox(height: 10),
              SelectableText(
                phone,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                Clipboard.setData(ClipboardData(text: phone));
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Telefon raqami buferga nusxalandi.'),
                    backgroundColor: Colors.green,
                  ),
                );
              },
              child: const Text('Nusxalash', style: TextStyle(color: Color(0xFF1A3C2A), fontWeight: FontWeight.bold)),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Yopish', style: TextStyle(color: Colors.grey)),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final listingsState = ref.watch(listingsProvider);
    final authState = ref.watch(authProvider);
    final currentUser = authState.user;

    ref.listen<bool>(shouldShowAddListingProvider, (previous, next) {
      if (next) {
        ref.read(shouldShowAddListingProvider.notifier).state = false;
        // Kichik kechikish bilan ko'rsatamiz, ekran to'liq ochilib olishi uchun
        Future.delayed(const Duration(milliseconds: 100), () {
          if (mounted) {
            _showAddListingBottomSheet(context, currentUser);
          }
        });
      }
    });

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        elevation: 1,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Texnika va Uskunalar Hamkorligi',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            Text(
              'Bo\'sh turgan jihozlarni ijaraga berish va olish',
              style: TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(listingsProvider.notifier).fetchListings(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Kategoriya filtri
          Container(
            height: 55,
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _categories.length,
              itemBuilder: (context, index) {
                final category = _categories[index];
                final isSelected = category == _selectedCategory;
                return Padding(
                  padding: const EdgeInsets.only(right: 8.0),
                  child: ChoiceChip(
                    label: Text(category),
                    selected: isSelected,
                    onSelected: (val) {
                      if (val) {
                        setState(() {
                          _selectedCategory = category;
                        });
                      }
                    },
                    selectedColor: const Color(0xFF1A3C2A).withOpacity(0.15),
                    checkmarkColor: const Color(0xFF1A3C2A),
                    labelStyle: TextStyle(
                      color: isSelected ? const Color(0xFF1A3C2A) : Colors.black87,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                    ),
                    backgroundColor: Colors.white,
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
          ),
          Expanded(
            child: listingsState.when(
              data: (listings) {
                // Kategoriya bo'yicha filterlash
                final filteredListings = _selectedCategory == 'Barchasi'
                    ? listings
                    : listings.where((l) => l['equipment_type'] == _selectedCategory).toList();

                if (filteredListings.isEmpty) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.engineering_outlined, size: 64, color: Colors.grey[400]),
                          const SizedBox(height: 16),
                          const Text(
                            'E\'lonlar mavjud emas',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black54),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _selectedCategory == 'Barchasi'
                                ? 'Hozircha hech kim e\'lon joylashtirmagan.'
                                : 'Ushbu turdagi texnikalar bo\'yicha e\'lonlar topilmadi.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 13, color: Colors.black38),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                return RefreshIndicator(
                  onRefresh: () => ref.read(listingsProvider.notifier).fetchListings(),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16.0),
                    physics: const AlwaysScrollableScrollPhysics(),
                    itemCount: filteredListings.length,
                    itemBuilder: (context, index) {
                      final item = filteredListings[index];
                      final owner = item['user'] ?? {};
                      final ownerName = owner['name'] ?? 'Fermer';
                      final phone = item['contact_phone'] ?? '';
                      final isOwner = owner['id'] == currentUser?['id'];
                      final regionName = owner['region']?['name'] ?? '';
                      final district = owner['district'] ?? '';
                      
                      String location = 'Hudud ko\'rsatilmagan';
                      if (regionName.isNotEmpty) {
                        location = regionName;
                        if (district.isNotEmpty) {
                          location += ', $district';
                        }
                      }

                      return Card(
                        margin: const EdgeInsets.only(bottom: 16),
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CircleAvatar(
                                    backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.08),
                                    foregroundColor: const Color(0xFF1A3C2A),
                                    radius: 22,
                                    child: Icon(_getCategoryIcon(item['equipment_type'] ?? '')),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          item['title'] ?? 'Texnika',
                                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.black87),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          'E\'lon beruvchi: $ownerName • $location',
                                          style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (isOwner)
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent),
                                      onPressed: () async {
                                        final deleteConfirm = await showDialog<bool>(
                                          context: context,
                                          builder: (context) => AlertDialog(
                                            title: const Text('E\'lonni o\'chirish'),
                                            content: const Text('Haqiqatdan ham ushbu e\'lonni o\'chirmoqchimisiz?'),
                                            actions: [
                                              TextButton(
                                                onPressed: () => Navigator.pop(context, false),
                                                child: const Text('Yo\'q', style: TextStyle(color: Colors.grey)),
                                              ),
                                              TextButton(
                                                onPressed: () => Navigator.pop(context, true),
                                                child: const Text('Ha, o\'chirish', style: TextStyle(color: Colors.red)),
                                              ),
                                            ],
                                          ),
                                        );

                                        if (deleteConfirm == true) {
                                          final done = await ref.read(listingsProvider.notifier).delete(item['id']);
                                          if (done && context.mounted) {
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(
                                                content: Text('E\'lon muvaffaqiyatli o\'chirildi.'),
                                                backgroundColor: Colors.green,
                                              ),
                                            );
                                          }
                                        }
                                      },
                                    ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Text(
                                item['description'] ?? '',
                                style: const TextStyle(fontSize: 13, color: Colors.black54, height: 1.4),
                              ),
                              const SizedBox(height: 14),
                              const Divider(height: 1, thickness: 0.5),
                              const SizedBox(height: 12),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Ijara narxi:',
                                        style: TextStyle(fontSize: 11, color: Colors.grey[500]),
                                      ),
                                      const SizedBox(height: 2),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFF2E6F40).withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          item['price'] ?? 'Kelishuv asosida',
                                          style: const TextStyle(
                                            fontSize: 13,
                                            fontWeight: FontWeight.bold,
                                            color: Color(0xFF2E6F40),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  ElevatedButton.icon(
                                    onPressed: () => _contactSeller(context, phone, ownerName),
                                    icon: const Icon(Icons.phone_rounded, size: 16),
                                    label: const Text('Bog\'lanish'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(0xFF1A3C2A),
                                      foregroundColor: Colors.white,
                                      elevation: 0,
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                );
              },
              error: (e, __) => Center(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text(
                        'E\'lonlarni yuklashda xatolik yuz berdi.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.red),
                      ),
                      const SizedBox(height: 8),
                      ElevatedButton(
                        onPressed: () => ref.read(listingsProvider.notifier).fetchListings(),
                        child: const Text('Qayta urinish'),
                      ),
                    ],
                  ),
                ),
              ),
              loading: () => const Center(
                child: CircularProgressIndicator(
                  color: Color(0xFF1A3C2A),
                ),
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddListingBottomSheet(context, currentUser),
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: const Text('E\'lon berish', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
    );
  }
}
