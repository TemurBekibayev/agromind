import 'package:flutter/material.dart';

class WeatherDay {
  final DateTime date;
  final String condition;
  final IconData icon;
  final double minTemp;
  final double maxTemp;
  final double windSpeed;
  final int precipitation;
  final int humidity;
  final Color themeColor;

  WeatherDay({
    required this.date,
    required this.condition,
    required this.icon,
    required this.minTemp,
    required this.maxTemp,
    required this.windSpeed,
    required this.precipitation,
    required this.humidity,
    required this.themeColor,
  });
}

class WeatherForecastScreen extends StatelessWidget {
  final String region;

  const WeatherForecastScreen({super.key, required this.region});

  String _getUzbekDayName(DateTime date, int index) {
    if (index == 0) return 'Bugun';
    if (index == 1) return 'Ertaga';
    
    switch (date.weekday) {
      case DateTime.monday:
        return 'Dushanba';
      case DateTime.tuesday:
        return 'Seshanba';
      case DateTime.wednesday:
        return 'Chorshanba';
      case DateTime.thursday:
        return 'Payshanba';
      case DateTime.friday:
        return 'Juma';
      case DateTime.saturday:
        return 'Shanba';
      case DateTime.sunday:
        return 'Yakshanba';
      default:
        return '';
    }
  }

  String _getUzbekMonth(int month) {
    switch (month) {
      case 1: return 'yanvar';
      case 2: return 'fevral';
      case 3: return 'mart';
      case 4: return 'aprel';
      case 5: return 'may';
      case 6: return 'iyun';
      case 7: return 'iyul';
      case 8: return 'avgust';
      case 9: return 'sentabr';
      case 10: return 'oktabr';
      case 11: return 'noyabr';
      case 12: return 'dekabr';
      default: return '';
    }
  }

  List<WeatherDay> _generateForecast() {
    final now = DateTime.now();
    final list = <WeatherDay>[];
    
    // Hash of region name for consistent pseudo-randomness
    final int seed = region.hashCode;
    
    final conditions = [
      {'name': 'Quyoshli', 'icon': Icons.wb_sunny_rounded, 'color': const Color(0xFFFFB300)},
      {'name': 'Qisman bulutli', 'icon': Icons.wb_cloudy_rounded, 'color': const Color(0xFF90A4AE)},
      {'name': 'Bulutli', 'icon': Icons.cloud_rounded, 'color': const Color(0xFF78909C)},
      {'name': 'Yomg\'irli', 'icon': Icons.grain_rounded, 'color': const Color(0xFF4FC3F7)},
      {'name': 'Momaqaldiroq', 'icon': Icons.thunderstorm_rounded, 'color': const Color(0xFFB39DDB)},
    ];

    for (int i = 0; i < 7; i++) {
      final date = now.add(Duration(days: i));
      final int idx = (seed + i) % conditions.length;
      final cond = conditions[idx];
      
      // Temperature range: 17°C - 38°C based on region hash
      final double baseMax = 27 + ((seed + i) % 9);
      final double baseMin = 15 + ((seed + i) % 7);
      
      final double wind = 2.5 + ((seed + i * 2) % 11) * 0.9;
      final int precip = (cond['name'] == 'Yomg\'irli' || cond['name'] == 'Momaqaldiroq') 
          ? 55 + ((seed + i) % 35) 
          : ((seed + i) % 15);
          
      final int hum = 20 + ((seed + i * 3) % 45);

      list.add(
        WeatherDay(
          date: date,
          condition: cond['name'] as String,
          icon: cond['icon'] as IconData,
          minTemp: baseMin,
          maxTemp: baseMax,
          windSpeed: wind,
          precipitation: precip,
          humidity: hum,
          themeColor: cond['color'] as Color,
        ),
      );
    }
    return list;
  }

  @override
  Widget build(BuildContext context) {
    final forecast = _generateForecast();
    final today = forecast[0];

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF1E3A2F), Color(0xFF11221B)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
                      onPressed: () => Navigator.pop(context),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Ob-havo Forecast',
                            style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w500),
                          ),
                          Text(
                            region,
                            style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Today's Large Card (Glassmorphic look)
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: Colors.white.withOpacity(0.12), width: 1.5),
                        ),
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          children: [
                            const Text(
                              'BUGUN',
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 1.5,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Icon(
                              today.icon,
                              size: 72,
                              color: today.themeColor,
                            ),
                            const SizedBox(height: 12),
                            Text(
                              '+${today.maxTemp.toStringAsFixed(0)}°C',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 52,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              today.condition,
                              style: const TextStyle(
                                color: Colors.white90,
                                fontSize: 18,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 24),
                            // Quick details row
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _buildDetailItem(
                                  icon: Icons.air_rounded,
                                  label: 'Shamol',
                                  value: '${today.windSpeed.toStringAsFixed(1)} m/s',
                                ),
                                _buildDetailItem(
                                  icon: Icons.umbrella_rounded,
                                  label: 'Yog\'ingarchilik',
                                  value: '${today.precipitation}%',
                                ),
                                _buildDetailItem(
                                  icon: Icons.water_drop_rounded,
                                  label: 'Namlik',
                                  value: '${today.humidity}%',
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Weekly title
                      const Text(
                        '1 Haftalik Ma\'lumotlar',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Remaining 6 days list
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: forecast.length,
                        itemBuilder: (context, index) {
                          final day = forecast[index];
                          final dayName = _getUzbekDayName(day.date, index);
                          final dateStr = '${day.date.day}-${_getUzbekMonth(day.date.month)}';

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.04),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: Colors.white.withOpacity(0.06)),
                            ),
                            child: Theme(
                              data: Theme.of(context).copyWith(
                                dividerColor: Colors.transparent,
                                unselectedWidgetColor: Colors.white38,
                              ),
                              child: ExpansionTile(
                                leading: Icon(
                                  day.icon,
                                  color: day.themeColor,
                                  size: 28,
                                ),
                                title: Text(
                                  dayName,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                subtitle: Text(
                                  dateStr,
                                  style: const TextStyle(
                                    color: Colors.white54,
                                    fontSize: 11,
                                  ),
                                ),
                                trailing: Text(
                                  '+${day.minTemp.toStringAsFixed(0)}° / +${day.maxTemp.toStringAsFixed(0)}°C',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                  ),
                                ),
                                children: [
                                  Padding(
                                    padding: const EdgeInsets.only(
                                      left: 16.0,
                                      right: 16.0,
                                      bottom: 16.0,
                                      top: 4.0,
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                                      children: [
                                        _buildMiniDetailItem(
                                          icon: Icons.air_rounded,
                                          label: 'Shamol',
                                          value: '${day.windSpeed.toStringAsFixed(1)} m/s',
                                        ),
                                        _buildMiniDetailItem(
                                          icon: Icons.umbrella_rounded,
                                          label: 'Yog\'in ehtimoli',
                                          value: '${day.precipitation}%',
                                        ),
                                        _buildMiniDetailItem(
                                          icon: Icons.water_drop_rounded,
                                          label: 'Namlik',
                                          value: '${day.humidity}%',
                                        ),
                                      ],
                                    ),
                                  )
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailItem({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.08),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: Colors.white, size: 24),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: const TextStyle(color: Colors.white38, fontSize: 10, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  Widget _buildMiniDetailItem({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Row(
      children: [
        Icon(icon, color: Colors.white70, size: 16),
        const SizedBox(width: 6),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: const TextStyle(color: Colors.white38, fontSize: 8),
            ),
            Text(
              value,
              style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ],
    );
  }
}
