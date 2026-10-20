import 'package:flutter/material.dart';

import '../models/rendez_vous.dart';
import '../services/rendez_vous_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class RendezVousScreen extends StatefulWidget {
  const RendezVousScreen({super.key});

  @override
  State<RendezVousScreen> createState() => _RendezVousScreenState();
}

class _RendezVousScreenState extends State<RendezVousScreen> {
  static const _mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

  late Future<DonneesRendezVous> _futur;
  List<Formateur> _formateurs = [];

  @override
  void initState() {
    super.initState();
    _futur = _charger();
  }

  Future<DonneesRendezVous> _charger() async {
    final donnees = await RendezVousService.instance.charger();
    _formateurs = donnees.formateurs;
    return donnees;
  }

  Future<void> _actualiser() async {
    setState(() {
      _futur = _charger();
    });
    await _futur;
  }

  String _deuxChiffres(int valeur) => valeur.toString().padLeft(2, '0');

  ({String libelle, Color couleur, IconData icone}) _styleStatut(String statut) {
    switch (statut) {
      case 'confirme':
        return (libelle: 'Confirmé', couleur: TounColors.green, icone: Icons.check_circle_rounded);
      case 'annule':
        return (libelle: 'Annulé', couleur: const Color(0xFFC94A4A), icone: Icons.cancel_rounded);
      default:
        return (libelle: 'En attente', couleur: const Color(0xFF20A1B8), icone: Icons.schedule_rounded);
    }
  }

