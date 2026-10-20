import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class Live {
  final int id;
  final String titre;
  final String formation;
  final String formateur;
  final String date;
  final String heure;
  final String duree;
  final String lien;
  final String statut;

  Live({
    required this.id,
    required this.titre,
    required this.formation,
    required this.formateur,
    required this.date,
    required this.heure,
    required this.duree,
    required this.lien,
    required this.statut,
  });

  factory Live.fromJson(Map<String, dynamic> json) {
    return Live(
      id: (json['id_live'] as num?)?.toInt() ?? 0,
      titre: (json['titre'] ?? '').toString(),
      formation: (json['formation'] ?? '').toString(),
      formateur: (json['formateur'] ?? '').toString(),
      date: (json['date'] ?? '').toString(),
      heure: (json['heure'] ?? '').toString(),
      duree: (json['duree'] ?? '').toString(),
      lien: (json['lien'] ?? '').toString(),
      statut: (json['statut'] ?? '').toString(),
    );
  }
}

class LivesDonnees {
  final List<Live> enCours;
  final List<Live> aVenir;
  final List<Live> termines;

  LivesDonnees({required this.enCours, required this.aVenir, required this.termines});

  bool get estVide => enCours.isEmpty && aVenir.isEmpty && termines.isEmpty;

  static List<Live> _liste(dynamic brute) {
    final liste = brute as List<dynamic>? ?? [];
    return liste.map((e) => Live.fromJson(e as Map<String, dynamic>)).toList();
  }

  factory LivesDonnees.fromJson(Map<String, dynamic> json) {
    return LivesDonnees(
      enCours: _liste(json['en_cours']),
      aVenir: _liste(json['a_venir']),
      termines: _liste(json['termines']),
    );
  }
}

class LivesScreen extends StatefulWidget {
  const LivesScreen({super.key});

  @override
  State<LivesScreen> createState() => _LivesScreenState();
}

class _LivesScreenState extends State<LivesScreen> {
  static const List<String> _mois = [
    'JAN', 'FÉV', 'MAR', 'AVR', 'MAI', 'JUIN',
    'JUIL', 'AOÛ', 'SEP', 'OCT', 'NOV', 'DÉC',
  ];

  late Future<LivesDonnees> _futur;

  @override
  void initState() {
    super.initState();
    _futur = _charger();
  }

  Future<LivesDonnees> _charger() async {
    final donnees = await ApiClient.instance.get('lives.php');
    return LivesDonnees.fromJson(donnees);
  }

  Future<void> _actualiser() async {
    setState(() {
      _futur = _charger();
    });
    await _futur;
  }

  Future<void> _rejoindre(Live live) async {
    final uri = Uri.tryParse(live.lien);
    final ouvert = uri != null && await launchUrl(uri, mode: LaunchMode.externalApplication);

    if (!ouvert && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le lien du live.')),
      );
    }
  }

  Widget _titreSection(String titre, int nombre, Color couleur) {
    return Padding(
      padding: const EdgeInsets.only(top: 6, bottom: 10),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 18,
            decoration: BoxDecoration(color: couleur, borderRadius: BorderRadius.circular(4)),
          ),
          const SizedBox(width: 8),
          Text(
            titre,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: TounColors.navy),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: couleur.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              '$nombre',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: couleur),
            ),
          ),
        ],
      ),
    );
  }

  Widget _boiteDate(Live live, Color couleur) {
    final date = DateTime.tryParse(live.date);

    return Container(
      width: 56,
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        color: couleur.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(
            date == null ? '--' : date.day.toString().padLeft(2, '0'),
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: couleur),
          ),
          Text(
            date == null ? '' : _mois[date.month - 1],
            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: couleur),
          ),
        ],
      ),
    );
  }

  Widget _info(IconData icone, String texte) {
    return Padding(
      padding: const EdgeInsets.only(top: 3),
      child: Row(
        children: [
          Icon(icone, size: 13, color: TounColors.textSecondary),
          const SizedBox(width: 5),
          Expanded(
            child: Text(
              texte,
              style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  Widget _carte(Live live) {
    final enCours = live.statut == 'en_cours';
    final termine = live.statut == 'termine';
    final couleur = enCours ? const Color(0xFFE5484D) : (termine ? TounColors.gray : TounColors.blue);
    final horaire = live.duree.isNotEmpty ? '${live.heure} · ${live.duree}' : live.heure;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: TounColors.border),
        boxShadow: [
          BoxShadow(color: TounColors.navy.withValues(alpha: 0.05), blurRadius: 14, offset: const Offset(0, 6)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _boiteDate(live, couleur),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (enCours)
                      Container(
                        margin: const EdgeInsets.only(bottom: 5),
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: couleur,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text(
                          'EN DIRECT',
                          style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w800, color: Colors.white),
                        ),
                      ),
                    Text(
                      live.titre,
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: TounColors.navy),
                    ),
                    if (live.formation.isNotEmpty) _info(Icons.menu_book_rounded, live.formation),
                    if (live.formateur.isNotEmpty) _info(Icons.person_outline_rounded, live.formateur),
                    _info(Icons.schedule_rounded, horaire),
                  ],
                ),
              ),
            ],
          ),
          if (!termine) ...[
            const SizedBox(height: 12),
            SizedBox(
              height: 42,
              child: ElevatedButton.icon(
                onPressed: live.lien.isEmpty ? null : () => _rejoindre(live),
                style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(42)),
                icon: const Icon(Icons.videocam_rounded, size: 18),
                label: Text(live.lien.isEmpty ? 'Lien bientôt disponible' : 'Rejoindre le live'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _vide() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 60, horizontal: 12),
      child: Column(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: TounColors.teal.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(Icons.videocam_rounded, color: TounColors.teal, size: 32),
          ),
          const SizedBox(height: 14),
          const Text(
            'Aucun live programmé pour le moment.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: TounColors.navy),
          ),
          const SizedBox(height: 6),
          const Text(
            'Les sessions en direct de vos formateurs apparaîtront ici.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Mes Lives',
              sousTitre: 'Participez aux sessions en direct de vos formateurs.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: FutureBuilder<LivesDonnees>(
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
                          'Impossible de charger les lives.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final donnees = snapshot.data!;

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                      children: [
                        if (donnees.estVide) _vide(),
                        if (donnees.enCours.isNotEmpty) ...[
                          _titreSection('En cours', donnees.enCours.length, const Color(0xFFE5484D)),
                          for (final live in donnees.enCours) _carte(live),
                        ],
                        if (donnees.aVenir.isNotEmpty) ...[
                          _titreSection('À venir', donnees.aVenir.length, TounColors.blue),
                          for (final live in donnees.aVenir) _carte(live),
                        ],
                        if (donnees.termines.isNotEmpty) ...[
                          _titreSection('Terminés', donnees.termines.length, TounColors.gray),
                          for (final live in donnees.termines) _carte(live),
                        ],
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
