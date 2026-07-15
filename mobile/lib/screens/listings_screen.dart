import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';
import '../services/localization_service.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'package:url_launcher/url_launcher.dart';
import 'private_chat_screen.dart';

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

  String _getCategoryLabel(String category) {
    switch (category.toLowerCase()) {
      case 'barchasi':
        return ref.tr('cat_all');
      case 'traktor':
        return ref.tr('cat_tractor');
      case 'plug':
        return ref.tr('cat_plow');
      case 'chizel':
        return ref.tr('cat_chisel');
      case 'kombayn':
        return ref.tr('cat_harvester');
      case 'kultivator':
        return ref.tr('cat_cultivator');
      case 'sevalka':
      case 'seyalka':
        return ref.tr('cat_seeder');
      case 'tirkama':
        return ref.watch(localeProvider) == 'uz'
            ? 'Tirkama'
            : ref.watch(localeProvider) == 'oz'
                ? 'Тиркама'
                : 'Прицеп';
      case 'boshqa':
        return ref.tr('cat_other');
      default:
        return category;
    }
  }

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
    String? modalErrorMessage;
    XFile? pickedImage;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
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
                        Text(
                          ref.tr('new_listing'),
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
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
                      decoration: InputDecoration(
                        labelText: ref.tr('machinery_type'),
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.category_outlined),
                      ),
                      items: _categories
                          .where((cat) => cat != 'Barchasi')
                          .map((cat) => DropdownMenuItem(value: cat, child: Text(_getCategoryLabel(cat))))
                          .toList(),
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() {
                            selectedType = val;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: titleController,
                      decoration: InputDecoration(
                        labelText: ref.tr('listing_title'),
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.title_rounded),
                        hintText: ref.watch(localeProvider) == 'uz'
                            ? 'Masalan: Chizel ijaraga beriladi'
                            : ref.watch(localeProvider) == 'oz'
                                ? 'Масалан: Чизел ижарага берилади'
                                : 'Например: Сдается чизель в аренду',
                      ),
                      onChanged: (_) {
                        if (modalErrorMessage != null) {
                          setModalState(() {
                            modalErrorMessage = null;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: priceController,
                      decoration: InputDecoration(
                        labelText: ref.tr('rental_price'),
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.payments_outlined),
                        hintText: ref.watch(localeProvider) == 'uz'
                            ? 'Masalan: 150 000 so\'m/kun yoki Kelishuv'
                            : ref.watch(localeProvider) == 'oz'
                                ? 'Масалан: 150 000 сўм/кун ёки Келишув'
                                : 'Например: 150 000 сум/день или Договорная',
                      ),
                      onChanged: (_) {
                        if (modalErrorMessage != null) {
                          setModalState(() {
                            modalErrorMessage = null;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: phoneController,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        labelText: ref.tr('contact_phone'),
                        border: const OutlineInputBorder(),
                        prefixIcon: const Icon(Icons.phone_rounded),
                        hintText: '998901234567',
                      ),
                      onChanged: (_) {
                        if (modalErrorMessage != null) {
                          setModalState(() {
                            modalErrorMessage = null;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: descriptionController,
                      maxLines: 4,
                      decoration: InputDecoration(
                        labelText: ref.tr('details_desc'),
                        border: const OutlineInputBorder(),
                        alignLabelWithHint: true,
                        hintText: ref.watch(localeProvider) == 'uz'
                            ? 'Texnika holati, shartlari va boshqalar...'
                            : ref.watch(localeProvider) == 'oz'
                                ? 'Техника ҳолати, шартлари ва бошқалар...'
                                : 'Введите состояние техники, условия и др...',
                      ),
                      onChanged: (_) {
                        if (modalErrorMessage != null) {
                          setModalState(() {
                            modalErrorMessage = null;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 16),
                    Text(
                      ref.tr('machinery_img'),
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                    ),
                    const SizedBox(height: 8),
                    if (pickedImage != null)
                      Stack(
                        children: [
                          Container(
                            height: 160,
                            width: double.infinity,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(12),
                              image: DecorationImage(
                                image: FileImage(File(pickedImage!.path)),
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                          Positioned(
                            top: 8,
                            right: 8,
                            child: CircleAvatar(
                              backgroundColor: Colors.black54,
                              child: IconButton(
                                icon: const Icon(Icons.delete_rounded, color: Colors.white),
                                onPressed: () {
                                  setModalState(() {
                                    pickedImage = null;
                                  });
                                },
                              ),
                            ),
                          ),
                        ],
                      )
                    else
                      InkWell(
                        onTap: () async {
                          final ImagePicker picker = ImagePicker();
                          final XFile? image = await picker.pickImage(
                            source: ImageSource.gallery,
                            imageQuality: 85,
                            maxWidth: 1024,
                          );
                          if (image != null) {
                            setModalState(() {
                              pickedImage = image;
                            });
                          }
                        },
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          height: 100,
                          decoration: BoxDecoration(
                            color: Colors.grey[100],
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.grey[300]!, style: BorderStyle.solid),
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.add_photo_alternate_rounded, size: 36, color: Colors.grey[600]),
                              const SizedBox(height: 8),
                              Text(
                                ref.tr('choose_gallery'),
                                style: TextStyle(color: Colors.grey[600], fontSize: 13),
                              ),
                            ],
                          ),
                        ),
                      ),
                    const SizedBox(height: 15),
                    if (modalErrorMessage != null) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.orange.withOpacity(0.5)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                modalErrorMessage!,
                                style: const TextStyle(color: Colors.orange, fontSize: 13, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 15),
                    ],
                    ElevatedButton(
                      onPressed: () async {
                        final title = titleController.text.trim();
                        final price = priceController.text.trim();
                        final phone = phoneController.text.trim();
                        final description = descriptionController.text.trim();

                        if (title.isEmpty || price.isEmpty || phone.isEmpty || description.isEmpty) {
                          setModalState(() {
                            modalErrorMessage = ref.tr('field_required');
                          });
                          return;
                        }

                        Navigator.pop(context);

                        final success = await ref.read(listingsProvider.notifier).addListing(
                              title: title,
                              description: description,
                              equipmentType: selectedType,
                              price: price,
                              contactPhone: phone,
                              imagePath: pickedImage?.path,
                            );

                        if (success) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(ref.tr('add_success')),
                                backgroundColor: Colors.green,
                              ),
                            );
                          }
                        } else {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(ref.tr('add_error')),
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
                      child: Text(ref.tr('publish_btn'), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _contactSeller(BuildContext context, String phone, String ownerName, int? ownerId) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                margin: const EdgeInsets.symmetric(vertical: 10),
                width: 40,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2.5),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                child: Text(
                  ownerName,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Text(
                  phone,
                  style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                ),
              ),
              const SizedBox(height: 12),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.phone_rounded, color: Colors.blue),
                title: const Text('Qo‘ng‘iroq qilish'),
                onTap: () async {
                  Navigator.pop(context);
                  final uri = Uri.parse('tel:$phone');
                  if (await canLaunchUrl(uri)) {
                    await launchUrl(uri);
                  }
                },
              ),
              if (ownerId != null)
                ListTile(
                  leading: const Icon(Icons.chat_bubble_outline_rounded, color: Colors.green),
                  title: const Text('Shaxsiy chatda yozish'),
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => PrivateChatScreen(
                          partner: {'id': ownerId, 'name': ownerName},
                        ),
                      ),
                    );
                  },
                ),
              ListTile(
                leading: const Icon(Icons.copy_rounded, color: Colors.orange),
                title: const Text('Raqamni nusxalash'),
                onTap: () {
                  Clipboard.setData(ClipboardData(text: phone));
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Telefon raqami nusxalandi!'),
                      backgroundColor: Colors.green,
                    ),
                  );
                },
              ),
              const SizedBox(height: 10),
            ],
          ),
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
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Theme.of(context).colorScheme.onPrimary,
        elevation: 1,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              ref.tr('rental_listings'),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            Text(
              ref.tr('listings_subtitle'),
              style: const TextStyle(fontSize: 11, color: Colors.white70),
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
                    label: Text(_getCategoryLabel(category)),
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
                          Text(
                            ref.tr('no_listings'),
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black54),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _selectedCategory == 'Barchasi'
                                ? ref.tr('no_listings_desc')
                                : ref.tr('no_category_listings'),
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
                      
                      String location = ref.tr('location_not_specified');
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
                                          '${ref.tr('posted_by')}: $ownerName • $location',
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
                                            title: Text(ref.tr('delete_confirm_title')),
                                            content: Text(ref.tr('delete_confirm_msg')),
                                            actions: [
                                              TextButton(
                                                onPressed: () => Navigator.pop(context, false),
                                                child: Text(ref.tr('delete_no'), style: const TextStyle(color: Colors.grey)),
                                              ),
                                              TextButton(
                                                onPressed: () => Navigator.pop(context, true),
                                                child: Text(ref.tr('delete_yes'), style: const TextStyle(color: Colors.red)),
                                              ),
                                            ],
                                          ),
                                        );

                                        if (deleteConfirm == true) {
                                          final done = await ref.read(listingsProvider.notifier).delete(item['id']);
                                          if (done && context.mounted) {
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              SnackBar(
                                                content: Text(ref.tr('delete_success')),
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
                              if (item['image_path'] != null && item['image_path'].toString().isNotEmpty) ...[
                                const SizedBox(height: 12),
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: Image.network(
                                    item['image_path'].toString(),
                                    height: 180,
                                    width: double.infinity,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) => const SizedBox(),
                                  ),
                                ),
                              ],
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
                                        '${ref.tr('rental_price')}:',
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
                                          item['price'] ?? ref.tr('agreement_price'),
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
                                    onPressed: () => _contactSeller(context, phone, ownerName, owner['id']),
                                    icon: const Icon(Icons.phone_rounded, size: 16),
                                    label: Text(ref.tr('contact_btn')),
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
                      Text(
                        ref.tr('load_error'),
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.red),
                      ),
                      const SizedBox(height: 8),
                      ElevatedButton(
                        onPressed: () => ref.read(listingsProvider.notifier).fetchListings(),
                        child: Text(ref.tr('retry')),
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
        label: Text(ref.tr('post_listing_btn'), style: const TextStyle(fontWeight: FontWeight.bold)),
      ),
    );
  }
}
