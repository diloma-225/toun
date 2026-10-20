import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import '../widgets/toun_logo.dart';
import 'main_shell.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailControleur = TextEditingController();
  final _motPasseControleur = TextEditingController();

  bool _masquerMotPasse = true;
  bool _seSouvenir = true;
  bool _chargement = false;
  String? _erreur;

  @override
  void dispose() {
    _emailControleur.dispose();
    _motPasseControleur.dispose();
    super.dispose();
  }

  Future<void> _seConnecter() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _chargement = true;
      _erreur = null;
    });

    try {
      await AuthService.instance.connexion(_emailControleur.text.trim(), _motPasseControleur.text);

      if (!mounted) {
        return;
      }

      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const MainShell()),
        (route) => false,
      );
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } catch (_) {
      setState(() => _erreur = 'Impossible de contacter le serveur TOUN.');
    } finally {
      if (mounted) {
        setState(() => _chargement = false);
      }
    }
  }

  void _connexionIndisponible(String service) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('La connexion avec $service arrive bientôt.')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 26, vertical: 20),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 12),
                const TounLogo(hauteur: 38),
                const SizedBox(height: 28),
                const Text(
                  'Bienvenue sur TOUN',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800, color: TounColors.navy),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Connectez-vous à votre compte pour accéder\nà votre espace d\'apprentissage.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13, color: TounColors.textSecondary, height: 1.5),
                ),
                const SizedBox(height: 26),
                if (_erreur != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFDECEC),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(_erreur!, style: const TextStyle(color: Color(0xFFB3261E), fontSize: 13)),
                  ),
                  const SizedBox(height: 16),
                ],
                TextFormField(
                  controller: _emailControleur,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    hintText: 'Adresse e-mail',
                    prefixIcon: Icon(Icons.mail_outline_rounded),
                  ),
                  validator: (valeur) {
                    if (valeur == null || valeur.trim().isEmpty) {
                      return 'Veuillez saisir votre e-mail.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _motPasseControleur,
                  obscureText: _masquerMotPasse,
                  decoration: InputDecoration(
                    hintText: 'Mot de passe',
                    prefixIcon: const Icon(Icons.lock_outline_rounded),
                    suffixIcon: IconButton(
                      icon: Icon(_masquerMotPasse ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                      onPressed: () => setState(() => _masquerMotPasse = !_masquerMotPasse),
                    ),
                  ),
                  validator: (valeur) {
                    if (valeur == null || valeur.isEmpty) {
                      return 'Veuillez saisir votre mot de passe.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    SizedBox(
                      height: 24,
                      width: 24,
                      child: Checkbox(
                        value: _seSouvenir,
                        onChanged: (v) => setState(() => _seSouvenir = v ?? true),
                        activeColor: TounColors.teal,
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Text('Se souvenir de moi', style: TextStyle(fontSize: 12, color: TounColors.textSecondary)),
                    const Spacer(),
                    TextButton(
                      onPressed: () => _connexionIndisponible('la réinitialisation'),
                      child: const Text('Mot de passe oublié ?', style: TextStyle(fontSize: 12)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                TounButton(texte: 'Se connecter', onPressed: _seConnecter, chargement: _chargement),
                const SizedBox(height: 22),
                Row(
                  children: const [
                    Expanded(child: Divider(color: TounColors.border)),
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: 10),
                      child: Text('Ou se connecter avec', style: TextStyle(fontSize: 12, color: TounColors.textSecondary)),
                    ),
                    Expanded(child: Divider(color: TounColors.border)),
                  ],
                ),
                const SizedBox(height: 18),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _boutonSocial('assets/images/social_google.png', () => _connexionIndisponible('Google')),
                    const SizedBox(width: 14),
                    _boutonSocial('assets/images/social_facebook.png', () => _connexionIndisponible('Facebook')),
                    const SizedBox(width: 14),
                    _boutonSocial('assets/images/social_apple.png', () => _connexionIndisponible('Apple')),
                  ],
                ),
                const SizedBox(height: 26),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Vous n\'avez pas de compte ? ', style: TextStyle(fontSize: 13, color: TounColors.textSecondary)),
                    GestureDetector(
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const RegisterScreen()),
                        );
                      },
                      child: const Text(
                        'Inscription',
                        style: TextStyle(fontSize: 13, color: TounColors.teal, fontWeight: FontWeight.w700),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _boutonSocial(String logo, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          border: Border.all(color: TounColors.border),
          borderRadius: BorderRadius.circular(14),
        ),
        alignment: Alignment.center,
        child: Image.asset(logo, width: 24, height: 24),
      ),
    );
  }
}
