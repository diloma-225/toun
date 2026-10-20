import 'api_client.dart';

class ResumeEvaluation {
  final int idQuiz;
  final String titre;
  final String type;
  final int idForm;
  final String formation;
  final bool disponible;
  final bool reussi;
  final double? derniereNote;

  ResumeEvaluation({
    required this.idQuiz,
    required this.titre,
    this.type = 'final',
    required this.idForm,
    required this.formation,
    required this.disponible,
    required this.reussi,
    this.derniereNote,
  });

  factory ResumeEvaluation.fromJson(Map<String, dynamic> json) {
    return ResumeEvaluation(
      idQuiz: json['id_quiz'] as int,
      titre: (json['titre'] ?? '') as String,
      type: (json['type'] ?? 'final') as String,
      idForm: json['id_form'] as int? ?? 0,
      formation: (json['formation'] ?? '') as String,
      disponible: json['disponible'] as bool? ?? false,
      reussi: json['reussi'] as bool? ?? false,
      derniereNote: (json['derniere_note'] as num?)?.toDouble(),
    );
  }
}

class ReponseQuiz {
  final int idReponse;
  final String libelle;

  ReponseQuiz({required this.idReponse, required this.libelle});

  factory ReponseQuiz.fromJson(Map<String, dynamic> json) {
    return ReponseQuiz(
      idReponse: json['id_reponse'] as int,
      libelle: (json['libelle'] ?? '') as String,
    );
  }
}

class QuestionQuiz {
  final int idQuestion;
  final String libelle;
  final List<ReponseQuiz> reponses;

  QuestionQuiz({required this.idQuestion, required this.libelle, required this.reponses});

  factory QuestionQuiz.fromJson(Map<String, dynamic> json) {
    return QuestionQuiz(
      idQuestion: json['id_question'] as int,
      libelle: (json['libelle'] ?? '') as String,
      reponses: (json['reponses'] as List<dynamic>? ?? [])
          .map((r) => ReponseQuiz.fromJson(r as Map<String, dynamic>))
          .toList(),
    );
  }
}

class QuizEvaluation {
  final int idQuiz;
  final String titre;
  final String description;
  final String formation;
  final double noteMinimale;
  final List<QuestionQuiz> questions;

  QuizEvaluation({
    required this.idQuiz,
    required this.titre,
    required this.description,
    required this.formation,
    required this.noteMinimale,
    required this.questions,
  });

  factory QuizEvaluation.fromJson(Map<String, dynamic> json) {
    final q = json['quiz'] as Map<String, dynamic>;

    return QuizEvaluation(
      idQuiz: q['id_quiz'] as int,
      titre: (q['titre'] ?? '') as String,
      description: (q['description'] ?? '') as String,
      formation: (q['formation'] ?? '') as String,
      noteMinimale: (q['note_minimale'] as num?)?.toDouble() ?? 10,
      questions: (json['questions'] as List<dynamic>? ?? [])
          .map((e) => QuestionQuiz.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class CorrectionQuestion {
  final String question;
  final String choisie;
  final String bonne;
  final bool juste;

  CorrectionQuestion({required this.question, required this.choisie, required this.bonne, required this.juste});

  factory CorrectionQuestion.fromJson(Map<String, dynamic> json) {
    return CorrectionQuestion(
      question: (json['question'] ?? '') as String,
      choisie: (json['choisie'] ?? '') as String,
      bonne: (json['bonne'] ?? '') as String,
      juste: json['juste'] as bool? ?? false,
    );
  }
}

class ResultatQuiz {
  final String type;
  final double note;
  final double noteMinimale;
  final int bonnes;
  final int total;
  final bool reussi;
  final String certificat;
  final List<CorrectionQuestion> corrections;

  ResultatQuiz({
    this.type = 'final',
    required this.note,
    required this.noteMinimale,
    required this.bonnes,
    required this.total,
    required this.reussi,
    required this.certificat,
    required this.corrections,
  });

  factory ResultatQuiz.fromJson(Map<String, dynamic> json) {
    return ResultatQuiz(
      type: (json['type'] ?? 'final') as String,
      note: (json['note'] as num?)?.toDouble() ?? 0,
      noteMinimale: (json['note_minimale'] as num?)?.toDouble() ?? 10,
      bonnes: json['bonnes'] as int? ?? 0,
      total: json['total'] as int? ?? 0,
      reussi: json['reussi'] as bool? ?? false,
      certificat: (json['certificat'] ?? '') as String,
      corrections: (json['corrections'] as List<dynamic>? ?? [])
          .map((c) => CorrectionQuestion.fromJson(c as Map<String, dynamic>))
          .toList(),
    );
  }
}

class EvaluationService {
  EvaluationService._();

  static final EvaluationService instance = EvaluationService._();

  final ApiClient _client = ApiClient.instance;

  Future<List<ResumeEvaluation>> liste() async {
    final donnees = await _client.get('evaluations.php');

    return (donnees['evaluations'] as List<dynamic>? ?? [])
        .map((e) => ResumeEvaluation.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<QuizEvaluation> charger(int idQuiz) async {
    final donnees = await _client.get('quiz.php', parametres: {'id': idQuiz.toString()});

    return QuizEvaluation.fromJson(donnees);
  }

  Future<ResultatQuiz> envoyer(int idQuiz, Map<int, int> reponses) async {
    final donnees = await _client.post('quiz.php', {
      'id_quiz': idQuiz,
      'reponses': reponses.map((question, reponse) => MapEntry(question.toString(), reponse)),
    });

    return ResultatQuiz.fromJson(donnees);
  }
}
