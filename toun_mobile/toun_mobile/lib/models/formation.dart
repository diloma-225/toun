class Lecon {
  final int idLecon;
  final String titre;
  final String? duree;
  final String type;
  final bool terminee;

  Lecon({required this.idLecon, required this.titre, this.duree, this.type = 'texte', this.terminee = false});

  factory Lecon.fromJson(Map<String, dynamic> json) {
    return Lecon(
      idLecon: json['id_lecon'] as int,
      titre: (json['titre'] ?? '') as String,
      duree: json['duree']?.toString(),
      type: (json['type'] ?? 'texte') as String,
      terminee: json['terminee'] as bool? ?? false,
    );
  }
}

class EvaluationFormation {
  final int idQuiz;
  final String titre;
  final bool reussi;
  final bool disponible;

  EvaluationFormation({required this.idQuiz, required this.titre, required this.reussi, required this.disponible});

  factory EvaluationFormation.fromJson(Map<String, dynamic> json) {
    return EvaluationFormation(
      idQuiz: json['id_quiz'] as int,
      titre: (json['titre'] ?? '') as String,
      reussi: json['reussi'] as bool? ?? false,
      disponible: json['disponible'] as bool? ?? false,
    );
  }
}

class RessourceCours {
  final String titre;
  final String type;
  final String lien;

  RessourceCours({required this.titre, required this.type, required this.lien});

  factory RessourceCours.fromJson(Map<String, dynamic> json) {
    return RessourceCours(
      titre: (json['titre'] ?? '') as String,
      type: (json['type'] ?? 'autre') as String,
      lien: (json['lien'] ?? '') as String,
    );
  }
}

class ModuleFormation {
  final int idModule;
  final String titre;
  final bool ouvert;
  final List<Lecon> lecons;
  final List<RessourceCours> ressources;
  final EvaluationFormation? test;

  ModuleFormation({
    required this.idModule,
    required this.titre,
    this.ouvert = true,
    required this.lecons,
    this.ressources = const [],
    this.test,
  });

  factory ModuleFormation.fromJson(Map<String, dynamic> json) {
    return ModuleFormation(
      idModule: json['id_module'] as int,
      titre: (json['titre'] ?? '') as String,
      ouvert: json['ouvert'] as bool? ?? true,
      lecons: (json['lecons'] as List<dynamic>? ?? [])
          .map((l) => Lecon.fromJson(l as Map<String, dynamic>))
          .toList(),
      ressources: (json['ressources'] as List<dynamic>? ?? [])
          .map((r) => RessourceCours.fromJson(r as Map<String, dynamic>))
          .toList(),
      test: json['test'] is Map<String, dynamic> ? EvaluationFormation.fromJson(json['test'] as Map<String, dynamic>) : null,
    );
  }
}

class Formation {
  final int idForm;
  final String titre;
  final String description;
  final String image;
  final String niveau;
  final String duree;
  final double prix;
  final String categorie;
  final String formateur;

  Formation({
    required this.idForm,
    required this.titre,
    required this.description,
    required this.image,
    required this.niveau,
    required this.duree,
    required this.prix,
    required this.categorie,
    this.formateur = '',
  });

  factory Formation.fromJson(Map<String, dynamic> json) {
    return Formation(
      idForm: json['id_form'] as int,
      titre: (json['titre'] ?? '') as String,
      description: (json['description'] ?? '') as String? ?? '',
      image: (json['image'] ?? '') as String? ?? '',
      niveau: (json['niveau'] ?? '') as String? ?? '',
      duree: (json['duree'] ?? '') as String? ?? '',
      prix: (json['prix'] as num?)?.toDouble() ?? 0,
      categorie: (json['categorie'] ?? '') as String? ?? '',
      formateur: (json['formateur'] ?? '') as String? ?? '',
    );
  }
}

class FormationDetail {
  final Formation formation;
  final String formateur;
  final String formateurPhoto;
  final List<ModuleFormation> modules;
  final bool dejaAchete;
  final bool collaborateur;
  final String video;
  final int progression;
  final bool coursTermines;
  final List<EvaluationFormation> evaluations;
  final String certificat;

  FormationDetail({
    required this.formation,
    required this.formateur,
    required this.formateurPhoto,
    required this.modules,
    required this.dejaAchete,
    this.collaborateur = false,
    this.video = '',
    this.progression = 0,
    this.coursTermines = false,
    this.evaluations = const [],
    this.certificat = '',
  });

  factory FormationDetail.fromJson(Map<String, dynamic> json) {
    final f = json['formation'] as Map<String, dynamic>;

    return FormationDetail(
      formation: Formation(
        idForm: f['id_form'] as int,
        titre: (f['titre'] ?? '') as String,
        description: (f['description'] ?? '') as String? ?? '',
        image: (f['image'] ?? '') as String? ?? '',
        niveau: (f['niveau'] ?? '') as String? ?? '',
        duree: (f['duree'] ?? '') as String? ?? '',
        prix: (f['prix'] as num?)?.toDouble() ?? 0,
        categorie: (f['categorie'] ?? '') as String? ?? '',
      ),
      formateur: (f['formateur'] ?? '') as String? ?? '',
      formateurPhoto: (f['formateur_photo'] ?? '') as String? ?? '',
      modules: (json['modules'] as List<dynamic>? ?? [])
          .map((m) => ModuleFormation.fromJson(m as Map<String, dynamic>))
          .toList(),
      dejaAchete: json['deja_achete'] as bool? ?? false,
      collaborateur: json['collaborateur'] as bool? ?? false,
      video: (f['video'] ?? '') as String? ?? '',
      progression: json['progression'] as int? ?? 0,
      coursTermines: json['cours_termines'] as bool? ?? false,
      evaluations: (json['evaluations'] as List<dynamic>? ?? [])
          .map((e) => EvaluationFormation.fromJson(e as Map<String, dynamic>))
          .toList(),
      certificat: (json['certificat'] ?? '') as String,
    );
  }
}

class MaFormation {
  final int idForm;
  final String titre;
  final String image;
  final String duree;
  final String categorie;
  final int progression;
  final String statut;

  MaFormation({
    required this.idForm,
    required this.titre,
    required this.image,
    required this.duree,
    required this.categorie,
    required this.progression,
    required this.statut,
  });

  factory MaFormation.fromJson(Map<String, dynamic> json) {
    return MaFormation(
      idForm: json['id_form'] as int,
      titre: (json['titre'] ?? '') as String,
      image: (json['image'] ?? '') as String? ?? '',
      duree: (json['duree'] ?? '') as String? ?? '',
      categorie: (json['categorie'] ?? '') as String? ?? '',
      progression: json['progression'] as int? ?? 0,
      statut: (json['statut'] ?? '') as String? ?? '',
    );
  }
}
