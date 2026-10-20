import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import '../widgets/toun_logo.dart';
import 'main_shell.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nomControleur = TextEditingController();
  final _prenomControleur = TextEditingController();
  final _emailControleur = TextEditingController();
  final _telControleur = TextEditingController();
  final _motPasseControleur = TextEditingController();
  final _confirmationControleur = TextEditingController();

  bool _masquerMotPasse = true;
  bool _masquerConfirmation = true;
  bool _chargement = false;
  String? _erreur;

  @override
  void dispose() {
    _nomControleur.dispose();
    _prenomControleur.dispose();
    _emailControleur.dispose();
    _telControleur.dispose();
    _motPasseControleur.dispose();
    _confirmationControleur.dispose();
    super.dispose();
  }

  Future<void> _creerCompte() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _chargement = true;
      _erreur = null;
    });

    try {
      await AuthService.instance.inscription(
        nom: _nomControleur.text.trim(),
        prenom: _prenomControleur.text.trim(),
        email: _emailControleur.text.trim(),
        telephone: _telControleur.text.trim(),
        motDePasse: _motPasseControleur.text,
        confirmation: _confirmationControleur.text,
      );

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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        foregroundColor: TounColors.navy,
      ),
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 26),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const TounLogo(hauteur: 34),
                const SizedBox(height: 22),
                const Text(
                  'Créer votre compte',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800, color: TounColors.navy),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Rejoignez TOUN et accédez à des\nformations de qualité.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13, color: TounColors.textSecondary, height: 1.5),
                ),
                const SizedBox(height: 24),
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
                  controller: _nomControleur,
                  decoration: const InputDecoration(hintText: 'Nom', prefixIcon: Icon(Icons.person_outline_rounded)),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Champ obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _prenomControleur,
                  decoration: const InputDecoration(hintText: 'Prénom', prefixIcon: Icon(Icons.person_outline_rounded)),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Champ obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _emailControleur,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(hintText: 'Adresse e-mail', prefixIcon: Icon(Icons.mail_outline_rounded)),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Champ obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _telControleur,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(hintText: 'Téléphone', prefixIcon: Icon(Icons.phone_outlined)),
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
                  validator: (v) {
                    if (v == null || v.length < 8) {
                      return 'Au moins 8 caractères.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _confirmationControleur,
                  obscureText: _masquerConfirmation,
                  decoration: InputDecoration(
                    hintText: 'Confirmer le mot de passe',
                    prefixIcon: const Icon(Icons.lock_outline_rounded),
                    suffixIcon: IconButton(
                      icon: Icon(_masquerConfirmation ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                      onPressed: () => setState(() => _masquerConfirmation = !_masquerConfirmation),
                    ),
                  ),
                  validator: (v) {
                    if (v != _motPasseControleur.text) {
                      return 'Les mots de passe ne correspondent pas.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 22),
                TounButton(texte: 'Créer un compte →', onPressed: _creerCompte, chargement: _chargement),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Vous avez déjà un compte ? ', style: TextStyle(fontSize: 13, color: TounColors.textSecondary)),
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: const Text(
                        'Se connecter',
                        style: TextStyle(fontSize: 13, color: TounColors.teal, fontWeight: FontWeight.w700),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
