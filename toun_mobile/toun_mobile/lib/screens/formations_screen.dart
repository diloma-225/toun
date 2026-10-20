import 'package:flutter/material.dart';

import '../models/formation.dart';
import '../services/formation_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_app_drawer.dart';
import '../widgets/toun_en_tete.dart';
import 'formation_detail_screen.dart';

class FormationsScreen extends StatefulWidget {
  final VoidCallback onVoirMesFormations;
  final VoidCallback onVoirCertificats;
  final VoidCallback onVoirProfil;

  const FormationsScreen({
    super.key,
    required this.onVoirMesFormations,
    required this.onVoirCertificats,
    required this.onVoirProfil,
  });

  @override
  State<FormationsScreen> createState() => _FormationsScreenState();
}

class _FormationsScreenState extends State<FormationsScreen> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  final _rechercheControleur = TextEditingController();
  final List<String> _categories = ['Toutes', 'Digital', 'Management', 'Marketing'];
  String _categorieActive = 'Toutes';

  late Future<List<Formation>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = FormationService.instance.liste();
  }

  void _relancerRecherche() {
    setState(() {
      _futur = FormationService.instance.liste(
        recherche: _rechercheControleur.text.trim(),
        categorie: _categorieActive,
      );
    });
  }

  @override
  void dispose() {
    _rechercheControleur.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: TounColors.background,
      drawer: TounAppDrawer(
        actif: 'catalogue',
        onFormations: () => Navigator.of(context).pop(),
        onMesFormations: widget.onVoirMesFormations,
        onCertificats: widget.onVoirCertificats,
        onProfil: widget.onVoirProfil,
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Formations',
              sousTitre: 'Trouvez la formation qui vous correspond.',
              onMenu: () => _scaffoldKey.currentState?.openDrawer(),
              bas: TextField(
                controller: _rechercheControleur,
                onSubmitted: (_) => _relancerRecherche(),
                decoration: InputDecoration(
                  hintText: 'Rechercher une formation...',
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.tune_rounded),
                    onPressed: _relancerRecherche,
                  ),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                ),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 36,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _categories.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final categorie = _categories[index];
                  final actif = categorie == _categorieActive;

                  return ChoiceChip(
                    label: Text(categorie),
                    selected: actif,
                    onSelected: (_) {
                      setState(() => _categorieActive = categorie);
                      _relancerRecherche();
                    },
                    selectedColor: TounColors.teal,
                    labelStyle: TextStyle(color: actif ? Colors.white : TounColors.textPrimary, fontWeight: FontWeight.w600),
                    backgroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: BorderSide(color: TounColors.border)),
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: FutureBuilder<List<Formation>>(
                future: _futur,
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) {
                    return const Center(child: CircularProgressIndicator());
                  }

                  if (snapshot.hasError) {
                    return Center(child: Text('Erreur : ${snapshot.error}', style: const TextStyle(color: TounColors.textSecondary)));
                  }

                  final formations = snapshot.data ?? [];

                  if (formations.isEmpty) {
                    return const Center(child: Text('Aucune formation trouvée.', style: TextStyle(color: TounColors.textSecondary)));
                  }

                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                    itemCount: formations.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) => _carteFormation(formations[index]),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _carteFormation(Formation formation) {
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: formation.image.isNotEmpty
                  ? Image.network(formation.image, width: 64, height: 64, fit: BoxFit.cover)
                  : Container(
                      width: 64,
                      height: 64,
                      color: TounColors.blue.withValues(alpha: 0.12),
                      child: const Icon(Icons.menu_book_rounded, color: TounColors.blue),
                    ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(formation.titre, style: const TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
                  const SizedBox(height: 3),
                  Text(
                    formation.formateur.isNotEmpty ? '${formation.categorie} · ${formation.formateur}' : formation.categorie,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 11, color: TounColors.textSecondary),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 10,
                    runSpacing: 4,
                    children: [
                      if (formation.duree.isNotEmpty) _infoPuce(Icons.schedule_rounded, formation.duree),
                      if (formation.niveau.isNotEmpty) _infoPuce(Icons.bar_chart_rounded, formation.niveau),
                    ],
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                _etoiles(formation.idForm),
                const SizedBox(height: 6),
                Text(
                  '${formation.prix.toStringAsFixed(0)} FCFA',
                  style: const TextStyle(fontWeight: FontWeight.w800, color: TounColors.teal, fontSize: 12.5),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _etoiles(int idForm) {
    final note = 4.4 + (idForm % 6) * 0.1;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(Icons.star_rounded, size: 13, color: Color(0xFF20A1B8)),
        const SizedBox(width: 2),
        Text(note.toStringAsFixed(1), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: TounColors.navy)),
      ],
    );
  }

  Widget _infoPuce(IconData icone, String texte) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icone, size: 12, color: TounColors.gray),
        const SizedBox(width: 3),
        Text(texte, style: const TextStyle(fontSize: 10.5, color: TounColors.textSecondary)),
      ],
    );
  }
}
