import 'package:flutter/material.dart';

import '../services/evaluation_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';
import 'quiz_screen.dart';

class EvaluationsScreen extends StatefulWidget {
  const EvaluationsScreen({super.key});

  @override
  State<EvaluationsScreen> createState() => _EvaluationsScreenState();
}

class _EvaluationsScreenState extends State<EvaluationsScreen> {
  late Future<List<ResumeEvaluation>> _futur;

  @override
  void initState() {
    super.initState();
    _futur = EvaluationService.instance.liste();
  }

  Future<void> _actualiser() async {
    setState(() => _futur = EvaluationService.instance.liste());
    await _futur;
  }

  Future<void> _ouvrir(ResumeEvaluation evaluation) async {
    if (!evaluation.disponible) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(evaluation.type == 'module'
              ? 'Terminez les leçons de ce module pour débloquer son test.'
              : 'Terminez tous les modules et leurs tests pour débloquer l\'évaluation finale.'),
        ),
      );
      return;
    }

    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => QuizScreen(idQuiz: evaluation.idQuiz)));

    if (mounted) {
      _actualiser();
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
              titre: 'Mes évaluations',
              sousTitre: 'Un test par module, puis l\'évaluation finale qui débloque le certificat.',
              onRetour: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: FutureBuilder<List<ResumeEvaluation>>(
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

                  final evaluations = snapshot.data ?? [];

                  return RefreshIndicator(
                    onRefresh: _actualiser,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                      children: [
                        if (evaluations.isEmpty)
                          const Padding(
                            padding: EdgeInsets.symmetric(vertical: 50),
                            child: Text(
                              'Aucune évaluation pour le moment. Elles apparaissent ici dès que vous êtes inscrit à une formation.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12.5, color: TounColors.textSecondary, height: 1.5),
                            ),
                          )
                        else
                          for (final evaluation in evaluations) ...[
                            _carte(evaluation),
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

  Widget _carte(ResumeEvaluation evaluation) {
    final String etat;
    final Color couleur;
    final IconData icone;

    if (evaluation.reussi) {
      etat = 'Réussie';
      couleur = TounColors.green;
      icone = Icons.verified_rounded;
    } else if (evaluation.disponible) {
      etat = evaluation.derniereNote != null ? 'À repasser' : 'À passer';
      couleur = TounColors.teal;
      icone = Icons.fact_check_rounded;
    } else {
      etat = 'Verrouillée';
      couleur = TounColors.gray;
      icone = Icons.lock_rounded;
    }

    return InkWell(
      onTap: () => _ouvrir(evaluation),
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
              decoration: BoxDecoration(color: couleur.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(14)),
              child: Icon(icone, color: couleur),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(evaluation.titre, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: TounColors.navy)),
                  const SizedBox(height: 3),
                  Text(evaluation.formation, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: TounColors.blue)),
                  const SizedBox(height: 2),
                  Text(
                    evaluation.derniereNote != null
                        ? 'Dernière note : ${evaluation.derniereNote!.toStringAsFixed(evaluation.derniereNote! % 1 == 0 ? 0 : 2)}/20'
                        : (evaluation.disponible ? 'Prête à être passée' : (evaluation.type == 'module' ? 'Terminez d\'abord les leçons du module' : 'Terminez d\'abord tous les modules')),
                    style: const TextStyle(fontSize: 11.5, color: TounColors.textSecondary),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
              decoration: BoxDecoration(color: couleur.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
              child: Text(etat, style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: couleur)),
            ),
          ],
        ),
      ),
    );
  }
}
