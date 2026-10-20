import 'package:flutter/material.dart';

import '../models/dashboard.dart';
import '../services/dashboard_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_app_drawer.dart';
import '../widgets/toun_logo.dart';
import 'formation_detail_screen.dart';
import 'notifications_screen.dart';
import 'rendez_vous_screen.dart';
import 'support_chat_screen.dart';

class DashboardScreen extends StatefulWidget {
  final VoidCallback onVoirFormations;
  final VoidCallback onVoirMesFormations;
  final VoidCallback onVoirCertificats;
  final VoidCallback onVoirProfil;

  const DashboardScreen({
    super.key,
    required this.onVoirFormations,
    required this.onVoirMesFormations,
    required this.onVoirCertificats,
    required this.onVoirProfil,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  static const Color _orange = Color(0xFF20A1B8);

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  late Future<TableauDeBord> _futur;

  @override
  void initState() {
    super.initState();
    _futur = DashboardService.instance.charger();
  }

  Future<void> _rafraichir() async {
    final futur = DashboardService.instance.charger();
    setState(() {
      _futur = futur;
    });
    await futur;
  }

  void _ouvrir(Widget ecran) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => ecran));
  }

  BoxDecoration get _carteBlanche {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: TounColors.border),
      boxShadow: [
        BoxShadow(color: TounColors.navy.withValues(alpha: 0.05), blurRadius: 16, offset: const Offset(0, 8)),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: TounColors.background,
      drawer: TounAppDrawer(
        futurDonnees: _futur,
        actif: 'dashboard',
        onFormations: widget.onVoirFormations,
        onMesFormations: widget.onVoirMesFormations,
        onCertificats: widget.onVoirCertificats,
        onProfil: widget.onVoirProfil,
      ),
      floatingActionButton: Container(
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: TounColors.buttonGradient,
          boxShadow: [
            BoxShadow(color: TounColors.blue.withValues(alpha: 0.35), blurRadius: 16, offset: const Offset(0, 8)),
          ],
        ),
        child: FloatingActionButton(
          onPressed: () => _ouvrir(const SupportChatScreen()),
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: const Icon(Icons.chat_bubble_rounded, color: Colors.white),
        ),
      ),
      body: SafeArea(
        child: FutureBuilder<TableauDeBord>(
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
                    'Impossible de charger le tableau de bord.\n${snapshot.error}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: TounColors.textSecondary),
                  ),
                ),
              );
            }

            final donnees = snapshot.data!;

            return Column(
              children: [
                _barreSuperieure(donnees),
                Expanded(
                  child: RefreshIndicator(
                    onRefresh: _rafraichir,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(18, 18, 18, 90),
                      children: [
                        _bienvenue(donnees),
                        const SizedBox(height: 16),
                        _statistiques(donnees),
                        const SizedBox(height: 16),
                        _continuerFormation(donnees),
                        const SizedBox(height: 16),
                        _accesRapides(),
                        const SizedBox(height: 16),
                        _mesFormations(donnees),
                        const SizedBox(height: 16),
                        _monApprentissage(donnees),
                        const SizedBox(height: 16),
                        _besoinAide(),
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

  Widget _barreSuperieure(TableauDeBord donnees) {
    final initiales =
        '${donnees.prenom.isNotEmpty ? donnees.prenom[0] : ''}${donnees.nom.isNotEmpty ? donnees.nom[0] : ''}'.toUpperCase();

    return Container(
      padding: const EdgeInsets.fromLTRB(8, 8, 16, 8),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: TounColors.border)),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: () => _scaffoldKey.currentState?.openDrawer(),
            icon: const Icon(Icons.menu_rounded, color: TounColors.navy),
          ),
          const TounLogo(hauteur: 24),
          const Spacer(),
          _boutonBarre(
            icone: Icons.event_available_rounded,
            onTap: () => _ouvrir(const RendezVousScreen()),
          ),
          const SizedBox(width: 8),
          Stack(
            clipBehavior: Clip.none,
            children: [
              _boutonBarre(
                icone: Icons.notifications_none_rounded,
                onTap: () => _ouvrir(const NotificationsScreen()),
              ),
              Positioned(
                top: 6,
                right: 7,
                child: Container(
                  width: 7,
                  height: 7,
                  decoration: const BoxDecoration(color: TounColors.teal, shape: BoxShape.circle),
                ),
              ),
            ],
          ),
          const SizedBox(width: 10),
          GestureDetector(
            onTap: widget.onVoirProfil,
            child: Container(
              width: 38,
              height: 38,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [TounColors.navy, TounColors.blue],
                ),
              ),
              clipBehavior: Clip.antiAlias,
              alignment: Alignment.center,
              child: donnees.photo.isNotEmpty
                  ? Image.network(donnees.photo, width: 38, height: 38, fit: BoxFit.cover)
                  : Text(
                      initiales.isEmpty ? 'TO' : initiales,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12.5),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _boutonBarre({required IconData icone, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          color: TounColors.teal.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: TounColors.teal.withValues(alpha: 0.18)),
        ),
        child: Icon(icone, size: 19, color: TounColors.blue),
      ),
    );
  }

  Widget _bienvenue(TableauDeBord donnees) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Bonjour ${donnees.prenom}',
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: TounColors.navy),
              ),
              const SizedBox(height: 3),
              const Text(
                'Votre espace d\'apprentissage TOUN.',
                style: TextStyle(fontSize: 12, color: TounColors.textSecondary),
              ),
            ],
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: TounColors.border),
          ),
          child: const Text(
            'Compte actif',
            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: TounColors.blue),
          ),
        ),
      ],
    );
  }

  Widget _statistiques(TableauDeBord donnees) {
    double pourcent(num valeur) => (valeur.clamp(0, 100)) / 100;

    final tuiles = [
      (
        icone: Icons.menu_book_rounded,
        couleur: TounColors.teal,
        label: 'Mes formations',
        valeur: '${donnees.totalFormations}',
        anneau: pourcent(donnees.totalFormations * 20),
      ),
      (
        icone: Icons.play_circle_fill_rounded,
        couleur: TounColors.blue,
        label: 'Cours disponibles',
        valeur: '${donnees.totalCours}',
        anneau: pourcent(donnees.totalCours * 10),
      ),
      (
        icone: Icons.show_chart_rounded,
        couleur: TounColors.green,
        label: 'Progression globale',
        valeur: '${donnees.progressionGlobale} %',
        anneau: pourcent(donnees.progressionGlobale),
      ),
      (
        icone: Icons.workspace_premium_rounded,
        couleur: TounColors.purple,
        label: 'Certificats',
        valeur: '${donnees.totalCertificats}',
        anneau: pourcent(donnees.totalCertificats * 25),
      ),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.45,
      children: tuiles.map((tuile) {
        return Container(
          clipBehavior: Clip.antiAlias,
          decoration: _carteBlanche,
          child: Stack(
            children: [
              Positioned(
                right: -26,
                bottom: -34,
                child: Container(
                  width: 96,
                  height: 96,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: tuile.couleur.withValues(alpha: 0.08),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(13),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(color: tuile.couleur, shape: BoxShape.circle),
                          child: Icon(tuile.icone, size: 16, color: Colors.white),
                        ),
                        const Spacer(),
                        SizedBox(
                          width: 26,
                          height: 26,
                          child: CircularProgressIndicator(
                            value: tuile.anneau,
                            strokeWidth: 3,
                            backgroundColor: tuile.couleur.withValues(alpha: 0.12),
                            valueColor: AlwaysStoppedAnimation(tuile.couleur),
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),
                    Text(
                      tuile.label.toUpperCase(),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 9.5,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.4,
                        color: TounColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      tuile.valeur,
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: tuile.couleur),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _enTeteCarte(String titre, String lien, {VoidCallback? onTap, bool clair = false}) {
    return Row(
      children: [
        Expanded(
          child: Text(
            titre,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: clair ? Colors.white : TounColors.navy,
            ),
          ),
        ),
        GestureDetector(
          onTap: onTap,
          child: Text(
            lien,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: clair ? Colors.white.withValues(alpha: 0.8) : TounColors.textSecondary,
            ),
          ),
        ),
      ],
    );
  }

  Widget _boutonDegrade(String texte, VoidCallback onTap) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: TounColors.buttonGradient,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(color: TounColors.blue.withValues(alpha: 0.25), blurRadius: 12, offset: const Offset(0, 6)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(10),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 11),
            child: Text(
              texte,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white),
            ),
          ),
        ),
      ),
    );
  }

  Widget _etatVide({
    required IconData icone,
    required String titre,
    required String texte,
    required Widget bouton,
    bool clair = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 18),
      child: Column(
        children: [
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: clair ? Colors.white.withValues(alpha: 0.12) : TounColors.teal.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(14),
              border: clair ? Border.all(color: Colors.white.withValues(alpha: 0.2)) : null,
            ),
            child: Icon(icone, color: clair ? Colors.white : TounColors.teal),
          ),
          const SizedBox(height: 12),
          Text(
            titre,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: clair ? Colors.white : TounColors.navy,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            texte,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 11.5,
              height: 1.5,
              color: clair ? Colors.white.withValues(alpha: 0.78) : TounColors.textSecondary,
            ),
          ),
          const SizedBox(height: 14),
          bouton,
        ],
      ),
    );
  }

  Widget _continuerFormation(TableauDeBord donnees) {
    final formation = donnees.formationEnCours;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _carteBlanche,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _enTeteCarte('Continuer ma formation', 'Voir tout', onTap: widget.onVoirMesFormations),
          const SizedBox(height: 12),
          if (formation == null)
            _etatVide(
              icone: Icons.school_rounded,
              titre: 'Votre parcours commence ici',
              texte: 'Vous n\'avez encore aucune formation. Découvrez le catalogue et choisissez la formation qui correspond à vos objectifs.',
              bouton: _boutonDegrade('Découvrir les formations', widget.onVoirFormations),
            )
          else
            InkWell(
              onTap: () => _ouvrir(FormationDetailScreen(idForm: formation.idForm)),
              borderRadius: BorderRadius.circular(14),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: TounColors.background,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: TounColors.border),
                ),
                child: Row(
                  children: [
                    _miniature(formation.image, 58),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            formation.titre,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: TounColors.navy),
                          ),
                          const SizedBox(height: 8),
                          _barreProgression(formation.progression, TounColors.teal, TounColors.border),
                          const SizedBox(height: 4),
                          Text(
                            '${formation.progression} % terminé',
                            style: const TextStyle(fontSize: 10.5, color: TounColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Container(
                      width: 34,
                      height: 34,
                      decoration: const BoxDecoration(gradient: TounColors.buttonGradient, shape: BoxShape.circle),
                      child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 20),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _accesRapides() {
    final liens = [
      (
        icone: Icons.explore_rounded,
        couleur: TounColors.blue,
        titre: 'Explorer les formations',
        texte: 'Trouvez votre prochain parcours',
        onTap: widget.onVoirFormations,
      ),
      (
        icone: Icons.play_circle_outline_rounded,
        couleur: TounColors.teal,
        titre: 'Reprendre mon apprentissage',
        texte: 'Retrouvez vos formations',
        onTap: widget.onVoirMesFormations,
      ),
      (
        icone: Icons.person_outline_rounded,
        couleur: TounColors.purple,
        titre: 'Mon profil',
        texte: 'Gérez vos informations',
        onTap: widget.onVoirProfil,
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _carteBlanche,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _enTeteCarte('Accès rapides', 'TOUN'),
          const SizedBox(height: 12),
          for (final lien in liens) ...[
            InkWell(
              onTap: lien.onTap,
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.all(11),
                decoration: BoxDecoration(
                  color: TounColors.background,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: TounColors.border),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: lien.couleur.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(lien.icone, size: 18, color: lien.couleur),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            lien.titre,
                            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: TounColors.navy),
                          ),
                          const SizedBox(height: 2),
                          Text(lien.texte, style: const TextStyle(fontSize: 11, color: TounColors.textSecondary)),
                        ],
                      ),
                    ),
                    const Icon(Icons.chevron_right_rounded, color: TounColors.textSecondary),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ],
      ),
    );
  }

  Widget _mesFormations(TableauDeBord donnees) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF18213F), Color(0xFF244D86), Color(0xFF20A1B8)],
        ),
        boxShadow: [
          BoxShadow(color: TounColors.navy.withValues(alpha: 0.25), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _enTeteCarte('Mes formations', 'Voir toutes', onTap: widget.onVoirMesFormations, clair: true),
          const SizedBox(height: 12),
          if (donnees.mesFormations.isEmpty)
            _etatVide(
              clair: true,
              icone: Icons.menu_book_rounded,
              titre: 'Aucune formation pour le moment',
              texte: 'Après votre premier achat, vos formations apparaîtront automatiquement ici.',
              bouton: Material(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                child: InkWell(
                  onTap: widget.onVoirFormations,
                  borderRadius: BorderRadius.circular(10),
                  child: const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 18, vertical: 11),
                    child: Text(
                      'Voir le catalogue',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: TounColors.blue),
                    ),
                  ),
                ),
              ),
            )
          else
            for (final formation in donnees.mesFormations) ...[
              InkWell(
                onTap: () => _ouvrir(FormationDetailScreen(idForm: formation.idForm)),
                borderRadius: BorderRadius.circular(14),
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
                  ),
                  child: Row(
                    children: [
                      _miniature(formation.image, 50),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              formation.categorie.toUpperCase(),
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.5,
                                color: Colors.white.withValues(alpha: 0.7),
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              formation.titre,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: Colors.white),
                            ),
                            const SizedBox(height: 7),
                            _barreProgression(
                              formation.progression,
                              const Color(0xFF67E0E6),
                              Colors.white.withValues(alpha: 0.18),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Text(
                        '${formation.progression} %',
                        style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Colors.white),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 8),
            ],
        ],
      ),
    );
  }

  Widget _monApprentissage(TableauDeBord donnees) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _carteBlanche,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _enTeteCarte('Mon apprentissage', 'Vue d\'ensemble', onTap: widget.onVoirMesFormations),
          const SizedBox(height: 12),
          _ligneInfo(
            icone: Icons.timer_outlined,
            couleur: TounColors.teal,
            titre: 'Temps d\'apprentissage',
            texte: 'Votre temps cumulé sur TOUN',
            fin: Text(
              '${donnees.tempsApprentissage} h',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: TounColors.navy),
            ),
          ),
        ],
      ),
    );
  }

  Widget _besoinAide() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _carteBlanche,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _enTeteCarte('Besoin d\'aide ?', 'Équipe pédagogique'),
          const SizedBox(height: 12),
          _ligneInfo(
            icone: Icons.support_agent_rounded,
            couleur: _orange,
            titre: 'Parler à un formateur',
            texte: 'Prenez rendez-vous avec l\'équipe',
            fin: _boutonDegrade('Rendez-vous', () => _ouvrir(const RendezVousScreen())),
          ),
        ],
      ),
    );
  }

  Widget _ligneInfo({
    required IconData icone,
    required Color couleur,
    required String titre,
    required String texte,
    required Widget fin,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: TounColors.background,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: TounColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: couleur.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icone, size: 19, color: couleur),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(titre, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: TounColors.navy)),
                const SizedBox(height: 2),
                Text(texte, style: const TextStyle(fontSize: 10.5, color: TounColors.textSecondary)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          fin,
        ],
      ),
    );
  }

  Widget _miniature(String image, double taille) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: image.isNotEmpty
          ? Image.network(
              image,
              width: taille,
              height: taille,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => _miniatureVide(taille),
            )
          : _miniatureVide(taille),
    );
  }

  Widget _miniatureVide(double taille) {
    return Container(
      width: taille,
      height: taille,
      color: TounColors.teal.withValues(alpha: 0.15),
      child: const Icon(Icons.menu_book_rounded, color: TounColors.teal),
    );
  }

  Widget _barreProgression(int progression, Color couleur, Color fond) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(6),
      child: LinearProgressIndicator(
        value: progression.clamp(0, 100) / 100,
        minHeight: 6,
        backgroundColor: fond,
        valueColor: AlwaysStoppedAnimation(couleur),
      ),
    );
  }
}
