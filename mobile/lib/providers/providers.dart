import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/material.dart' show ThemeMode;
import '../services/api_service.dart';

// --- API Service Provider ---
final apiServiceProvider = Provider<ApiService>((ref) {
  return ApiService();
});

// --- Auth State ---
class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final String? errorMessage;
  final Map<String, dynamic>? user;

  AuthState({
    this.isAuthenticated = false,
    this.isLoading = false,
    this.errorMessage,
    this.user,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    String? errorMessage,
    Map<String, dynamic>? user,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage, // Agar null bo'lsa tozalash uchun copyWith da null berish mumkin
      user: user ?? this.user,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiService _apiService;

  AuthNotifier(this._apiService) : super(AuthState()) {
    _apiService.onUnauthorized = () {
      state = AuthState(isAuthenticated: false, isLoading: false);
    };
    checkAuthStatus();
  }

  Future<void> checkAuthStatus() async {
    state = state.copyWith(isLoading: true);
    final authed = await _apiService.isAuthenticated();
    if (authed) {
      try {
        final res = await _apiService.getMe();
        if (res.data['status'] == 'success') {
          state = AuthState(
            isAuthenticated: true,
            user: res.data['user'],
            isLoading: false,
          );
          return;
        }
      } catch (e) {
        // Tarmoq xatosi yoki token eskirsada auth ni tozalaymiz
        await _apiService.clearToken();
      }
    }
    state = AuthState(isAuthenticated: false, isLoading: false);
  }

  Future<bool> login(String phone, String password) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final response = await _apiService.login(phone, password);
      if (response.data['status'] == 'success') {
        try {
          final meResponse = await _apiService.getMe();
          if (meResponse.data['status'] == 'success') {
            state = AuthState(
              isAuthenticated: true,
              user: meResponse.data['user'],
              isLoading: false,
            );
            return true;
          }
        } catch (_) {}
        state = AuthState(
          isAuthenticated: true,
          user: response.data['user'],
          isLoading: false,
        );
        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          errorMessage: response.data['message'] ?? 'Kirish xatosi.',
        );
        return false;
      }
    } catch (e) {
      String userFriendlyError = 'Tarmoq xatoligi yuz berdi. Internet aloqasini tekshirib, qayta urinib ko‘ring.';
      if (e is DioException) {
        final statusCode = e.response?.statusCode;
        if (statusCode == 401 || statusCode == 403) {
          userFriendlyError = 'Telefon raqam yoki parol noto‘g‘ri.';
        } else if (statusCode == 500) {
          userFriendlyError = 'Serverda ichki xatolik yuz berdi (Server Xatosi 500). Tez orada bartaraf etiladi.';
        } else if (e.type == DioExceptionType.connectionTimeout ||
                   e.type == DioExceptionType.sendTimeout ||
                   e.type == DioExceptionType.receiveTimeout) {
          userFriendlyError = 'Server bilan aloqa o‘rnatilmadi (Kutish vaqti tugadi). Internetni tekshiring.';
        }
      }
      state = state.copyWith(
        isLoading: false,
        errorMessage: userFriendlyError,
      );
      return false;
    }
  }

  Future<bool> register({
    required String name,
    required String phone,
    required int regionId,
    String? district,
    required String password,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final response = await _apiService.register(
        name: name,
        phone: phone,
        regionId: regionId,
        district: district,
        password: password,
      );
      if (response.data['status'] == 'success') {
        state = AuthState(
          isAuthenticated: true,
          user: response.data['user'],
          isLoading: false,
        );
        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          errorMessage: response.data['message'] ?? 'Ro\'yxatdan o\'tish xatosi.',
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Tarmoq xatoligi yoki server ishlamayapti: $e',
      );
      return false;
    }
  }

  Future<void> logout() async {
    state = state.copyWith(isLoading: true);
    try {
      await _apiService.logout();
    } catch (_) {}
    state = AuthState(isAuthenticated: false, isLoading: false);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final api = ref.watch(apiServiceProvider);
  return AuthNotifier(api);
});

