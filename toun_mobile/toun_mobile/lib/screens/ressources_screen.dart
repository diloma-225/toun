import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class Ressource {
  final String titre;
  final String type;
  final String formation;
  final String module;
  final String lien;

  Ressource({required this.titre, required this.type, required this.formation, this.module = '', required this.lien});

  factory Ressource.fromJson(Map<String, dynamic> json) {
    return Ressource(
      titre: (json['titre'] ?? '') as String,
      type: (json['type'] ?? 'autre') as String,
      formation: (json['formation'] ?? '') as String,
      module: (json['module'] ?? '') as String,
      lien: (json['lien'] ?? '') as String,
    );
  }
}

class RessourcesScreen extends StatefulWidget {
  const RessourcesScreen({super.key});

  @override
  State<RessourcesScreen> createState() => _RessourcesScreenState();
}

class _RessourcesScreenState extends State<RessourcesScreen> {
  late Future<List<Ressource>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = _charger();
  }

  Future<List<Ressource>> _charger() async {
    final donnees = await ApiClient.instance.get('ressources.php');
    final liste = donnees['ressources'] as List<dynamic>? ?? [];
    return liste.map((e) => Ressource.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> _actualiser() async {
    setState(() => _futur = _charger());
    await _futur;
  }

  Future<void> _ouvrir(Ressource ressource) async {
    final ouvert = await launchUrl(Uri.parse(ressource.lien), mode: LaunchMode.externalApplication);

    if (!ouvert && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir ce document.')),
      );
    }
  }

  IconData _icone(String type) {
    switch (type) {
      case 'pdf':
        return Icons.picture_as_pdf_rounded;
      case 'video':
        return Icons.play_circle_rounded;
      case 'doc':
        return Icons.description_rounded;
      case 'lien':
        return Icons.link_rounded;
      default:
        return Icons.insert_drive_file_rounded;
    }
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
              titre: 'Ressources',
              sousTitre: 'Les supports de cours et documents utiles de vos formations.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: FutureBuilder<List<Ressource>>(
                future: _futur,
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) {
                    return const Center(child: CircularProgressIndicator());
                  }

                  if (snapshot.hasError) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text('${snapshot.error}', textAlign: TextAlign.center, style: const TextStyle(color: TounColors.textSecondary)),
                      ),
                    );
                  }

                  final ressources = snapshot.data ?? [];

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                      children: [
                        if (ressources.isEmpty)
                          const Padding(
                            padding: EdgeInsets.symmetric(vertical: 50),
                            child: Text(
                              'Aucune ressource pour le moment. Elles apparaissent ici dès que vous êtes inscrit à une formation.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12.5, color: TounColors.textSecondary, height: 1.5),
                            ),
                          )
                        else
                          for (final ressource in ressources) ...[
                            _carte(ressource),
                            const SizedBox(height: 12),
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

  Widget _carte(Ressource ressource) {
    return InkWell(
      onTap: () => _ouvrir(ressource),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: TounColors.border),
        ),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(color: TounColors.teal.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(14)),
              child: Icon(_icone(ressource.type), color: TounColors.teal),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(ressource.titre, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: TounColors.navy)),
                  const SizedBox(height: 3),
                  Text(
                    ressource.module.isNotEmpty ? '${ressource.formation} · ${ressource.module}' : ressource.formation,
                    style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary),
                  ),
                ],
              ),
            ),
            const Icon(Icons.open_in_new_rounded, size: 18, color: TounColors.blue),
          ],
        ),
      ),
    );
  }
}
