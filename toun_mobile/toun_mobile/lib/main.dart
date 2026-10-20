import 'package:flutter/material.dart';

import 'screens/main_shell.dart';
import 'screens/payment_confirmation_screen.dart';
import 'screens/splash_screen.dart';
import 'services/auth_service.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AuthService.instance.chargerSession();
  runApp(const TounApp());
}

class TounApp extends StatelessWidget {
  const TounApp({super.key});

  @override
  Widget build(BuildContext context) {
    final referencePaiement = Uri.base.queryParameters['paiement'];

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'TOUN',
      theme: AppTheme.light,
      home: !AuthService.instance.estConnecte
          ? const SplashScreen()
          : referencePaiement != null && referencePaiement.isNotEmpty
              ? PaymentConfirmationScreen(reference: referencePaiement)
              : const MainShell(),
    );
  }
}
