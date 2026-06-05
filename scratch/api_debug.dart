import 'dart:io';
import 'dart:convert';

void main() async {
  final client = HttpClient();
  
  try {
    print('Logging in...');
    final loginRequest = await client.postUrl(Uri.parse('https://agromind.uz.lazzatkafe.uz/api/auth/login'));
    loginRequest.headers.set('Content-Type', 'application/json');
    loginRequest.headers.set('Accept', 'application/json');
    loginRequest.add(utf8.encode(jsonEncode({
      'phone': '998901111111',
      'password': 'secret123',
      'device_name': 'dart_api_debug'
    })));
    
    final loginResponse = await loginRequest.close();
    final loginResponseBody = await loginResponse.transform(utf8.decoder).join();
    final loginData = jsonDecode(loginResponseBody);
    
    if (loginData['status'] != 'success') {
      print('Login failed: $loginData');
      return;
    }
    
    final token = loginData['token'];
    print('Login successful! Fetching vehicles...');
    
    final vehiclesRequest = await client.getUrl(Uri.parse('https://agromind.uz.lazzatkafe.uz/api/vehicles'));
    vehiclesRequest.headers.set('Accept', 'application/json');
    vehiclesRequest.headers.set('Authorization', 'Bearer $token');
    
    final vehiclesResponse = await vehiclesRequest.close();
    final vehiclesResponseBody = await vehiclesResponse.transform(utf8.decoder).join();
    final vehiclesData = jsonDecode(vehiclesResponseBody);
    
    print('Vehicles: ${jsonEncode(vehiclesData)}');
    
    final List vehiclesList = vehiclesData['vehicles'] ?? [];
    for (var v in vehiclesList) {
      final id = v['id'];
      final name = v['name'];
      print('\nFetching location for vehicle ID $id ($name):');
      
      final locRequest = await client.getUrl(Uri.parse('https://agromind.uz.lazzatkafe.uz/api/vehicles/$id/location'));
      locRequest.headers.set('Accept', 'application/json');
      locRequest.headers.set('Authorization', 'Bearer $token');
      
      final locResponse = await locRequest.close();
      final locResponseBody = await locResponse.transform(utf8.decoder).join();
      final locData = jsonDecode(locResponseBody);
      
      print('Response for ID $id: ${jsonEncode(locData)}');
    }
  } catch (e) {
    print('Error: $e');
  } finally {
    client.close();
  }
}
