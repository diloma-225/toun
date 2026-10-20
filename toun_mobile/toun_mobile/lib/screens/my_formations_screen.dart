import 'package:flutter/material.dart';

import '../models/formation.dart';
import '../services/formation_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_app_drawer.dart';
import '../widgets/toun_en_tete.dart';
import 'formation_detail_screen.dart';

class MyFormationsScreen extends StatefulWidget {
  final VoidCallback onVoirFormations;
  final VoidCallback onVoirCertificats;
  final VoidCallback onVoirProfil;

  const MyFormationsScreen({
    super.key,
    required this.onVoirFormations,
    required this.onVoirCertificats,
    required this.onVoirProfil,
  });

  @override
  State<MyFormationsScreen> createState() => _MyFormationsScreenState();
}

class _MyFormationsScreenState extends State<MyFormationsScreen> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  late Future<List<MaFormation>> _futur;
  String _filtre = 'toutes';

  @override
  void initState() {
    super.initState();
    _futur = FormationService.instance.mesFormations();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: TounColors.background,
      drawer: TounAppDrawer(
        actif: 'mes_formations',
        onFormations: widget.onVoirFormations,
        onMesFormations: () => Navigator.of(context).pop(),
        onCertificats: widget.onVoirCertificats,
        onProfil: widget.onVoirProfil,
      ),
      body: SafeArea(
        top: false,
        child: FutureBuilder<List<MaFormation>>(
          future: _futur,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return Center(child: Text('Erreur : ${snapshot.error}', style: const TextStyle(color: TounColors.textSecondary)));
            }

            final toutes = snapshot.data ?? [];
            final enCours = toutes.where((f) => f.statut == 'en_cours').toList();
            final terminees = toutes.where((f) => f.statut == 'terminee').toList();

            final affichees = switch (_filtre) {
              'en_cours' => enCours,
              'terminee' => terminees,
              _ => toutes,
            };

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TounEnTete(
                  titre: 'Mes formations',
                  sousTitre: '${toutes.length} formation${toutes.length > 1 ? 's' : ''} · ${enCours.length} en cours',
                  onMenu: () => _scaffoldKey.currentState?.openDrawer(),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      _onglet('En cours (${enCours.length})', 'en_cours'),
                      const SizedBox(width: 10),
                      _onglet('Terminées (${terminees.length})', 'terminee'),
                      const SizedBox(width: 10),
                      _onglet('Toutes (${toutes.length})', 'toutes'),
                    ],
                  ),
                ),
                Expanded(
                  child: affichees.isEmpty
                      ? const Center(child: Text('Aucune formation dans cette catégorie.', style: TextStyle(color: TounColors.textSecondary)))
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                          itemCount: affichees.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 12),
                          itemBuilder: (context, index) => _carte(affichees[index]),
                        ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _onglet(String label, String valeur) {
    final actif = _filtre == valeur;

    return GestureDetector(
      onTap: () => setState(() => _filtre = valeur),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: actif ? TounColors.teal : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: actif ? TounColors.teal : TounColors.border),
        ),
        child: Text(label, style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: actif ? Colors.white : TounColors.textSecondary)),
      ),
    );
  }

  Widget _carte(MaFormation formation) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => FormationDetailScreen(idForm: formation.idForm)),
        );
      },
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 6))],
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: formation.image.isNotEmpty
                  ? Image.network(formation.image, width: 58, height: 58, fit: BoxFit.cover)
                  : Container(width: 58, height: 58, color: TounColors.purple.withValues(alpha: 0.12), child: const Icon(Icons.menu_book_rounded, color: TounColors.purple)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(formation.titre, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
                  const SizedBox(height: 3),
                  Text(formation.categorie, style: const TextStyle(fontSize: 11, color: TounColors.textSecondary)),
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: formation.progression / 100,
                      minHeight: 6,
                      backgroundColor: TounColors.background,
                      valueColor: AlwaysStoppedAnimation(formation.statut == 'terminee' ? TounColors.green : TounColors.teal),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text('${formation.progression}%', style: const TextStyle(fontSize: 11, color: TounColors.textSecondary)),
                ],
              ),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.chevron_right_rounded, color: TounColors.gray),
          ],
        ),
      ),
    );
  }
}
