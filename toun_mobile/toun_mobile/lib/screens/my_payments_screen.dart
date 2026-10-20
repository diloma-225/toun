import 'package:flutter/material.dart';

import '../models/paiement.dart';
import '../services/paiement_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class MyPaymentsScreen extends StatefulWidget {
  const MyPaymentsScreen({super.key});

  @override
  State<MyPaymentsScreen> createState() => _MyPaymentsScreenState();
}

class _MyPaymentsScreenState extends State<MyPaymentsScreen> {
  late Future<HistoriquePaiements> _futur;
  String _filtre = 'tous';

  @override
  void initState() {
    super.initState();
    _futur = PaiementService.instance.historique();
  }

  Future<void> _actualiser() async {
    setState(() {
      _futur = PaiementService.instance.historique();
    });
    await _futur;
  }

  String _formaterMontant(double montant) {
    final entier = montant.round().toString();
    final buffer = StringBuffer();

    for (var i = 0; i < entier.length; i++) {
      if (i > 0 && (entier.length - i) % 3 == 0) buffer.write(' ');
      buffer.write(entier[i]);
    }

    return '${buffer.toString()} FCFA';
  }

  String _formaterDate(String? brute) {
    if (brute == null || brute.isEmpty) return '—';

    final date = DateTime.tryParse(brute);
    if (date == null) return brute;

    final jour = date.day.toString().padLeft(2, '0');
    final mois = date.month.toString().padLeft(2, '0');
    final heure = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');

    return '$jour/$mois/${date.year} à $heure:$minute';
  }

  String _libelleMode(String mode) {
    switch (mode.toUpperCase()) {
      case 'WAVE':
        return 'Wave';
      case 'OM':
      case 'ORANGE':
        return 'Orange Money';
      case 'MTN':
        return 'MTN Mobile Money';
      case 'MOOV':
        return 'Moov Money';
      case '':
        return 'Mobile Money';
      default:
        return mode;
    }
  }

  String? _logoMode(String mode) {
    switch (mode.toUpperCase()) {
      case 'WAVE':
        return 'assets/images/paiement_wave.png';
      case 'OM':
      case 'ORANGE':
        return 'assets/images/paiement_orange_money.png';
      case 'MOOV':
        return 'assets/images/paiement_moov_money.png';
      default:
        return null;
    }
  }

  ({String libelle, Color couleur, IconData icone}) _styleStatut(String statut) {
    switch (statut) {
      case 'paye':
        return (libelle: 'Payé', couleur: TounColors.green, icone: Icons.check_circle_rounded);
      case 'en_attente':
        return (libelle: 'En attente', couleur: const Color(0xFF20A1B8), icone: Icons.schedule_rounded);
      default:
        return (libelle: 'Échoué', couleur: const Color(0xFFC94A4A), icone: Icons.cancel_rounded);
    }
  }

  List<Paiement> _filtrer(List<Paiement> paiements) {
    switch (_filtre) {
      case 'payes':
        return paiements.where((p) => p.estPaye).toList();
      case 'attente':
        return paiements.where((p) => p.estEnAttente).toList();
      default:
        return paiements;
    }
  }