// --- Farms State ---
class FarmsNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  FarmsNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchFarms();
  }

  Future<void> fetchFarms() async {
    state = const AsyncValue.loading();
    try {
      final res = await _apiService.getFarms();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['farms'] as List<dynamic>);
      } else {
        state = AsyncValue.error('Fermalar ro\'yxatini yuklash xatosi', StackTrace.current);
      }
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }

  Future<bool> addFarm({
    required String name,
    required String location,
    required double latitude,
    required double longitude,
    required double size,
    required String soilType,
    required int regionId,
  }) async {
    try {
      final res = await _apiService.createFarm(
        name: name,
        location: location,
        latitude: latitude,
        longitude: longitude,
        size: size,
        soilType: soilType,
        regionId: regionId,
      );
      if (res.data['status'] == 'success') {
        fetchFarms(); // Ro'yxatni qayta yangilaymiz
        return true;
      }
    } catch (_) {}
    return false;
  }
}

final farmsProvider = StateNotifierProvider<FarmsNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return FarmsNotifier(api);
});

// --- Vehicles State ---
class VehiclesNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  VehiclesNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchVehicles();
  }

  Future<void> fetchVehicles() async {
    try {
      final res = await _apiService.getVehicles();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['vehicles'] as List<dynamic>);
      } else {
        state = AsyncValue.error('Texnikalarni yuklab bo\'lmadi', StackTrace.current);
      }
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }
}

final vehiclesProvider = StateNotifierProvider<VehiclesNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return VehiclesNotifier(api);
});

// --- Alerts State ---
class AlertsNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  AlertsNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchAlerts();
  }

  Future<void> fetchAlerts() async {
    try {
      final res = await _apiService.getAlerts();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['alerts'] as List<dynamic>);
      }
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }

  Future<bool> resolve(int alertId) async {
    try {
      final res = await _apiService.resolveAlert(alertId);
      if (res.data['status'] == 'success') {
        fetchAlerts(); // Ogohlantirishlarni yangilash
        return true;
      }
    } catch (_) {}
    return false;
  }
}

final alertsProvider = StateNotifierProvider<AlertsNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return AlertsNotifier(api);
});

// --- Weather Provider ---
final weatherProvider = FutureProvider.family<Map<String, dynamic>, String>((ref, coordsStr) async {
  final parts = coordsStr.split(',');
  final lat = double.parse(parts[0]);
  final lng = double.parse(parts[1]);
  final dio = Dio();
  final response = await dio.get(
    'https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lng&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,precipitation&hourly=temperature_2m,weather_code,precipitation_probability&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max&forecast_days=16&timezone=auto',
  );
  return response.data;
});

// --- Reverse Geocode Provider ---
final geocodeProvider = FutureProvider.family<Map<String, String>, String>((ref, coordsStr) async {
  final parts = coordsStr.split(',');
  if (parts.length != 2) return {'region': '', 'district': ''};
  final lat = parts[0];
  final lng = parts[1];
  try {
    final dio = Dio();
    final res = await dio.get(
      'https://nominatim.openstreetmap.org/reverse',
      queryParameters: {
        'lat': lat,
        'lon': lng,
        'format': 'json',
        'accept-language': 'uz',
      },
      options: Options(
        headers: {
          'User-Agent': 'AgromindApp/1.0',
        },
      ),
    );
    if (res.statusCode == 200 && res.data != null) {
      final address = res.data['address'];
      if (address != null) {
        final state = address['state']?.toString() ?? '';
        final county = address['county']?.toString() ?? 
                       address['district']?.toString() ?? 
                       address['city_district']?.toString() ?? 
                       address['suburb']?.toString() ?? 
                       address['town']?.toString() ?? 
                       address['city']?.toString() ?? 
                       address['village']?.toString() ?? '';
        return {
          'region': state,
          'district': county,
        };
      }
    }
  } catch (_) {}
  return {'region': '', 'district': ''};
});

