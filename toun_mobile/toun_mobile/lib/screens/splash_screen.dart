import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import '../widgets/toun_logo.dart';
import 'onboarding_screen.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            children: [
              const Spacer(flex: 2),

              const TounLogo(hauteur: 40),

              const Spacer(flex: 1),

              Expanded(
                flex: 7,
                child: Image.asset(
                  'assets/images/toun_bienvenue.png',
                  fit: BoxFit.contain,
                ),
              ),

              const SizedBox(height: 26),

              const Text(
                'La formation professionnelle\naccessible partout.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: TounColors.navy,
                  fontSize: 19,
                  fontWeight: FontWeight.w800,
                  height: 1.35,
                ),
              ),

              const Spacer(flex: 3),

              TounButton(
                texte: 'Commencer  →',
                onPressed: () {
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(
                      builder: (_) => const OnboardingScreen(),
                    ),
                  );
                },
              ),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}
