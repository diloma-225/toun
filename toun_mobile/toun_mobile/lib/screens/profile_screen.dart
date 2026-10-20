import 'package:flutter/material.dart';

import '../models/profil.dart';
import '../services/auth_service.dart';
import '../services/profil_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_app_drawer.dart';
import '../widgets/toun_en_tete.dart';
import 'splash_screen.dart';

class ProfileScreen extends StatefulWidget {
  final VoidCallback onVoirFormations;
  final VoidCallback onVoirMesFormations;
  final VoidCallback onVoirCertificats;

  const ProfileScreen({
    super.key,
    required this.onVoirFormations,
    required this.onVoirMesFormations,
    required this.onVoirCertificats,
  });

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  late Future<ProfilDonnees> _futur;

  @override
  void initState() {
    super.initState();
    _futur = ProfilService.instance.charger();
  }

  Future<void> _seDeconnecter() async {
    await AuthService.instance.deconnexion();

    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const SplashScreen()),
      (route) => false,
    );
  }

  String _formaterDate(String? brute) {
    if (brute == null || brute.isEmpty) return '';

    final date = DateTime.tryParse(brute);
    if (date == null) return '';

    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: TounColors.background,
      drawer: TounAppDrawer(
        actif: 'profil',
        onFormations: widget.onVoirFormations,
        onMesFormations: widget.onVoirMesFormations,
        onCertificats: widget.onVoirCertificats,
        onProfil: () => Navigator.of(context).pop(),
      ),
      body: SafeArea(
        top: false,
        child: FutureBuilder<ProfilDonnees>(
          future: _futur,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text(
                    'Impossible de charger le profil.\n${snapshot.error}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: TounColors.textSecondary),
                  ),
                ),
              );
            }

            final profil = snapshot.data!;

            return ListView(
              padding: const EdgeInsets.only(bottom: 24),
              children: [
                TounEnTete(
                  titre: 'Mon profil',
                  onMenu: () => _scaffoldKey.currentState?.openDrawer(),
                  espaceBas: 50,
                ),
                Transform.translate(
                  offset: const Offset(0, -50),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _carteEntete(profil),
                        const SizedBox(height: 20),
                        _sectionInformations(profil),
                        const SizedBox(height: 24),
                        OutlinedButton.icon(
                  onPressed: _seDeconnecter,
                  icon: const Icon(Icons.logout_rounded, color: Color(0xFFE0523F)),
                  label: const Text('Se déconnecter'),
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(50),
                    foregroundColor: const Color(0xFFE0523F),
                    side: const BorderSide(color: Color(0xFFE0523F)),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _carteEntete(ProfilDonnees profil) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 14, offset: const Offset(0, 6))],
      ),
      child: Column(
        children: [
          CircleAvatar(
            radius: 40,
            backgroundColor: TounColors.teal.withValues(alpha: 0.12),
            backgroundImage: profil.photo.isNotEmpty ? NetworkImage(profil.photo) : null,
            child: profil.photo.isEmpty
                ? Text(profil.initiales, style: const TextStyle(color: TounColors.teal, fontWeight: FontWeight.w800, fontSize: 22))
                : null,
          ),
          const SizedBox(height: 14),
          Text(profil.nomComplet, style: const TextStyle(fontSize: 16.5, fontWeight: FontWeight.w800, color: TounColors.navy)),
          const SizedBox(height: 4),
          Text(profil.email, style: const TextStyle(fontSize: 12.5, color: TounColors.textSecondary)),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(color: TounColors.teal.withValues(alpha: 0.10), borderRadius: BorderRadius.circular(20)),
            child: const Text('Apprenant TOUN', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: TounColors.teal)),
          ),
        ],
      ),
    );
  }

  Widget _sectionInformations(ProfilDonnees profil) {
    final lignes = [
      (icone: Icons.mail_outline_rounded, label: 'Adresse e-mail', valeur: profil.email),
      if (profil.telephone.isNotEmpty) (icone: Icons.phone_outlined, label: 'Téléphone', valeur: profil.telephone),
      if (_formaterDate(profil.dateInscription).isNotEmpty)
        (icone: Icons.calendar_today_outlined, label: 'Membre depuis le', valeur: _formaterDate(profil.dateInscription)),
    ];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 14, offset: const Offset(0, 6))],
      ),
      child: Column(
        children: List.generate(lignes.length, (index) {
          final ligne = lignes[index];

          return Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              border: index == lignes.length - 1 ? null : const Border(bottom: BorderSide(color: TounColors.background, width: 1.4)),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(color: TounColors.blue.withValues(alpha: 0.10), borderRadius: BorderRadius.circular(10)),
                  child: Icon(ligne.icone, size: 18, color: TounColors.blue),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(ligne.label, style: const TextStyle(fontSize: 10.5, color: TounColors.textSecondary)),
                      const SizedBox(height: 2),
                      Text(ligne.valeur, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: TounColors.navy)),
                    ],
                  ),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }
}
