import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'login_screen.dart';
import 'register_screen.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  final List<OnboardingPageData> _pages = [
    OnboardingPageData(
      title: 'AgroMind-ga Xush Kelibsiz',
      description: 'Dehqon va fermerlar uchun zamonaviy raqamli portal. Hosildorlikni oshirish va texnikani aqlli boshqarish osonlashdi.',
      icon: Icons.agriculture_rounded,
      startColor: const Color(0xFF1A3C2A),
      endColor: const Color(0xFF2E7D32),
    ),
    OnboardingPageData(
      title: 'Tuproq Tahlili va AI',
      description: 'Tuproq tahlillarini kiritib boring va sun\'iy intellektdan ekinlaringiz uchun to\'g\'ri parvarish hamda o\'g\'itlash tavsiyalarini oling.',
      icon: Icons.analytics_rounded,
      startColor: const Color(0xFF1565C0),
      endColor: const Color(0xFF00ACC1),
    ),
    OnboardingPageData(
      title: 'GPS Harakat Kuzatuvi',
      description: 'Qishloq xo\'jaligi texnikalarining joriy koordinatalari va 24 soatlik harakat tarixini real vaqt rejimida GPS orqali kuzatib boring.',
      icon: Icons.map_rounded,
      startColor: const Color(0xFFE65C00),
      endColor: const Color(0xFFF57C00),
    ),
  ];

  Future<void> _completeOnboarding(BuildContext context, Widget screen) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('seen_onboarding', true);
    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => screen),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            // Page view containing slides
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                itemCount: _pages.length,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Premium visual illustration/icon container
                        Container(
                          height: 240,
                          width: 240,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(
                              colors: [page.startColor.withOpacity(0.15), page.endColor.withOpacity(0.05)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                          ),
                          child: Center(
                            child: Container(
                              height: 170,
                              width: 170,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: LinearGradient(
                                  colors: [page.startColor, page.endColor],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: page.startColor.withOpacity(0.4),
                                    blurRadius: 20,
                                    offset: const Offset(0, 10),
                                  )
                                ],
                              ),
                              child: Icon(
                                page.icon,
                                color: Colors.white,
                                size: 85,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 48),
                        // Slide Title
                        Text(
                          page.title,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF1E293B),
                            letterSpacing: 0.5,
                          ),
                        ),
                        const SizedBox(height: 16),
                        // Slide Description
                        Text(
                          page.description,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 15,
                            color: Colors.grey[600],
                            height: 1.6,
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),

            // Indicator dots
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                _pages.length,
                (index) => AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: const EdgeInsets.symmetric(horizontal: 4.0),
                  height: 6,
                  width: _currentPage == index ? 24 : 6,
                  decoration: BoxDecoration(
                    color: _currentPage == index
                        ? _pages[_currentPage].startColor
                        : Colors.grey[300],
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 40),

            // Action Buttons
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 32.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Kirish (Login) Button - Gradient (matches screenshot)
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      gradient: const LinearGradient(
                        colors: [Color(0xFF2979FF), Color(0xFF7C4DFF)], // Blue to Purple gradient
                        begin: Alignment.centerLeft,
                        end: Alignment.centerRight,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF7C4DFF).withOpacity(0.35),
                          blurRadius: 15,
                          offset: const Offset(0, 5),
                        )
                      ],
                    ),
                    child: ElevatedButton(
                      onPressed: () => _completeOnboarding(context, const LoginScreen()),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.transparent,
                        shadowColor: Colors.transparent,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text(
                        'KIRISH',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                          letterSpacing: 1.0,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Ro'yxatdan o'tish (Register) Button - Outlined
                  OutlinedButton(
                    onPressed: () => _completeOnboarding(context, const RegisterScreen()),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      side: BorderSide(color: Colors.grey[300]!, width: 1.5),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      foregroundColor: const Color(0xFF1E293B),
                    ),
                    child: const Text(
                      'RO\'YXATDAN O\'TISH',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Skip Intro Button
                  TextButton(
                    onPressed: () => _completeOnboarding(context, const LoginScreen()),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.grey[500],
                      padding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                    child: const Text(
                      'O\'TKAZIB YUBORISH',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class OnboardingPageData {
  final String title;
  final String description;
  final IconData icon;
  final Color startColor;
  final Color endColor;

  OnboardingPageData({
    required this.title,
    required this.description,
    required this.icon,
    required this.startColor,
    required this.endColor,
  });
}
