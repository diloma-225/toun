import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/evaluation_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class QuizScreen extends StatefulWidget {
  final int idQuiz;

  const QuizScreen({super.key, required this.idQuiz});

  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> {
  late Future<QuizEvaluation> _futur;
  final Map<int, int> _choix = {};
  ResultatQuiz? _resultat;
  bool _envoi = false;

  @override
  void initState() {
    super.initState();
    _futur = EvaluationService.instance.charger(widget.idQuiz);
  }

  String _note(double note) {
    return note == note.roundToDouble() ? note.toStringAsFixed(0) : note.toStringAsFixed(2).replaceAll('.', ',');
  }

  Future<void> _valider(QuizEvaluation quiz) async {
    setState(() => _envoi = true);

    try {
      final resultat = await EvaluationService.instance.envoyer(quiz.idQuiz, _choix);

      if (mounted) {
        setState(() => _resultat = resultat);
      }
    } catch (erreur) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$erreur')));
      }
    } finally {
      if (mounted) {
        setState(() => _envoi = false);
      }
    }
  }

  void _recommencer() {
    setState(() {
      _resultat = null;
      _choix.clear();
      _futur = EvaluationService.instance.charger(widget.idQuiz);
    });
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
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Évaluation finale',
              onRetour: () => Navigator.of(context).pop(_resultat?.reussi ?? false),
            ),
            Expanded(
              child: FutureBuilder<QuizEvaluation>(
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
                          '${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: TounColors.textSecondary),
                        ),
                      ),
                    );
                  }

                  final quiz = snapshot.data!;

                  return _resultat == null ? _questionnaire(quiz) : _ecranResultat(quiz, _resultat!);
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _questionnaire(QuizEvaluation quiz) {
    final complet = _choix.length == quiz.questions.length;

    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
            children: [
              Text(quiz.titre, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: TounColors.navy)),
              const SizedBox(height: 6),
              Text(
                '${quiz.questions.length} questions · note minimale ${_note(quiz.noteMinimale)}/20',
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: TounColors.teal),
              ),
              if (quiz.description.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(quiz.description, style: const TextStyle(fontSize: 12.5, color: TounColors.textSecondary, height: 1.5)),
              ],
              const SizedBox(height: 16),
              for (var i = 0; i < quiz.questions.length; i++) ...[
                _carteQuestion(i + 1, quiz.questions[i]),
                const SizedBox(height: 12),
              ],
            ],
          ),
        ),
        Container(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
          color: Colors.white,
          child: SafeArea(
            top: false,
            child: SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: complet && !_envoi ? () => _valider(quiz) : null,
                child: Text(
                  _envoi
                      ? 'Correction en cours...'
                      : complet
                          ? 'Valider mes réponses'
                          : 'Répondez à toutes les questions (${_choix.length}/${quiz.questions.length})',
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _carteQuestion(int numero, QuestionQuiz question) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: TounColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Question $numero', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: TounColors.teal)),
          const SizedBox(height: 4),
          Text(question.libelle, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: TounColors.navy, height: 1.4)),
          const SizedBox(height: 10),
          ...question.reponses.map((reponse) {
            final choisie = _choix[question.idQuestion] == reponse.idReponse;

            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: InkWell(
                onTap: () => setState(() => _choix[question.idQuestion] = reponse.idReponse),
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: choisie ? TounColors.teal.withValues(alpha: 0.08) : TounColors.background,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: choisie ? TounColors.teal : Colors.transparent),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        choisie ? Icons.radio_button_checked_rounded : Icons.radio_button_unchecked_rounded,
                        size: 18,
                        color: choisie ? TounColors.teal : TounColors.gray,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(reponse.libelle, style: const TextStyle(fontSize: 12.5, color: TounColors.textPrimary, height: 1.4)),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _ecranResultat(QuizEvaluation quiz, ResultatQuiz resultat) {
    final couleur = resultat.reussi ? TounColors.green : const Color(0xFFC0392B);

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 24),
      children: [
        Center(
          child: Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              border: Border.all(color: couleur, width: 6),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(_note(resultat.note), style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: couleur)),
                const Text('/ 20', style: TextStyle(fontSize: 12, color: TounColors.textSecondary)),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        Text(
          resultat.reussi ? (resultat.type == 'module' ? 'Test réussi' : 'Évaluation réussie') : (resultat.type == 'module' ? 'Test non validé' : 'Évaluation non validée'),
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: couleur),
        ),
        const SizedBox(height: 6),
        Text(
          resultat.reussi
              ? (resultat.type == 'module'
                  ? '${resultat.bonnes} bonnes réponses sur ${resultat.total}. Le module suivant est débloqué.'
                  : '${resultat.bonnes} bonnes réponses sur ${resultat.total}. Votre formation est terminée et votre certificat est prêt.')
              : '${resultat.bonnes} bonnes réponses sur ${resultat.total}. Il faut au moins ${_note(resultat.noteMinimale)}/20 : revoyez les leçons puis repassez ${resultat.type == 'module' ? 'le test' : 'l\'évaluation'}.',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 12.5, color: TounColors.textSecondary, height: 1.5),
        ),
        const SizedBox(height: 18),
        SizedBox(
          height: 48,
          child: resultat.reussi && resultat.certificat.isNotEmpty && resultat.type != 'module'
              ? ElevatedButton.icon(
                  onPressed: () => _ouvrirCertificat(resultat.certificat),
                  icon: const Icon(Icons.workspace_premium_rounded, size: 20),
                  label: const Text('Voir et télécharger mon certificat'),
                )
              : ElevatedButton.icon(
                  onPressed: resultat.reussi ? () => Navigator.of(context).pop(true) : _recommencer,
                  icon: Icon(resultat.reussi ? Icons.check_rounded : Icons.refresh_rounded, size: 20),
                  label: Text(resultat.reussi ? (resultat.type == 'module' ? 'Continuer la formation' : 'Terminer') : (resultat.type == 'module' ? 'Repasser le test' : 'Repasser l\'évaluation')),
                ),
        ),
        const SizedBox(height: 24),
        const Text('Correction', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: TounColors.navy)),
        const SizedBox(height: 10),
        for (var i = 0; i < resultat.corrections.length; i++) ...[
          _carteCorrection(i + 1, resultat.corrections[i]),
          const SizedBox(height: 10),
        ],
      ],
    );
  }

  Widget _carteCorrection(int numero, CorrectionQuestion correction) {
    return Container(
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
              Icon(
                correction.juste ? Icons.check_circle_rounded : Icons.cancel_rounded,
                size: 18,
                color: correction.juste ? TounColors.green : const Color(0xFFC0392B),
              ),
              const SizedBox(width: 8),
              Text('Question $numero', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: TounColors.textSecondary)),
            ],
          ),
          const SizedBox(height: 6),
          Text(correction.question, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: TounColors.navy, height: 1.4)),
          const SizedBox(height: 6),
          Text('Votre réponse : ${correction.choisie}', style: const TextStyle(fontSize: 12, color: TounColors.textSecondary, height: 1.4)),
          if (!correction.juste)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text('Bonne réponse : ${correction.bonne}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: TounColors.green, height: 1.4)),
            ),
        ],
      ),
    );
  }
}
