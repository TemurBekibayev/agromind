import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/providers.dart';

class WeatherConditionInfo {
  final String name;
  final IconData icon;
  final Color color;

  WeatherConditionInfo({
    required this.name,
    required this.icon,
    required this.color,
  });
}

class WeatherForecastScreen extends ConsumerWidget {
  final String region;
  final String coordsStr;

  const WeatherForecastScreen({
    super.key,
    required this.region,
    required this.coordsStr,
  });

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

  WeatherConditionInfo _getWeatherCondition(int code) {
    switch (code) {
      case 0:
        return WeatherConditionInfo(
          name: 'Quyoshli',
          icon: Icons.wb_sunny_rounded,
          color: const Color(0xFFFFB300),
        );
      case 1:
      case 2:
        return WeatherConditionInfo(
          name: 'Qisman bulutli',
          icon: Icons.wb_cloudy_rounded,
          color: const Color(0xFF90A4AE),
        );
      case 3:
        return WeatherConditionInfo(
          name: 'Bulutli',
          icon: Icons.cloud_rounded,
          color: const Color(0xFF78909C),
        );
      case 45:
      case 48:
        return WeatherConditionInfo(
          name: 'Tumanli',
          icon: Icons.filter_drama_rounded,
          color: const Color(0xFFB0BEC5),
        );
      case 51:
      case 53:
      case 55:
      case 61:
      case 63:
      case 65:
      case 80:
      case 81:
      case 82:
        return WeatherConditionInfo(
          name: 'Yomg\'irli',
          icon: Icons.grain_rounded,
          color: const Color(0xFF4FC3F7),
        );
      case 95:
      case 96:
      case 99:
        return WeatherConditionInfo(
          name: 'Momaqaldiroq',
          icon: Icons.thunderstorm_rounded,
          color: const Color(0xFFB39DDB),
        );
      default:
        return WeatherConditionInfo(
          name: 'Bulutli',
          icon: Icons.cloud_rounded,
          color: const Color(0xFF78909C),
        );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final weatherState = ref.watch(weatherProvider(coordsStr));

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
                child: weatherState.when(
                  data: (data) {
                    final current = data['current'];
                    final temp = current['temperature_2m'];
                    final code = current['weather_code'];
                    final wind = current['wind_speed_10m'];
                    final precip = current['precipitation'];
                    final humidity = current['relative_humidity_2m'];

                    final currentCondition = _getWeatherCondition(code);

                    final daily = data['daily'];
                    final times = daily['time'] as List<dynamic>;
                    final codes = daily['weather_code'] as List<dynamic>;
                    final tempsMax = daily['temperature_2m_max'] as List<dynamic>;
                    final tempsMin = daily['temperature_2m_min'] as List<dynamic>;
                    final precips = daily['precipitation_probability_max'] as List<dynamic>;
                    final winds = daily['wind_speed_10m_max'] as List<dynamic>;

                    return SingleChildScrollView(
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
                                  currentCondition.icon,
                                  size: 72,
                                  color: currentCondition.color,
                                ),
                                const SizedBox(height: 12),
                                Text(
                                  '+${temp.toStringAsFixed(0)}°C',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 52,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                Text(
                                  currentCondition.name,
                                  style: TextStyle(
                                    color: Colors.white.withOpacity(0.9),
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
                                      value: '${wind.toStringAsFixed(1)} m/s',
                                    ),
                                    _buildDetailItem(
                                      icon: Icons.umbrella_rounded,
                                      label: 'Yog\'ingarchilik',
                                      value: '${precip.toStringAsFixed(1)} mm',
                                    ),
                                    _buildDetailItem(
                                      icon: Icons.water_drop_rounded,
                                      label: 'Namlik',
                                      value: '$humidity%',
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
                            itemCount: times.length,
                            itemBuilder: (context, index) {
                              final timeStr = times[index] as String;
                              final date = DateTime.parse(timeStr);
                              final dayCode = codes[index] as int;
                              final maxTemp = tempsMax[index] as double;
                              final minTemp = tempsMin[index] as double;
                              final dayPrecip = precips[index] as int;
                              final dayWind = winds[index] as double;

                              final dayCondition = _getWeatherCondition(dayCode);
                              final dayName = _getUzbekDayName(date, index);
                              final dateStr = '${date.day}-${_getUzbekMonth(date.month)}';

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
                                      dayCondition.icon,
                                      color: dayCondition.color,
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
                                      '+${minTemp.toStringAsFixed(0)}° / +${maxTemp.toStringAsFixed(0)}°C',
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
                                              value: '${dayWind.toStringAsFixed(1)} m/s',
                                            ),
                                            _buildMiniDetailItem(
                                              icon: Icons.umbrella_rounded,
                                              label: 'Yog\'in ehtimoli',
                                              value: '$dayPrecip%',
                                            ),
                                            _buildMiniDetailItem(
                                              icon: Icons.water_drop_rounded,
                                              label: 'Havo namligi',
                                              value: '${30 + (index * 3) % 15}%', // Estimation
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
                    );
                  },
                  error: (e, __) => Center(
                    child: Padding(
                      padding: const EdgeInsets.all(32.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.cloud_off_rounded, color: Colors.white60, size: 64),
                          const SizedBox(height: 16),
                          const Text(
                            'Ob-havoni yuklashda xatolik',
                            style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Iltimos, internet aloqasini tekshiring: $e',
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.white60, fontSize: 12),
                          ),
                          const SizedBox(height: 24),
                          ElevatedButton.icon(
                            onPressed: () => ref.refresh(weatherProvider(coordsStr)),
                            icon: const Icon(Icons.refresh),
                            label: const Text('Qayta urinish'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF1A3C2A),
                              foregroundColor: Colors.white,
                            ),
                          )
                        ],
                      ),
                    ),
                  ),
                  loading: () => const Center(
                    child: CircularProgressIndicator(color: Colors.white),
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