  Future<void> _ouvrirFormulaire() async {
    final motifControleur = TextEditingController();
    DateTime? date;
    TimeOfDay? heure;
    int idFormateur = 0;
    bool envoi = false;
    String erreur = '';

    final envoye = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModal) {
            Future<void> choisirDate() async {
              final maintenant = DateTime.now();
              final choix = await showDatePicker(
                context: context,
                initialDate: date ?? maintenant,
                firstDate: DateTime(maintenant.year, maintenant.month, maintenant.day),
                lastDate: maintenant.add(const Duration(days: 365)),
                helpText: 'Date du rendez-vous',
                cancelText: 'Annuler',
                confirmText: 'Valider',
              );
              if (choix != null) setModal(() => date = choix);
            }

            Future<void> choisirHeure() async {
              final choix = await showTimePicker(
                context: context,
                initialTime: heure ?? const TimeOfDay(hour: 10, minute: 0),
                helpText: 'Heure du rendez-vous',
                cancelText: 'Annuler',
                confirmText: 'Valider',
              );
              if (choix != null) setModal(() => heure = choix);
            }

            Future<void> envoyer() async {
              if (motifControleur.text.trim().isEmpty || date == null || heure == null) {
                setModal(() => erreur = 'Merci de renseigner le motif, la date et l\'heure.');
                return;
              }

              setModal(() {
                envoi = true;
                erreur = '';
              });

              try {
                await RendezVousService.instance.demander(
                  motif: motifControleur.text.trim(),
                  date: '${date!.year}-${_deuxChiffres(date!.month)}-${_deuxChiffres(date!.day)}',
                  heure: '${_deuxChiffres(heure!.hour)}:${_deuxChiffres(heure!.minute)}',
                  idFormateur: idFormateur,
                );
                if (context.mounted) Navigator.of(context).pop(true);
              } catch (e) {
                setModal(() {
                  envoi = false;
                  erreur = e.toString();
                });
              }
            }

            return Padding(
              padding: EdgeInsets.fromLTRB(22, 14, 22, MediaQuery.viewInsetsOf(context).bottom + 22),
              child: SingleChildScrollView(
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
                    const Text(
                      'Demander un rendez-vous',
                      style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: TounColors.navy),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Un formateur vous accompagne sur votre parcours.',
                      style: TextStyle(fontSize: 12, color: TounColors.textSecondary),
                    ),
                    const SizedBox(height: 18),
                    TextField(
                      controller: motifControleur,
                      maxLines: 2,
                      decoration: const InputDecoration(
                        labelText: 'Motif du rendez-vous',
                        hintText: 'Ex. : point sur ma progression',
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      initialValue: idFormateur,
                      decoration: const InputDecoration(labelText: 'Formateur'),
                      items: [
                        const DropdownMenuItem(value: 0, child: Text('Équipe pédagogique TOUN')),
                        for (final formateur in _formateurs)
                          DropdownMenuItem(value: formateur.id, child: Text(formateur.nom)),
                      ],
                      onChanged: (valeur) => setModal(() => idFormateur = valeur ?? 0),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _champChoix(
                            icone: Icons.calendar_month_rounded,
                            texte: date == null
                                ? 'Choisir la date'
                                : '${_deuxChiffres(date!.day)}/${_deuxChiffres(date!.month)}/${date!.year}',
                            onTap: choisirDate,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _champChoix(
                            icone: Icons.schedule_rounded,
                            texte: heure == null
                                ? 'Choisir l\'heure'
                                : '${_deuxChiffres(heure!.hour)}:${_deuxChiffres(heure!.minute)}',
                            onTap: choisirHeure,
                          ),
                        ),
                      ],
                    ),
                    if (erreur.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Text(erreur, style: const TextStyle(fontSize: 12, color: Color(0xFFC94A4A))),
                    ],
                    const SizedBox(height: 18),
                    ElevatedButton(
                      onPressed: envoi ? null : envoyer,
                      child: envoi
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Text('Envoyer la demande'),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    motifControleur.dispose();

    if (envoye == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Votre demande de rendez-vous a bien été envoyée.')),
      );
      _actualiser();
    }
  }

  Widget _champChoix({required IconData icone, required String texte, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: BoxDecoration(
          color: TounColors.background,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: TounColors.border),
        ),
        child: Row(
          children: [
            Icon(icone, size: 18, color: TounColors.teal),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                texte,
                style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: TounColors.navy),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _carte(RendezVous rdv) {
    final style = _styleStatut(rdv.statut);
    final date = DateTime.tryParse(rdv.date);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: TounColors.navy.withValues(alpha: 0.06), blurRadius: 14, offset: const Offset(0, 6)),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 56,
            decoration: BoxDecoration(
              gradient: TounColors.buttonGradient,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  date != null ? '${date.day}' : '--',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: Colors.white, height: 1),
                ),
                const SizedBox(height: 3),
                Text(
                  date != null ? _mois[date.month - 1] : '',
                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.85)),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  rdv.motif,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: TounColors.navy),
                ),
                const SizedBox(height: 4),
                Text(
                  '${rdv.formateur} · ${rdv.heure}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: TounColors.textSecondary),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: style.couleur.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(style.icone, size: 12, color: style.couleur),
                const SizedBox(width: 4),
                Text(
                  style.libelle,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: style.couleur),
                ),
              ],
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
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _ouvrirFormulaire,
        backgroundColor: TounColors.blue,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Nouveau rendez-vous'),
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Mes rendez-vous',
              sousTitre: 'Échangez avec un formateur TOUN.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: FutureBuilder<DonneesRendezVous>(
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
                          'Impossible de charger vos rendez-vous.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final liste = snapshot.data?.rendezVous ?? [];

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(20, 16, 20, 100),
                      children: [
                        if (liste.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 50, horizontal: 12),
                            child: Column(
                              children: [
                                Container(
                                  width: 60,
                                  height: 60,
                                  decoration: BoxDecoration(
                                    color: TounColors.teal.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(18),
                                  ),
                                  child: const Icon(Icons.calendar_month_rounded, color: TounColors.teal, size: 30),
                                ),
                                const SizedBox(height: 14),
                                const Text(
                                  'Aucun rendez-vous pour le moment.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: TounColors.navy),
                                ),
                                const SizedBox(height: 6),
                                const Text(
                                  'Demandez un rendez-vous pour faire le point avec un formateur.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
                                ),
                              ],
                            ),
                          )
                        else
                          for (final rdv in liste) ...[
                            _carte(rdv),
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
