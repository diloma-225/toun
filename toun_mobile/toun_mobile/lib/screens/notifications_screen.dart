import 'package:flutter/material.dart';

import '../models/profil.dart';
import '../services/profil_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<List<NotificationTOUN>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = ProfilService.instance.notifications();
  }

  Future<void> _actualiser() async {
    setState(() {
      _futur = ProfilService.instance.notifications();
    });
    await _futur;
  }

  ({IconData icone, Color couleur}) _styleType(String type) {
    switch (type) {
      case 'certificat':
        return (icone: Icons.workspace_premium_rounded, couleur: TounColors.purple);
      case 'paiement':
        return (icone: Icons.check_circle_rounded, couleur: TounColors.green);
      case 'message':
        return (icone: Icons.mail_outline_rounded, couleur: TounColors.purple);
      case 'rendezvous':
        return (icone: Icons.event_available_rounded, couleur: TounColors.teal);
      case 'live':
        return (icone: Icons.videocam_rounded, couleur: Color(0xFFE5484D));
      default:
        return (icone: Icons.menu_book_rounded, couleur: TounColors.blue);
    }
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
      backgroundColor: TounColors.background,
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Notifications',
              sousTitre: 'Vos dernières activités sur TOUN.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: FutureBuilder<List<NotificationTOUN>>(
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
                          'Impossible de charger les notifications.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final elements = snapshot.data ?? [];

                  if (elements.isEmpty) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 56,
                              height: 56,
                              decoration: BoxDecoration(color: TounColors.teal.withValues(alpha: 0.10), borderRadius: BorderRadius.circular(18)),
                              child: const Icon(Icons.notifications_none_rounded, color: TounColors.teal, size: 28),
                            ),
                            const SizedBox(height: 14),
                            const Text(
                              'Aucune notification pour le moment.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: TounColors.navy),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Elles apparaîtront ici : formations, certificats, paiements, messages, rendez-vous et lives.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                      itemCount: elements.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 10),
                      itemBuilder: (context, index) {
                        final notification = elements[index];
                        final style = _styleType(notification.type);
                        final date = _formaterDate(notification.date);

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 6))],
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 38,
                                height: 38,
                                decoration: BoxDecoration(color: style.couleur.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(11)),
                                child: Icon(style.icone, size: 18, color: style.couleur),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(notification.titre, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: TounColors.navy)),
                                    const SizedBox(height: 3),
                                    Text(
                                      date.isNotEmpty ? '${notification.texte} · $date' : notification.texte,
                                      style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
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
