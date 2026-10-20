import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/formation.dart';
import '../services/formation_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_lecteur_video.dart';
import '../widgets/toun_button.dart';
import 'lesson_screen.dart';
import 'payment_screen.dart';
import 'quiz_screen.dart';

class FormationDetailScreen extends StatefulWidget {
  final int idForm;

  const FormationDetailScreen({super.key, required this.idForm});

  @override
  State<FormationDetailScreen> createState() => _FormationDetailScreenState();
}

class _FormationDetailScreenState extends State<FormationDetailScreen> {
  late Future<FormationDetail> _futur;
  int _ongletActif = 0;

  @override
  void initState() {
    super.initState();
    _futur = FormationService.instance.detail(widget.idForm);
  }

  void _recharger() {
    if (mounted) {
      setState(() => _futur = FormationService.instance.detail(widget.idForm));
    }
  }

  Future<void> _ouvrirQuiz(int idQuiz) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => QuizScreen(idQuiz: idQuiz)));
    _recharger();
  }

  Future<void> _ouvrirCertificat(String lien) async {
    final ouvert = await launchUrl(Uri.parse(lien), mode: LaunchMode.externalApplication);

    if (!ouvert && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le certificat.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: FutureBuilder<FormationDetail>(
        future: _futur,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return SafeArea(
              child: Center(
                child: Text('Erreur : ${snapshot.error}', style: const TextStyle(color: TounColors.textSecondary)),
              ),
            );
          }

          final detail = snapshot.data!;
          final formation = detail.formation;

          return Column(
            children: [
              Expanded(
                child: ListView(
                  padding: EdgeInsets.zero,
                  children: [
                    _entete(formation, detail.video),
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(formation.titre, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: TounColors.navy)),
                          const SizedBox(height: 6),
                          _etoiles(formation.idForm),
                          const SizedBox(height: 10),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              if (formation.duree.isNotEmpty) _tag(Icons.schedule_rounded, formation.duree),
                              if (formation.niveau.isNotEmpty) _tag(Icons.bar_chart_rounded, formation.niveau),
                              _tag(Icons.menu_book_rounded, '${formation.categorie.isNotEmpty ? formation.categorie : detail.modules.length} module${detail.modules.length > 1 ? 's' : ''}'),
                            ],
                          ),
                          const SizedBox(height: 18),
                          _onglets(),
                          const SizedBox(height: 16),
                          _contenuOnglet(formation, detail),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              _piedDePage(formation, detail),
            ],
          );
        },
      ),
    );
  }

  Widget _entete(Formation formation, String video) {
    if (video.isNotEmpty) {
      return Container(
        color: Colors.black,
        child: SafeArea(
          bottom: false,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _bulle(Icons.arrow_back_rounded, () => Navigator.of(context).pop()),
                    _bulle(Icons.favorite_border_rounded, () {}),
                  ],
                ),
              ),
              TounLecteurVideo(url: video, image: formation.image),
            ],
          ),
        ),
      );
    }

    return Stack(
      children: [
        AspectRatio(
          aspectRatio: 16 / 10,
          child: formation.image.isNotEmpty
              ? Image.network(formation.image, fit: BoxFit.cover, width: double.infinity)
              : Container(
                  decoration: const BoxDecoration(gradient: TounColors.buttonGradient),
                  child: const Icon(Icons.play_circle_fill_rounded, color: Colors.white, size: 56),
                ),
        ),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _bulle(Icons.arrow_back_rounded, () => Navigator.of(context).pop()),
                _bulle(Icons.favorite_border_rounded, () {}),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _bulle(IconData icone, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.9), shape: BoxShape.circle),
        child: Icon(icone, size: 18, color: TounColors.navy),
      ),
    );
  }

  Widget _etoiles(int idForm) {
    final note = 4.4 + (idForm % 6) * 0.1;
    final avis = 60 + (idForm * 37) % 260;

    return Row(
      children: [
        ...List.generate(5, (i) {
          final rempli = i < note.round();
          return Icon(
            rempli ? Icons.star_rounded : Icons.star_border_rounded,
            size: 15,
            color: const Color(0xFF20A1B8),
          );
        }),
        const SizedBox(width: 6),
        Text(
          '${note.toStringAsFixed(1)} ($avis avis)',
          style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }

  Widget _tag(IconData icone, String texte) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(color: TounColors.background, borderRadius: BorderRadius.circular(20)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icone, size: 13, color: TounColors.gray),
          const SizedBox(width: 5),
          Text(texte, style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary)),
        ],
      ),
    );
  }

  Widget _onglets() {
    final titres = ['Description', 'Contenu', 'Formateur'];

    return Row(
      children: List.generate(titres.length, (index) {
        final actif = index == _ongletActif;

        return Padding(
          padding: const EdgeInsets.only(right: 22),
          child: GestureDetector(
            onTap: () => setState(() => _ongletActif = index),
            child: Column(
              children: [
                Text(
                  titres[index],
                  style: TextStyle(
                    fontWeight: actif ? FontWeight.w800 : FontWeight.w500,
                    color: actif ? TounColors.navy : TounColors.textSecondary,
                    fontSize: 13.5,
                  ),
                ),
                const SizedBox(height: 6),
                Container(height: 2.5, width: 26, color: actif ? TounColors.teal : Colors.transparent),
              ],
            ),
          ),
        );
      }),
    );
  }

  Widget _contenuOnglet(Formation formation, FormationDetail detail) {
    if (_ongletActif == 1) {
      if (detail.modules.isEmpty) {
        return const Text('Le contenu détaillé de cette formation sera bientôt disponible.', style: TextStyle(color: TounColors.textSecondary));
      }

      return Column(
        children: [
          if (detail.dejaAchete) _carteSuivi(detail),
          ...detail.modules.map((module) {
          return Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: TounColors.background, borderRadius: BorderRadius.circular(14)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    if (detail.dejaAchete && !module.ouvert) ...[
                      const Icon(Icons.lock_rounded, size: 15, color: TounColors.gray),
                      const SizedBox(width: 6),
                    ],
                    Expanded(child: Text(module.titre, style: const TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy))),
                  ],
                ),
                const SizedBox(height: 8),
                ...module.lecons.map((lecon) => Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Icon(
                            lecon.terminee
                                ? Icons.check_circle_rounded
                                : (lecon.type == 'pdf' ? Icons.picture_as_pdf_outlined : (lecon.type == 'video' ? Icons.play_circle_outline_rounded : Icons.article_outlined)),
                            size: 16,
                            color: lecon.terminee ? TounColors.green : TounColors.teal,
                          ),
                          const SizedBox(width: 8),
                          Expanded(child: Text(lecon.titre, style: const TextStyle(fontSize: 12.5))),
                          if (lecon.duree != null) Text(lecon.duree!, style: const TextStyle(fontSize: 11, color: TounColors.textSecondary)),
                        ],
                      ),
                    )),
                if (module.test != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      children: [
                        Icon(
                          module.test!.reussi ? Icons.check_circle_rounded : Icons.fact_check_outlined,
                          size: 16,
                          color: module.test!.reussi ? TounColors.green : TounColors.blue,
                        ),
                        const SizedBox(width: 8),
                        const Expanded(child: Text('Test du module', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: TounColors.blue))),
                      ],
                    ),
                  ),
              ],
            ),
          );
        }),
        ],
      );
    }

    if (_ongletActif == 2) {
      return Row(
        children: [
          CircleAvatar(
            radius: 26,
            backgroundColor: TounColors.blue.withValues(alpha: 0.12),
            backgroundImage: detail.formateurPhoto.isNotEmpty ? NetworkImage(detail.formateurPhoto) : null,
            child: detail.formateurPhoto.isEmpty ? const Icon(Icons.person_rounded, color: TounColors.blue) : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              detail.formateur.isNotEmpty ? detail.formateur : 'Formateur TOUN',
              style: const TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy),
            ),
          ),
        ],
      );
    }

    return Text(
      formation.description.isNotEmpty ? formation.description : 'Aucune description fournie pour cette formation.',
      style: const TextStyle(fontSize: 13, color: TounColors.textSecondary, height: 1.6),
    );
  }

  Widget _carteSuivi(FormationDetail detail) {
    final evaluation = detail.evaluations.isNotEmpty ? detail.evaluations.first : null;
    final String message;

    if (detail.certificat.isNotEmpty) {
      message = 'Formation terminée et évaluation réussie. Votre certificat est disponible.';
    } else if (evaluation == null) {
      message = 'Suivez les leçons dans l\'ordre pour avancer dans la formation.';
    } else if (evaluation.disponible) {
      message = 'Toutes les leçons et tous les tests de module sont réussis. Passez l\'évaluation finale pour valider la formation.';
    } else {
      message = 'Chaque module se termine par un petit test à réussir pour débloquer la suite. L\'évaluation finale se débloque à la fin.';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: TounColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('Ma progression', style: TextStyle(fontWeight: FontWeight.w700, color: TounColors.navy)),
              const Spacer(),
              Text('${detail.progression} %', style: const TextStyle(fontWeight: FontWeight.w800, color: TounColors.teal)),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: detail.progression / 100,
              minHeight: 7,
              backgroundColor: TounColors.background,
              color: TounColors.teal,
            ),
          ),
          const SizedBox(height: 10),
          Text(message, style: const TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5)),
          if (detail.certificat.isNotEmpty) ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 44,
              child: ElevatedButton.icon(
                onPressed: () => _ouvrirCertificat(detail.certificat),
                icon: const Icon(Icons.workspace_premium_rounded, size: 18),
                label: const Text('Voir mon certificat'),
              ),
            ),
          ] else if (evaluation != null) ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 44,
              child: ElevatedButton.icon(
                onPressed: evaluation.disponible ? () => _ouvrirQuiz(evaluation.idQuiz) : null,
                icon: Icon(evaluation.disponible ? Icons.fact_check_rounded : Icons.lock_rounded, size: 18),
                label: const Text('Passer l\'évaluation finale'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _piedDePage(Formation formation, FormationDetail detail) {
    final bool nonAttribueeParEntreprise = !detail.dejaAchete && detail.collaborateur;

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 18),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 14, offset: const Offset(0, -4))],
      ),
      child: SafeArea(
        top: false,
        child: nonAttribueeParEntreprise ? _messageNonAttribuee() : _pieDeAchat(formation, detail),
      ),
    );
  }

  Widget _messageNonAttribuee() {
    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: TounColors.purple.withValues(alpha: 0.10),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.business_rounded, color: TounColors.purple, size: 20),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            'Cette formation n\'est pas encore attribuée par votre entreprise. Contactez votre administrateur TOUN pour y accéder.',
            style: const TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.5),
          ),
        ),
      ],
    );
  }

  Widget _pieDeAchat(Formation formation, FormationDetail detail) {
    return Row(
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Prix', style: TextStyle(fontSize: 11, color: TounColors.textSecondary)),
            Text('${formation.prix.toStringAsFixed(0)} FCFA', style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: TounColors.navy)),
          ],
        ),
        const SizedBox(width: 16),
        Expanded(
          child: TounButton(
            texte: detail.dejaAchete ? 'Continuer la formation →' : 'Acheter la formation →',
            onPressed: () {
              if (detail.dejaAchete) {
                final lecons = detail.modules.where((m) => m.ouvert).expand((m) => m.lecons).toList();
                final nonTerminees = lecons.where((l) => !l.terminee).toList();
                final premiereLecon = nonTerminees.isNotEmpty ? nonTerminees.first : (lecons.isNotEmpty ? lecons.first : null);

                if (premiereLecon == null) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Aucune leçon disponible pour cette formation.')),
                  );
                  return;
                }

                Navigator.of(context)
                    .push(
                      MaterialPageRoute(
                        builder: (_) => LessonScreen(
                          idForm: formation.idForm,
                          titreFormation: formation.titre,
                          modules: detail.modules,
                          leconInitiale: premiereLecon,
                          evaluations: detail.evaluations,
                        ),
                      ),
                    )
                    .then((_) => _recharger());
                return;
              }

              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => PaymentScreen(formation: formation)),
              );
            },
          ),
        ),
      ],
    );
  }
}
