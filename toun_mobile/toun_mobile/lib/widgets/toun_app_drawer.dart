import 'package:flutter/material.dart';

import '../models/dashboard.dart';
import '../screens/evaluations_screen.dart';
import '../screens/lives_screen.dart';
import '../screens/my_payments_screen.dart';
import '../screens/notifications_screen.dart';
import '../screens/rendez_vous_screen.dart';
import '../screens/ressources_screen.dart';
import '../screens/splash_screen.dart';
import '../screens/support_chat_screen.dart';
import '../services/auth_service.dart';
import 'toun_ecran_a_venir.dart';
import 'toun_logo.dart';

class TounAppDrawer extends StatelessWidget {
  final Future<TableauDeBord>? futurDonnees;
  final VoidCallback onFormations;
  final VoidCallback onMesFormations;
  final VoidCallback onCertificats;
  final VoidCallback onProfil;
  final VoidCallback? onTableauDeBord;
  final String actif;

  const TounAppDrawer({
    super.key,
    this.futurDonnees,
    required this.onFormations,
    required this.onMesFormations,
    required this.onCertificats,
    required this.onProfil,
    this.onTableauDeBord,
    this.actif = 'dashboard',
  });

  static const LinearGradient _degrade = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF1FA6B8), Color(0xFF2A86B5), Color(0xFF3F68AE)],
  );

  void _fermerPuis(BuildContext context, VoidCallback action) {
    Navigator.of(context).pop();
    action();
  }

  void _ouvrir(BuildContext context, Widget ecran) {
    Navigator.of(context).pop();
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => ecran));
  }

  void _ouvrirAVenir(BuildContext context, String titre, IconData icone) {
    _ouvrir(context, EcranAVenir(titre: titre, icone: icone));
  }

  Future<void> _seDeconnecter(BuildContext context) async {
    Navigator.of(context).pop();
    await AuthService.instance.deconnexion();

    if (!context.mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const SplashScreen()),
      (route) => false,
    );
  }

  Widget _titreSection(String titre) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 18, 20, 6),
      child: Text(
        titre.toUpperCase(),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          letterSpacing: 1.2,
          color: Colors.white.withValues(alpha: 0.72),
        ),
      ),
    );
  }

  Widget _item({
    required String cle,
    required IconData icone,
    required String label,
    required VoidCallback onTap,
  }) {
    final estActif = cle == actif;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(12),
          splashColor: Colors.white.withValues(alpha: 0.18),
          highlightColor: Colors.white.withValues(alpha: 0.10),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 220),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            decoration: BoxDecoration(
              color: estActif ? Colors.white.withValues(alpha: 0.20) : Colors.transparent,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: estActif ? Colors.white.withValues(alpha: 0.35) : Colors.transparent,
              ),
              boxShadow: estActif
                  ? [BoxShadow(color: Colors.black.withValues(alpha: 0.10), blurRadius: 12, offset: const Offset(0, 5))]
                  : null,
            ),
            child: Row(
              children: [
                Icon(icone, size: 19, color: Colors.white),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: estActif ? FontWeight.w700 : FontWeight.w500,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _profil() {
    return FutureBuilder<TableauDeBord>(
      future: futurDonnees,
      builder: (context, snapshot) {
        final donnees = snapshot.data;
        final prenom = donnees?.prenom ?? '';
        final nom = donnees?.nom ?? '';
        final photo = donnees?.photo ?? '';
        final nomComplet = '$prenom $nom'.trim();
        final initiales = '${prenom.isNotEmpty ? prenom[0] : ''}${nom.isNotEmpty ? nom[0] : ''}'.toUpperCase();

        return Container(
          margin: const EdgeInsets.fromLTRB(16, 18, 16, 4),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
          ),
          child: Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: Colors.white.withValues(alpha: 0.25),
                backgroundImage: photo.isNotEmpty ? NetworkImage(photo) : null,
                child: photo.isEmpty
                    ? Text(
                        initiales.isEmpty ? 'TO' : initiales,
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                      )
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nomComplet.isEmpty ? 'Apprenant TOUN' : nomComplet,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: Colors.white),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Apprenant',
                      style: TextStyle(fontSize: 11, color: Colors.white.withValues(alpha: 0.75)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      width: 290,
      backgroundColor: Colors.transparent,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.horizontal(right: Radius.circular(24)),
      ),
      child: Container(
        decoration: const BoxDecoration(
          gradient: _degrade,
          borderRadius: BorderRadius.horizontal(right: Radius.circular(24)),
        ),
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(24, 18, 24, 0),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: TounLogo(hauteur: 34, blanc: true),
                ),
              ),
              _profil(),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.only(bottom: 8),
                  children: [
                    _titreSection('Principal'),
                    _item(
                      cle: 'dashboard',
                      icone: Icons.grid_view_rounded,
                      label: 'Tableau de bord',
                      onTap: () => onTableauDeBord != null
                          ? _fermerPuis(context, onTableauDeBord!)
                          : Navigator.of(context).pop(),
                    ),
                    _item(
                      cle: 'mes_formations',
                      icone: Icons.menu_book_rounded,
                      label: 'Mes formations',
                      onTap: () => _fermerPuis(context, onMesFormations),
                    ),
                    _item(
                      cle: 'lives',
                      icone: Icons.videocam_rounded,
                      label: 'Mes Lives',
                      onTap: () => _ouvrir(context, const LivesScreen()),
                    ),
                    _item(
                      cle: 'catalogue',
                      icone: Icons.auto_stories_rounded,
                      label: 'Catalogue',
                      onTap: () => _fermerPuis(context, onFormations),
                    ),
                    _titreSection('Apprentissage'),
                    _item(
                      cle: 'progression',
                      icone: Icons.show_chart_rounded,
                      label: 'Ma progression',
                      onTap: () => _fermerPuis(context, onMesFormations),
                    ),
                    _item(
                      cle: 'evaluations',
                      icone: Icons.fact_check_rounded,
                      label: 'Mes évaluations',
                      onTap: () => _ouvrir(context, const EvaluationsScreen()),
                    ),
                    _item(
                      cle: 'certificats',
                      icone: Icons.workspace_premium_rounded,
                      label: 'Mes certificats',
                      onTap: () => _fermerPuis(context, onCertificats),
                    ),
                    _item(
                      cle: 'rendez_vous',
                      icone: Icons.calendar_month_rounded,
                      label: 'Mes rendez-vous',
                      onTap: () => _ouvrir(context, const RendezVousScreen()),
                    ),
                    _titreSection('Services'),
                    _item(
                      cle: 'paiements',
                      icone: Icons.receipt_long_rounded,
                      label: 'Mes paiements',
                      onTap: () => _ouvrir(context, const MyPaymentsScreen()),
                    ),
                    _item(
                      cle: 'ressources',
                      icone: Icons.folder_open_rounded,
                      label: 'Ressources',
                      onTap: () => _ouvrir(context, const RessourcesScreen()),
                    ),
                    _item(
                      cle: 'notifications',
                      icone: Icons.notifications_none_rounded,
                      label: 'Notifications',
                      onTap: () => _ouvrir(context, const NotificationsScreen()),
                    ),
                    _item(
                      cle: 'messages',
                      icone: Icons.chat_bubble_outline_rounded,
                      label: 'Messages',
                      onTap: () => _ouvrir(context, const SupportChatScreen()),
                    ),
                    _titreSection('Mon compte'),
                    _item(
                      cle: 'profil',
                      icone: Icons.person_outline_rounded,
                      label: 'Mon profil',
                      onTap: () => _fermerPuis(context, onProfil),
                    ),
                    _item(
                      cle: 'parametres',
                      icone: Icons.settings_outlined,
                      label: 'Paramètres',
                      onTap: () => _ouvrirAVenir(context, 'Paramètres', Icons.settings_outlined),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 14),
                child: Material(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                  child: InkWell(
                    onTap: () => _seDeconnecter(context),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.28)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.logout_rounded, size: 19, color: Colors.white),
                          SizedBox(width: 14),
                          Text(
                            'Déconnexion',
                            style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: Colors.white),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
