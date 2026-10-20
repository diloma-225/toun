import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import 'login_screen.dart';

class _PageOnboarding {
  final String image;
  final String titre;
  final String description;
  final String texteBouton;

  const _PageOnboarding({
    required this.image,
    required this.titre,
    required this.description,
    required this.texteBouton,
  });
}

const List<_PageOnboarding> _pages = [
  _PageOnboarding(
    image: 'assets/images/onboarding_1.png',
    titre: 'Développez\nvos compétences',
    description: 'Des formations adaptées à vos objectifs professionnels.',
    texteBouton: 'Suivant  →',
  ),
  _PageOnboarding(
    image: 'assets/images/onboarding_2.png',
    titre: 'Apprenez\nà votre rythme',
    description: 'Accédez à vos formations où que vous soyez.',
    texteBouton: 'Commencer  →',
  ),
];

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _controleur = PageController();
  int _pageActuelle = 0;

  void _ouvrirConnexion() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  void _suivant() {
    if (_pageActuelle == _pages.length - 1) {
      _ouvrirConnexion();
      return;
    }

    _controleur.nextPage(duration: const Duration(milliseconds: 320), curve: Curves.easeOut);
  }

  @override
  void dispose() {
    _controleur.dispose();
    super.dispose();
  }

  Widget _pointsPagination(int index) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(_pages.length, (i) {
        final actif = i == index;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          margin: const EdgeInsets.symmetric(horizontal: 4),
          width: actif ? 22 : 8,
          height: 8,
          decoration: BoxDecoration(
            color: actif ? TounColors.teal : TounColors.border,
            borderRadius: BorderRadius.circular(4),
          ),
        );
      }),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 20, 0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: _ouvrirConnexion,
                    child: const Text(
                      'Passer',
                      style: TextStyle(color: TounColors.textSecondary),
                    ),
                  ),
                ],
              ),
            ),

            Expanded(
              child: PageView.builder(
                controller: _controleur,
                itemCount: _pages.length,
                onPageChanged: (i) => setState(() => _pageActuelle = i),
                itemBuilder: (context, index) {
                  final page = _pages[index];

                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Column(
                      children: [
                        Expanded(
                          flex: 6,
                          child: Image.asset(page.image, fit: BoxFit.contain),
                        ),

                        const SizedBox(height: 22),

                        Text(
                          page.titre,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            color: TounColors.navy,
                            height: 1.3,
                          ),
                        ),

                        const SizedBox(height: 12),

                        Text(
                          page.description,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 14,
                            color: TounColors.textSecondary,
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),

            _pointsPagination(_pageActuelle),

            Padding(
              padding: const EdgeInsets.all(24),
              child: TounButton(
                texte: _pages[_pageActuelle].texteBouton,
                onPressed: _suivant,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