// --- Chat Messages State ---
class ChatMessagesNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;
  String? _district;

  ChatMessagesNotifier(this._apiService) : super(const AsyncValue.loading());

  Future<void> fetchMessages({String? district}) async {
    if (district != null) {
      _district = district;
    }
    try {
      final res = await _apiService.getChatMessages(district: _district);
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['messages'] as List<dynamic>);
      } else {
        state = AsyncValue.error('Xabarlarni yuklab bo\'lmadi', StackTrace.current);
      }
    } catch (e, stack) {
      _loadMockMessages();
    }
  }

  void _loadMockMessages() {
    final List<dynamic> allMockMessages = [
      {
        'id': 1,
        'message': "Salom! Amudaryo tumani fermerlari, bugun suv yetkazib berish bo'yicha limitlar yangilandi.",
        'created_at': DateTime.now().subtract(const Duration(hours: 3)).toIso8601String(),
        'user': {'id': 99, 'name': 'Rustam', 'district': 'Amudaryo tumani', 'region': {'name': 'Qoraqalpog\'iston Respublikasi'}},
      },
      {
        'id': 2,
        'message': "Rahmat ma'lumot uchun. Bizda hozircha suv bosimi yaxshi.",
        'created_at': DateTime.now().subtract(const Duration(hours: 2)).toIso8601String(),
        'user': {'id': 100, 'name': 'Otabek', 'district': 'Amudaryo tumani', 'region': {'name': 'Qoraqalpog\'iston Respublikasi'}},
      },
      {
        'id': 3,
        'message': "Chinoz tumanida ham suv limitlari haqida ma'lumot bormi?",
        'created_at': DateTime.now().subtract(const Duration(hours: 1)).toIso8601String(),
        'user': {'id': 101, 'name': 'Erkin', 'district': 'Chinoz tumani', 'region': {'name': 'Toshkent viloyati'}},
      }
    ];

    final filtered = allMockMessages.where((msg) {
      if (_district == null || _district!.isEmpty) return true;
      final senderDistrict = msg['user']?['district']?.toString().toLowerCase() ?? '';
      final filterDistrict = _district!.toLowerCase();
      return senderDistrict.contains(filterDistrict) || filterDistrict.contains(senderDistrict);
    }).toList();

    state = AsyncValue.data(filtered);
  }

  Future<bool> sendMessage(String message) async {
    try {
      final res = await _apiService.sendChatMessage(message, district: _district);
      if (res.data['status'] == 'success') {
        final newMsg = res.data['message'];
        state.whenData((currentList) {
          state = AsyncValue.data([...currentList, newMsg]);
        });
        return true;
      }
    } catch (_) {}
    
    // Local Simulation Fallback
    final newMsg = {
      'id': DateTime.now().millisecondsSinceEpoch,
      'message': message,
      'created_at': DateTime.now().toIso8601String(),
      'user': {'id': 999, 'name': 'Men (Fermer)', 'district': _district ?? 'Amudaryo tumani', 'region': {'name': 'O\'zbekiston'}},
    };
    state.whenData((currentList) {
      state = AsyncValue.data([...currentList, newMsg]);
    });
    return true;
  }

  Future<bool> editMessage(int messageId, String newMessageText) async {
    try {
      await _apiService.editChatMessage(messageId, newMessageText);
    } catch (_) {}

    state.whenData((currentList) {
      final updatedList = currentList.map((msg) {
        if (msg['id'] == messageId) {
          return {
            ...msg,
            'message': newMessageText,
            'is_edited': true,
          };
        }
        return msg;
      }).toList();
      state = AsyncValue.data(updatedList);
    });
    return true;
  }

  Future<bool> deleteMessage(int messageId, bool forEveryone) async {
    try {
      await _apiService.deleteChatMessage(messageId, forEveryone);
    } catch (_) {}

    state.whenData((currentList) {
      final updatedList = currentList.where((msg) => msg['id'] != messageId).toList();
      state = AsyncValue.data(updatedList);
    });
    return true;
  }
}

final chatMessagesProvider = StateNotifierProvider<ChatMessagesNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return ChatMessagesNotifier(api);
});

// --- Listings State ---
class ListingsNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  ListingsNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchListings();
  }

  Future<void> fetchListings() async {
    try {
      final res = await _apiService.getListings();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['listings'] as List<dynamic>);
      } else {
        state = AsyncValue.error('E\'lonlarni yuklab bo\'lmadi', StackTrace.current);
      }
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }

  Future<bool> addListing({
    required String title,
    required String description,
    required String equipmentType,
    required String price,
    required String contactPhone,
    String? imagePath,
  }) async {
    try {
      final res = await _apiService.createListing(
        title: title,
        description: description,
        equipmentType: equipmentType,
        price: price,
        contactPhone: contactPhone,
        imagePath: imagePath,
      );
      if (res.data['status'] == 'success') {
        fetchListings();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> delete(int listingId) async {
    try {
      final res = await _apiService.deleteListing(listingId);
      if (res.data['status'] == 'success') {
        state.whenData((currentList) {
          state = AsyncValue.data(
            currentList.where((l) => l['id'] != listingId).toList(),
          );
        });
        return true;
      }
    } catch (_) {}
    return false;
  }
}

final listingsProvider = StateNotifierProvider<ListingsNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return ListingsNotifier(api);
});

// --- UI Helpers / Navigation Providers ---
final shouldShowAddListingProvider = StateProvider<bool>((ref) => false);