  void _ouvrirDetail(Paiement paiement) {
    final style = _styleStatut(paiement.statut);

    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(22, 14, 22, 22),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: TounColors.border,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: style.couleur.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Icon(style.icone, color: style.couleur),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            paiement.estPaye ? 'Reçu de paiement' : 'Détail du paiement',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: TounColors.navy),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            style.libelle,
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: style.couleur),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                _ligneDetail('Formation', paiement.formation),
                _ligneDetail('Montant', _formaterMontant(paiement.montant)),
                _ligneDetail('Moyen de paiement', _libelleMode(paiement.mode)),
                _ligneDetail('Date', _formaterDate(paiement.date)),
                _ligneDetail('Référence', paiement.reference),
                if (paiement.numeroRecu.isNotEmpty) _ligneDetail('N° de reçu', paiement.numeroRecu),
                const SizedBox(height: 18),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Fermer'),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _ligneDetail(String libelle, String valeur) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 11),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: TounColors.border)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(libelle, style: const TextStyle(fontSize: 12.5, color: TounColors.textSecondary)),
          ),
          Expanded(
            child: Text(
              valeur,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: TounColors.navy),
            ),
          ),
        ],
      ),
    );
  }

  Widget _carteResume(String libelle, String valeur, IconData icone, Color couleur) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: TounColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icone, size: 18, color: couleur),
            const SizedBox(height: 8),
            Text(
              valeur,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: TounColors.navy),
            ),
            const SizedBox(height: 2),
            Text(libelle, style: const TextStyle(fontSize: 10.5, color: TounColors.textSecondary)),
          ],
        ),
      ),
    );
  }

  Widget _puceFiltre(String valeur, String libelle) {
    final actif = _filtre == valeur;

    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(libelle),
        selected: actif,
        onSelected: (_) => setState(() => _filtre = valeur),
        showCheckmark: false,
        selectedColor: TounColors.teal,
        backgroundColor: Colors.white,
        side: BorderSide(color: actif ? TounColors.teal : TounColors.border),
        labelStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: actif ? Colors.white : TounColors.textSecondary,
        ),
      ),
    );
  }

  Widget _cartePaiement(Paiement paiement) {
    final style = _styleStatut(paiement.statut);

    return InkWell(
      onTap: () => _ouvrirDetail(paiement),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 6)),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              clipBehavior: Clip.antiAlias,
              decoration: BoxDecoration(
                color: _logoMode(paiement.mode) == null ? TounColors.blue.withValues(alpha: 0.10) : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: TounColors.border),
              ),
              child: _logoMode(paiement.mode) == null
                  ? const Icon(Icons.receipt_long_rounded, size: 19, color: TounColors.blue)
                  : Image.asset(_logoMode(paiement.mode)!, fit: BoxFit.contain),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    paiement.formation,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: TounColors.navy),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${_libelleMode(paiement.mode)} · ${_formaterDate(paiement.date)}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 11, color: TounColors.textSecondary),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  _formaterMontant(paiement.montant),
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800, color: TounColors.navy),
                ),
                const SizedBox(height: 5),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: style.couleur.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    style.libelle,
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: style.couleur),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _etatVide(String titre, String texte, IconData icone) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 40),
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: TounColors.teal.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Icon(icone, color: TounColors.teal, size: 28),
          ),
          const SizedBox(height: 14),
          Text(
            titre,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: TounColors.navy),
          ),
          const SizedBox(height: 6),
          Text(
            texte,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
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
              titre: 'Mes paiements',
              sousTitre: 'Historique de vos achats et reçus.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: FutureBuilder<HistoriquePaiements>(
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
                          'Impossible de charger vos paiements.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final historique = snapshot.data!;
                  final paiements = _filtrer(historique.paiements);

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                      children: [
                        if (historique.collaborateur)
                          Container(
                            margin: const EdgeInsets.only(bottom: 14),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: TounColors.blue.withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Text(
                              'Vos formations sont prises en charge par votre entreprise. Seuls vos achats personnels apparaissent ici.',
                              style: TextStyle(fontSize: 11.5, color: TounColors.navy, height: 1.4),
                            ),
                          ),
                        Row(
                          children: [
                            _carteResume(
                              'Total payé',
                              _formaterMontant(historique.totalDepense),
                              Icons.account_balance_wallet_rounded,
                              TounColors.teal,
                            ),
                            const SizedBox(width: 10),
                            _carteResume(
                              'Réussis',
                              '${historique.payes}',
                              Icons.check_circle_rounded,
                              TounColors.green,
                            ),
                            const SizedBox(width: 10),
                            _carteResume(
                              'En attente',
                              '${historique.enAttente}',
                              Icons.schedule_rounded,
                              const Color(0xFF20A1B8),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _puceFiltre('tous', 'Tous (${historique.total})'),
                              _puceFiltre('payes', 'Payés'),
                              _puceFiltre('attente', 'En attente'),
                            ],
                          ),
                        ),
                        const SizedBox(height: 14),
                        if (historique.paiements.isEmpty)
                          _etatVide(
                            'Aucun paiement pour le moment.',
                            'Vos achats de formations apparaîtront ici avec leur reçu.',
                            Icons.receipt_long_rounded,
                          )
                        else if (paiements.isEmpty)
                          _etatVide(
                            'Aucun paiement dans cette catégorie.',
                            'Essayez un autre filtre.',
                            Icons.filter_list_rounded,
                          )
                        else
                          for (final paiement in paiements) ...[
                            _cartePaiement(paiement),
                            const SizedBox(height: 10),
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
