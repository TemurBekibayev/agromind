import 'package:flutter_riverpod/flutter_riverpod.dart';
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
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Tarmoq xatoligi yoki server ishlamayapti.',
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