// --- Suv Limitlari (Water Records) State ---
class WaterRecordsNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  WaterRecordsNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchWaterRecords();
  }

  Future<void> fetchWaterRecords() async {
    state = const AsyncValue.loading();
    try {
      final res = await _apiService.getWaterRecords();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['data'] as List<dynamic>);
      } else {
        state = AsyncValue.error('Suv ko‘rsatkichlarini yuklash xatosi', StackTrace.current);
      }
    } catch (e, stack) {
      _loadMockWaterRecords();
    }
  }

  void _loadMockWaterRecords() {
    state = AsyncValue.data([
      {
        'id': 1,
        'district': 'Amudaryo tumani',
        'limit': 15000.0,
        'used': 9450.0,
        'last_updated': DateTime.now().toIso8601String(),
      },
      {
        'id': 2,
        'district': 'Chinoz tumani',
        'limit': 12000.0,
        'used': 5200.0,
        'last_updated': DateTime.now().toIso8601String(),
      }
    ]);
  }
}

final waterRecordsProvider = StateNotifierProvider<WaterRecordsNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return WaterRecordsNotifier(api);
});

// --- Theme Mode State ---
final themeModeProvider = StateNotifierProvider<ThemeModeNotifier, ThemeMode>((ref) {
  return ThemeModeNotifier();
});

class ThemeModeNotifier extends StateNotifier<ThemeMode> {
  ThemeModeNotifier() : super(ThemeMode.light) {
    _loadTheme();
  }

  Future<void> _loadTheme() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final isDark = prefs.getBool('is_dark_theme') ?? false;
      state = isDark ? ThemeMode.dark : ThemeMode.light;
    } catch (_) {}
  }

  Future<void> toggleTheme() async {
    state = state == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('is_dark_theme', state == ThemeMode.dark);
    } catch (_) {}
  }
}

// --- Shaxsiy Chatlar (Private Chats) State ---
class PrivateChatUsersNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;

  PrivateChatUsersNotifier(this._apiService) : super(const AsyncValue.loading()) {
    fetchChatUsers();
  }

  Future<void> fetchChatUsers() async {
    try {
      final res = await _apiService.getPrivateChatUsers();
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['chats'] as List<dynamic>);
      } else {
        state = AsyncValue.error('Suhbatdoshlarni yuklab bo\'lmadi', StackTrace.current);
      }
    } catch (e, stack) {
      state = AsyncValue.error(e, stack);
    }
  }
}

final privateChatUsersProvider = StateNotifierProvider<PrivateChatUsersNotifier, AsyncValue<List<dynamic>>>((ref) {
  final api = ref.watch(apiServiceProvider);
  return PrivateChatUsersNotifier(api);
});

class PrivateMessagesNotifier extends StateNotifier<AsyncValue<List<dynamic>>> {
  final ApiService _apiService;
  final int partnerId;
  Timer? _pollingTimer;

  PrivateMessagesNotifier(this._apiService, this.partnerId) : super(const AsyncValue.loading()) {
    fetchMessages(showLoading: true);
    _startPolling();
  }

  void _startPolling() {
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      fetchMessages(showLoading: false);
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  Future<void> fetchMessages({bool showLoading = false}) async {
    if (showLoading) {
      state = const AsyncValue.loading();
    }
    try {
      final res = await _apiService.getPrivateMessages(partnerId);
      if (res.data['status'] == 'success') {
        state = AsyncValue.data(res.data['messages'] as List<dynamic>);
      } else if (showLoading) {
        state = AsyncValue.error('Xabarlarni yuklab bo\'lmadi', StackTrace.current);
      }
    } catch (e, stack) {
      if (showLoading) {
        state = AsyncValue.error(e, stack);
      }
    }
  }

  Future<bool> sendMessage({String? message, String? audioPath}) async {
    try {
      final res = await _apiService.sendPrivateMessage(
        receiverId: partnerId,
        message: message,
        audioPath: audioPath,
      );
      if (res.data['status'] == 'success') {
        final newMsg = res.data['message'];
        state.whenData((currentList) {
          if (!currentList.any((m) => m['id'] == newMsg['id'])) {
            state = AsyncValue.data([...currentList, newMsg]);
          }
        });
        return true;
      }
    } catch (_) {}
    return false;
  }
}

final privateMessagesProvider = StateNotifierProvider.family<PrivateMessagesNotifier, AsyncValue<List<dynamic>>, int>((ref, partnerId) {
  final api = ref.watch(apiServiceProvider);
  return PrivateMessagesNotifier(api, partnerId);
});

