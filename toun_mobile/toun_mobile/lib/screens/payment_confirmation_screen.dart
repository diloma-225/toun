import 'package:flutter/material.dart';

import '../services/paiement_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import 'formation_detail_screen.dart';
import 'main_shell.dart';

class PaymentConfirmationScreen extends StatefulWidget {
  final String reference;

  const PaymentConfirmationScreen({super.key, required this.reference});

  @override
  State<PaymentConfirmationScreen> createState() => _PaymentConfirmationScreenState();
}

class _PaymentConfirmationScreenState extends State<PaymentConfirmationScreen> {
  late Future<StatutPaiement> _futur;

  @override
  void initState() {
    super.initState();
    _futur = PaiementService.instance.statut(widget.reference);
  }

  void _revenirAuDebut() {
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const MainShell()),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        child: FutureBuilder<StatutPaiement>(
          future: _futur,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return _carte(
                icone: Icons.error_outline_rounded,
                couleurIcone: const Color(0xFF20A1B8),
                fondIcone: const Color(0xFFEAF3F9),
                titre: 'Vérification impossible',
                description: 'Nous n\'avons pas pu vérifier ce paiement.\n${snapshot.error}',
                boutons: [
                  TounButton(texte: 'Retour à l\'accueil →', onPressed: _revenirAuDebut),
                ],
              );
            }

            final statut = snapshot.data!;

            if (statut.estPaye) {
              return _carte(
                icone: Icons.check_rounded,
                couleurIcone: const Color(0xFF22A06B),
                fondIcone: const Color(0xFFE8F8F1),
                titre: 'Paiement réussi !',
                description: 'Votre inscription à la formation « ${statut.formation} » a été effectuée avec succès.',
                boutons: [
                  TounButton(
                    texte: 'Voir ma formation →',
                    onPressed: () {
                      Navigator.of(context).pushAndRemoveUntil(
                        MaterialPageRoute(builder: (_) => const MainShell()),
                        (route) => false,
                      );
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => FormationDetailScreen(idForm: statut.idForm)),
                      );
                    },
                  ),
                  const SizedBox(height: 10),
                  TounButton(texte: 'Retour à l\'accueil', style: TounButtonStyle.contour, onPressed: _revenirAuDebut),
                ],
              );
            }

            if (statut.statut == 'echoue') {
              return _carte(
                icone: Icons.close_rounded,
                couleurIcone: const Color(0xFFB3261E),
                fondIcone: const Color(0xFFFDECEC),
                titre: 'Paiement échoué',
                description: 'La transaction n\'a pas pu être confirmée. Vous pouvez réessayer.',
                boutons: [
                  TounButton(texte: 'Retour à l\'accueil →', onPressed: _revenirAuDebut),
                ],
              );
            }

            return _carte(
              icone: Icons.hourglass_top_rounded,
              couleurIcone: const Color(0xFF20A1B8),
              fondIcone: const Color(0xFFEAF3F9),
              titre: 'Paiement en cours',
              description: 'Votre paiement est en cours de confirmation par l\'opérateur. Cela peut prendre quelques instants.',
              boutons: [
                TounButton(
                  texte: 'Vérifier à nouveau',
                  onPressed: () => setState(() {
                    _futur = PaiementService.instance.statut(widget.reference);
                  }),
                ),
                const SizedBox(height: 10),
                TounButton(texte: 'Retour à l\'accueil', style: TounButtonStyle.contour, onPressed: _revenirAuDebut),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _carte({
    required IconData icone,
    required Color couleurIcone,
    required Color fondIcone,
    required String titre,
    required String description,
    required List<Widget> boutons,
  }) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 76,
              height: 76,
              decoration: BoxDecoration(color: fondIcone, shape: BoxShape.circle),
              child: Icon(icone, color: couleurIcone, size: 36),
            ),
            const SizedBox(height: 22),
            Text(titre, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: TounColors.navy)),
            const SizedBox(height: 10),
            Text(description, textAlign: TextAlign.center, style: const TextStyle(color: TounColors.textSecondary, height: 1.6)),
            const SizedBox(height: 26),
            ...boutons,
          ],
        ),
      ),
    );
  }
}
