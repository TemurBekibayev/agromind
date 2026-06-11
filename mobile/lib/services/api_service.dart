import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:developer' as dev;

class ApiService {
  // Mahalliy Docker yoki Laravel dev server uchun IP manzil (localhost yoki emulator IP)
  // Android emulyatorlari uchun 10.0.2.2 Laravel backend portiga ishora qiladi
  static const String _defaultBaseUrl = 'https://agromind.uz.lazzatkafe.uz/api'
      '';
  
  final Dio _dio;
  final FlutterSecureStorage _storage;
  String _baseUrl;

  ApiService({String? baseUrl}) 
      : _dio = Dio(),
        _storage = const FlutterSecureStorage(),
        _baseUrl = baseUrl ?? _defaultBaseUrl {
    _initDio();
  }

  void _initDio() {
    if (!_baseUrl.endsWith('/')) {
      _baseUrl = '$_baseUrl/';
    }
    _dio.options.baseUrl = _baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 15);
    _dio.options.receiveTimeout = const Duration(seconds: 15);
    _dio.options.headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    // Interceptor orqali har bir so'rovga tokenni avtomatik biriktiramiz
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Path boshidagi '/' belgisini olib tashlaymiz, shunda Dio uni baseUrl ga nisbatan to'g'ri biriktiradi
          if (options.path.startsWith('/')) {
            options.path = options.path.substring(1);
          }
          final token = await getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          dev.log('API Request: ${options.method} ${options.path}');
          return handler.next(options);
        },
        onResponse: (response, handler) {
          dev.log('API Response: ${response.statusCode} for ${response.requestOptions.path}');
          return handler.next(response);
        },
        onError: (DioException e, handler) async {
          dev.log('API Error: ${e.response?.statusCode} for ${e.requestOptions.path}');
          // Agar token muddati o'tgan bo'lsa yoki xato bo'lsa (401 Unauthorized), tokenni o'chiramiz
          if (e.response?.statusCode == 401) {
            await clearToken();
          }
          return handler.next(e);
        },
      ),
    );
  }

  // Base URL ni dinamik yangilash (sozlamalar menyusi uchun)
  void updateBaseUrl(String newUrl) {
    if (!newUrl.endsWith('/')) {
      newUrl = '$newUrl/';
    }
    _baseUrl = newUrl;
    _dio.options.baseUrl = newUrl;
  }

  String get baseUrl => _baseUrl;

  // --- Token Boshqaruvi ---

  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  Future<void> clearToken() async {
    await _storage.delete(key: 'auth_token');
  }

  Future<bool> isAuthenticated() async {
    final token = await getToken();
    return token != null;
  }

  // --- API Endpoints ---

  /// Tizimga kirish (Login)
  Future<Response> login(String phone, String password) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'phone': phone,
        'password': password,
        'device_name': 'flutter_mobile_app',
      });
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final token = response.data['token'];
        await saveToken(token);
      }
      return response;
    } catch (e) {
      rethrow;
    }
  }

  /// Tizimdan chiqish (Logout)
  Future<Response?> logout() async {
    try {
      final response = await _dio.post('/auth/logout');
      await clearToken();
      return response;
    } catch (e) {
      await clearToken(); // Xatolik yuz bersa ham mahalliy tokenni tozalaymiz
      rethrow;
    }
  }

  /// Joriy foydalanuvchi ma'lumotlari
  Future<Response> getMe() async {
    return await _dio.get('/auth/me');
  }

  /// Fermalar ro'yxatini olish
  Future<Response> getFarms() async {
    return await _dio.get('/farms');
  }

  /// Yangi ferma qo'shish
  Future<Response> createFarm({
    required String name,
    required String location,
    required double latitude,
    required double longitude,
    required double size,
    required String soilType,
    required int regionId,
  }) async {
    return await _dio.post('/farms', data: {
      'name': name,
      'location': location,
      'latitude': latitude,
      'longitude': longitude,
      'size': size,
      'soil_type': soilType,
      'region_id': regionId,
    });
  }

  /// Fermaga tegishli tuproq tahlillari ro'yxati
  Future<Response> getSoilAnalyses(int farmId) async {
    return await _dio.get('/farms/$farmId/analyses');
  }

  /// Yangi tuproq tahlili kiritish
  Future<Response> createSoilAnalysis({
    required int farmId,
    int? geofenceId,
    required String targetCrop,
    required double ph,
    required double fertility,
    required double moisture,
    required double temperature,
    required double sunlight,
    required double humidity,
    required String analysisDate,
  }) async {
    return await _dio.post('/farms/$farmId/analyses', data: {
      'geofence_id': geofenceId,
      'target_crop': targetCrop,
      'ph': ph,
      'fertility': fertility,
      'moisture': moisture,
      'temperature': temperature,
      'sunlight': sunlight,
      'humidity': humidity,
      'analysis_date': analysisDate,
    });
  }

  /// Sun'iy intellektdan maslahat so'rash
  Future<Response> requestSoilRecommendation(int analysisId) async {
    return await _dio.post('/analyses/$analysisId/recommend');
  }

  /// Tuproq tahlili tafsilotlarini olish
  Future<Response> getSoilAnalysis(int analysisId) async {
    return await _dio.get('/analyses/$analysisId');
  }

  /// Tuproq tahlilini o'chirish
  Future<Response> deleteSoilAnalysis(int analysisId) async {
    return await _dio.delete('/analyses/$analysisId');
  }

  /// Texnikalar ro'yxatini olish
  Future<Response> getVehicles() async {
    return await _dio.get('/vehicles');
  }

  /// Texnikaning oxirgi GPS koordinatasi
  Future<Response> getVehicleLocation(int vehicleId) async {
    return await _dio.get('/vehicles/$vehicleId/location');
  }

  /// Texnikaning 24 soatlik GPS harakat tarixi
  Future<Response> getVehicleHistory(int vehicleId) async {
    return await _dio.get('/vehicles/$vehicleId/history');
  }

  /// Ogohlantirishlarni olish
  Future<Response> getAlerts() async {
    return await _dio.get('/alerts');
  }

  /// Ogohlantirishni bartaraf etish/yopish
  Future<Response> resolveAlert(int alertId) async {
    return await _dio.post('/alerts/$alertId/resolve');
  }

  /// AI Agronomdan chat orqali maslahat so'rash
  Future<Response> askAiAgronomist({
    required String message,
    List<dynamic>? history,
  }) async {
    return await _dio.post('/ai/chat', data: {
      'message': message,
      if (history != null) 'history': history,
    });
  }

  // --- Dehqonlar Suhbat (Chat) API ---

  /// Oxirgi chat xabarlarini olish
  Future<Response> getChatMessages() async {
    return await _dio.get('/chat/messages');
  }

  /// Yangi chat xabarini yuborish
  Future<Response> sendChatMessage(String message) async {
    return await _dio.post('/chat/messages', data: {
      'message': message,
    });
  }

  // --- Texnika va Uskunalar Ijarasi (Listings) API ---

  /// Barcha faol ijaraga beriladigan texnika e'lonlarini olish
  Future<Response> getListings() async {
    return await _dio.get('/listings');
  }

  /// Yangi ijara e'lonini yaratish
  Future<Response> createListing({
    required String title,
    required String description,
    required String equipmentType,
    required String price,
    required String contactPhone,
  }) async {
    return await _dio.post('/listings', data: {
      'title': title,
      'description': description,
      'equipment_type': equipmentType,
      'price': price,
      'contact_phone': contactPhone,
    });
  }

  /// E'lonni o'chirish
  Future<Response> deleteListing(int listingId) async {
    return await _dio.delete('/listings/$listingId');
  }
}
