import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/formation.dart';
import '../services/api_client.dart';
import '../services/ouverture_paiement.dart';
import '../services/paiement_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import 'payment_confirmation_screen.dart';

class _MoyenPaiement {
  final String code;
  final String label;
  final String logo;

  const _MoyenPaiement({required this.code, required this.label, required this.logo});
}

const List<_MoyenPaiement> _moyens = [
  _MoyenPaiement(code: 'WAVE', label: 'Wave', logo: 'assets/images/paiement_wave.png'),
  _MoyenPaiement(code: 'OM', label: 'Orange Money', logo: 'assets/images/paiement_orange_money.png'),
  _MoyenPaiement(code: 'MOOV', label: 'Moov Money', logo: 'assets/images/paiement_moov_money.png'),
];

class PaymentScreen extends StatefulWidget {
  final Formation formation;

  const PaymentScreen({super.key, required this.formation});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  String _moyenChoisi = _moyens.first.code;
  bool _chargement = false;
  String? _erreur;
  PaiementInitie? _paiementInitie;

  Future<void> _payerMaintenant() async {
    setState(() {
      _chargement = true;
      _erreur = null;
    });

    try {
      final paiement = await PaiementService.instance.creer(
        widget.formation.idForm,
        _moyenChoisi,
        retourApp: kIsWeb ? Uri.base.origin : null,
      );
      setState(() => _paiementInitie = paiement);

      final uri = Uri.parse(paiement.lienPaiement);
      await ouvrirPagePaiement(uri);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } catch (_) {
      setState(() => _erreur = 'Impossible de lancer le paiement.');
    } finally {
      if (mounted) {
        setState(() => _chargement = false);
      }
    }
  }

  void _verifierPaiement() {
    if (_paiementInitie == null) {
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PaymentConfirmationScreen(reference: _paiementInitie!.reference),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final formation = widget.formation;

    return Scaffold(
      backgroundColor: TounColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.white,
        flexibleSpace: Container(decoration: const BoxDecoration(gradient: TounColors.headerGradient)),
        systemOverlayStyle: SystemUiOverlayStyle.light,
        title: const Text('Paiement sécurisé', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
              child: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: formation.image.isNotEmpty
                        ? Image.network(formation.image, width: 54, height: 54, fit: BoxFit.cover)
                        : Container(width: 54, height: 54, color: TounColors.teal.withValues(alpha: 0.12), child: const Icon(Icons.menu_book_rounded, color: TounColors.teal)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(formation.titre, style: const TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
                        const SizedBox(height: 4),
                        Text('${formation.duree} · ${formation.niveau}', style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary)),
                      ],
                    ),
                  ),
                  Text('${formation.prix.toStringAsFixed(0)} FCFA', style: const TextStyle(fontWeight: FontWeight.w800, color: TounColors.navy)),
                ],
              ),
            ),
            const SizedBox(height: 22),
            const Text('Choisir un moyen de paiement', style: TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
            const SizedBox(height: 10),
            RadioGroup<String>(
              groupValue: _moyenChoisi,
              onChanged: (v) => setState(() => _moyenChoisi = v!),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: _moyens.map((moyen) {
                  final selectionne = moyen.code == _moyenChoisi;

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(14),
                      onTap: () => setState(() => _moyenChoisi = moyen.code),
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: selectionne ? TounColors.teal : TounColors.border, width: selectionne ? 1.6 : 1),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: TounColors.border),
                              ),
                              child: Image.asset(moyen.logo, fit: BoxFit.contain),
                            ),
                            const SizedBox(width: 12),
                            Expanded(child: Text(moyen.label, style: const TextStyle(fontWeight: FontWeight.w600))),
                            Radio<String>(
                              value: moyen.code,
                              activeColor: TounColors.teal,
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14)),
              child: Column(
                children: [
                  const Text('Résumé', style: TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
                  const SizedBox(height: 10),
                  _ligneResume('Montant', '${formation.prix.toStringAsFixed(0)} FCFA'),
                  const SizedBox(height: 6),
                  const _LigneResumeStatic('Frais', '0 FCFA'),
                  const Divider(height: 22),
                  _ligneResume('Total', '${formation.prix.toStringAsFixed(0)} FCFA', accent: true),
                ],
              ),
            ),
            const SizedBox(height: 20),
            if (_erreur != null) ...[
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: const Color(0xFFFDECEC), borderRadius: BorderRadius.circular(10)),
                child: Text(_erreur!, style: const TextStyle(color: Color(0xFFB3261E), fontSize: 13)),
              ),
              const SizedBox(height: 14),
            ],
            TounButton(
              texte: 'Payer maintenant →',
              onPressed: _payerMaintenant,
              chargement: _chargement,
            ),
            if (_paiementInitie != null) ...[
              const SizedBox(height: 12),
              TounButton(
                texte: 'J\'ai terminé mon paiement',
                style: TounButtonStyle.contour,
                onPressed: _verifierPaiement,
              ),
            ],
            const SizedBox(height: 16),
            const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.lock_outline_rounded, size: 14, color: TounColors.textSecondary),
                SizedBox(width: 6),
                Text('Paiement sécurisé et crypté', style: TextStyle(fontSize: 11.5, color: TounColors.textSecondary)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _ligneResume(String label, String valeur, {bool accent = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(fontWeight: accent ? FontWeight.w800 : FontWeight.w500, color: accent ? TounColors.navy : TounColors.textSecondary)),
        Text(valeur, style: TextStyle(fontWeight: FontWeight.w800, color: accent ? TounColors.teal : TounColors.navy)),
      ],
    );
  }
}

class _LigneResumeStatic extends StatelessWidget {
  final String label;
  final String valeur;

  const _LigneResumeStatic(this.label, this.valeur);

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: TounColors.textSecondary)),
        Text(valeur, style: const TextStyle(color: TounColors.navy, fontWeight: FontWeight.w600)),
      ],
    );
  }
}
