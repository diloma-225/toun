import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class Certificat {
  final int id;
  final String numero;
  final String date;
  final String formation;
  final String niveau;
  final String image;
  final String apercu;
  final String lien;

  Certificat({
    required this.id,
    required this.numero,
    required this.date,
    required this.formation,
    required this.niveau,
    required this.image,
    this.apercu = '',
    required this.lien,
  });

  factory Certificat.fromJson(Map<String, dynamic> json) {
    return Certificat(
      id: (json['id_certificat'] as num?)?.toInt() ?? 0,
      numero: (json['numero'] ?? '') as String,
      date: (json['date'] ?? '') as String? ?? '',
      formation: (json['formation'] ?? '') as String,
      niveau: (json['niveau'] ?? '') as String? ?? '',
      image: (json['image'] ?? '') as String? ?? '',
      apercu: (json['apercu'] ?? '') as String? ?? '',
      lien: (json['lien'] ?? '') as String? ?? '',
    );
  }
}

class CertificatsScreen extends StatefulWidget {
  final VoidCallback? onMenu;
  final VoidCallback? onVoirMesFormations;

  const CertificatsScreen({super.key, this.onMenu, this.onVoirMesFormations});

  @override
  State<CertificatsScreen> createState() => _CertificatsScreenState();
}

class _CertificatsScreenState extends State<CertificatsScreen> {
  late Future<List<Certificat>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = _charger();
  }

  Future<List<Certificat>> _charger() async {
    final donnees = await ApiClient.instance.get('certificats.php');
    final liste = donnees['certificats'] as List<dynamic>? ?? [];
    return liste.map((e) => Certificat.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> _actualiser() async {
    setState(() {
      _futur = _charger();
    });
    await _futur;
  }

  String _formaterDate(String brute) {
    final date = DateTime.tryParse(brute);
    if (date == null) return '';
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  Future<void> _ouvrir(Certificat certificat) async {
    final ouvert = await launchUrl(Uri.parse(certificat.lien), mode: LaunchMode.externalApplication);

    if (!ouvert && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le certificat.')),
      );
    }
  }

  Widget _carte(Certificat certificat) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: TounColors.border),
        boxShadow: [
          BoxShadow(color: TounColors.navy.withValues(alpha: 0.06), blurRadius: 16, offset: const Offset(0, 8)),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            height: 6,
            decoration: const BoxDecoration(gradient: TounColors.buttonGradient),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 84,
                  height: 63,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: TounColors.border),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: certificat.apercu.isNotEmpty
                      ? Image.network(
                          certificat.apercu,
                          fit: BoxFit.contain,
                          errorBuilder: (_, _, _) => const Icon(Icons.workspace_premium_rounded, color: TounColors.teal, size: 28),
                        )
                      : const Icon(Icons.workspace_premium_rounded, color: TounColors.teal, size: 28),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: TounColors.green.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text(
                          'Certificat obtenu',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: TounColors.green),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        certificat.formation,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: TounColors.navy),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Obtenu le ${_formaterDate(certificat.date)}${certificat.niveau.isNotEmpty ? ' · ${certificat.niveau}' : ''}',
                        style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'N° ${certificat.numero}',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: TounColors.blue),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
            child: SizedBox(
              height: 44,
              child: ElevatedButton.icon(
                onPressed: certificat.lien.isEmpty ? null : () => _ouvrir(certificat),
                style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(44)),
                icon: const Icon(Icons.visibility_rounded, size: 18),
                label: const Text('Voir et télécharger'),
              ),
            ),
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
              titre: 'Mes certificats',
              sousTitre: 'Vos certificats obtenus après la réussite de vos formations.',
              onMenu: widget.onMenu,
              onRetour: widget.onMenu == null && Navigator.of(context).canPop() ? () => Navigator.of(context).pop() : null,
            ),
            Expanded(
              child: FutureBuilder<List<Certificat>>(
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
                          'Impossible de charger vos certificats.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final certificats = snapshot.data ?? [];

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                      children: [
                        if (certificats.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 50, horizontal: 12),
                            child: Column(
                              children: [
                                Container(
                                  width: 64,
                                  height: 64,
                                  decoration: BoxDecoration(
                                    color: TounColors.purple.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Icon(Icons.workspace_premium_rounded, color: TounColors.purple, size: 32),
                                ),
                                const SizedBox(height: 14),
                                const Text(
                                  'Aucun certificat pour le moment.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: TounColors.navy),
                                ),
                                const SizedBox(height: 6),
                                const Text(
                                  'Terminez une formation à 100 % : votre certificat apparaîtra ici automatiquement.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
                                ),
                                if (widget.onVoirMesFormations != null) ...[
                                  const SizedBox(height: 16),
                                  ElevatedButton(
                                    onPressed: widget.onVoirMesFormations,
                                    style: ElevatedButton.styleFrom(
                                      minimumSize: const Size(0, 44),
                                      padding: const EdgeInsets.symmetric(horizontal: 22),
                                    ),
                                    child: const Text('Continuer mes formations'),
                                  ),
                                ],
                              ],
                            ),
                          )
                        else
                          for (final certificat in certificats) ...[
                            _carte(certificat),
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
}
