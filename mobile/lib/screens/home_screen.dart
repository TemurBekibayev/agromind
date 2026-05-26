import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';
import 'gps_map_screen.dart';
import 'soil_analysis_screen.dart';
import 'profile_screen.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  void _navigateTo(BuildContext context, Widget screen) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => screen),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final vehiclesState = ref.watch(vehiclesProvider);
    final alertsState = ref.watch(alertsProvider);

    final userName = authState.user?['name'] ?? 'Fermer';
    final userRegion = authState.user?['region']?['name'] ?? 'O\'zbekiston';

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              userName,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            Row(
              children: [
                const Icon(Icons.location_on_rounded, size: 12, color: Colors.orange),
                const SizedBox(width: 4),
                Text(
                  userRegion,
                  style: const TextStyle(fontSize: 12, color: Colors.white70),
                ),
              ],
            )
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () {
              ref.read(vehiclesProvider.notifier).fetchVehicles();
              ref.read(alertsProvider.notifier).fetchAlerts();
            },
          ),
          IconButton(
            icon: const Icon(Icons.account_circle_rounded),
            onPressed: () => _navigateTo(context, const ProfileScreen()),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.read(vehiclesProvider.notifier).fetchVehicles();
          ref.read(alertsProvider.notifier).fetchAlerts();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. Weather Widget
              _buildWeatherCard(userRegion),
              const SizedBox(height: 20),

              // 2. Quick navigation grid
              const Text(
                'Tezkor Xizmatlar',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
              ),
              const SizedBox(height: 10),
              _buildNavigationGrid(context),
              const SizedBox(height: 25),

              // 3. Vehicles summary card
              const Text(
                'Texnika Monitoringi',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
              ),
              const SizedBox(height: 10),
              _buildVehiclesSummaryCard(vehiclesState),
              const SizedBox(height: 25),

              // 4. Alert Ticker
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Faol Ogohlantirishlar',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                  ),
                  alertsState.when(
                    data: (list) => list.isNotEmpty
                        ? Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.red[100],
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              '${list.length} ta faol',
                              style: const TextStyle(fontSize: 10, color: Colors.red, fontWeight: FontWeight.bold),
                            ),
                          )
                        : const SizedBox(),
                    error: (_, __) => const SizedBox(),
                    loading: () => const SizedBox(),
                  )
                ],
              ),
              const SizedBox(height: 10),
              _buildAlertsFeed(context, ref, alertsState),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildWeatherCard(String region) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFF2A5C43), Color(0xFF1A3C2A)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        padding: const EdgeInsets.all(20),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Bugun Havo Iliq',
                  style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w500),
                ),
                const SizedBox(height: 4),
                const Text(
                  '+26°C',
                  style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  'Yog\'ingarchilik ehtimoli: 5% | $region',
                  style: const TextStyle(color: Colors.white60, fontSize: 11),
                ),
              ],
            ),
            const Column(
              children: [
                Icon(Icons.wb_sunny_rounded, color: Colors.orange, size: 48),
                SizedBox(height: 4),
                Text(
                  'Quyoshli',
                  style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                )
              ],
            )
          ],
        ),
      ),
    );
  }

  Widget _buildNavigationGrid(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.6,
      children: [
        _buildNavCard(
          context,
          'GPS Xarita',
          Icons.map_rounded,
          Colors.blue[700]!,
          const GpsMapScreen(),
        ),
        _buildNavCard(
          context,
          'Tuproq AI',
          Icons.science_rounded,
          Colors.orange[800]!,
          const SoilAnalysisScreen(),
        ),
      ],
    );
  }

  Widget _buildNavCard(
    BuildContext context,
    String title,
    IconData icon,
    Color color,
    Widget targetScreen,
  ) {
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: () => _navigateTo(context, targetScreen),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: color, size: 28),
              Text(
                title,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87),
              )
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildVehiclesSummaryCard(AsyncValue<List<dynamic>> vehiclesState) {
    return vehiclesState.when(
      data: (vehicles) {
        final onlineCount = vehicles.where((v) => v['status_label'] == 'online').length;
        final alertCount = vehicles.where((v) => v['status_label'] == 'problem').length;
        final totalCount = vehicles.length;

        return Card(
          elevation: 1,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatItem('Jami', '$totalCount ta', Colors.grey[700]!),
                _buildStatItem('Online', '$onlineCount ta', Colors.green[700]!),
                _buildStatItem('Xavf', '$alertCount ta', Colors.red[700]!),
              ],
            ),
          ),
        );
      },
      error: (e, __) => const Card(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: Text('Texnikalarni yuklashda xatolik yuz berdi.'),
        ),
      ),
      loading: () => const Card(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
    );
  }

  Widget _buildStatItem(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          label,
          style: TextStyle(fontSize: 12, color: Colors.grey[600], fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color),
        ),
      ],
    );
  }

  Widget _buildAlertsFeed(
    BuildContext context,
    WidgetRef ref,
    AsyncValue<List<dynamic>> alertsState,
  ) {
    return alertsState.when(
      data: (alerts) {
        if (alerts.isEmpty) {
          return Card(
            elevation: 0,
            color: Colors.green[50],
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: const Padding(
              padding: EdgeInsets.all(20.0),
              child: Row(
                children: [
                  Icon(Icons.check_circle_rounded, color: Colors.green),
                  SizedBox(width: 12),
                  Text(
                    'Hech qanday xavf signallari yo\'q.',
                    style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                ],
              ),
            ),
          );
        }

        return ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: alerts.length,
          itemBuilder: (context, index) {
            final alert = alerts[index];
            final vehicleName = alert['vehicle'] != null ? alert['vehicle']['name'] : 'Noma\'lum texnika';
            final type = alert['type'] ?? 'unknown';
            final message = alert['message'] ?? 'Tafsilotlar yo\'q';
            final alertId = alert['id'];

            String typeTitle;
            switch (type) {
              case 'geofence_breach':
                typeTitle = 'Chegara buzilishi';
                break;
              case 'low_fuel':
                typeTitle = 'Kam yoqilg\'i';
                break;
              case 'signal_lost':
                typeTitle = 'Aloqa uzildi';
                break;
              case 'offline':
                typeTitle = 'Oflayn';
                break;
              default:
                typeTitle = 'Ogohlantirish';
            }

            return Card(
              margin: const EdgeInsets.only(bottom: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.red[50],
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.warning_amber_rounded, color: Colors.red),
                ),
                title: Text(
                  '$vehicleName - $typeTitle',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
                subtitle: Text(
                  message,
                  style: const TextStyle(fontSize: 12),
                ),
                trailing: TextButton(
                  onPressed: () async {
                    final solved = await ref.read(alertsProvider.notifier).resolve(alertId);
                    if (solved && context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Ogohlantirish yopildi.'),
                          backgroundColor: Colors.green,
                        ),
                      );
                    }
                  },
                  child: const Text(
                    'Yopish',
                    style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            );
          },
        );
      },
      error: (e, __) => const Card(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: Text('Ogohlantirishlarni yuklashda xatolik yuz berdi.'),
        ),
      ),
      loading: () => const Center(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: CircularProgressIndicator(),
        ),
      ),
    );
  }
}
