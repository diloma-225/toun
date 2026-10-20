class FormationEnCours {
  final int idForm;
  final String titre;
  final String image;
  final int progression;

  FormationEnCours({
    required this.idForm,
    required this.titre,
    required this.image,
    required this.progression,
  });

  factory FormationEnCours.fromJson(Map<String, dynamic> json) {
    return FormationEnCours(
      idForm: json['id_form'] as int,
      titre: (json['titre'] ?? '') as String,
      image: (json['image'] ?? '') as String? ?? '',
      progression: json['progression'] as int? ?? 0,
    );
  }
}

class FormationResume {
  final int idForm;
  final String titre;
  final String categorie;
  final String image;
  final int progression;

  FormationResume({
    required this.idForm,
    required this.titre,
    required this.categorie,
    required this.image,
    required this.progression,
  });

  factory FormationResume.fromJson(Map<String, dynamic> json) {
    return FormationResume(
      idForm: (json['id_form'] as num?)?.toInt() ?? 0,
      titre: (json['titre'] ?? '') as String,
      categorie: (json['categorie'] ?? 'Formation') as String? ?? 'Formation',
      image: (json['image'] ?? '') as String? ?? '',
      progression: (json['progression'] as num?)?.toInt() ?? 0,
    );
  }
}

class TableauDeBord {
  final String prenom;
  final String nom;
  final String photo;
  final int totalFormations;
  final int totalFormationsDisponibles;
  final int totalCertificats;
  final int progressionGlobale;
  final FormationEnCours? formationEnCours;
  final List<FormationResume> mesFormations;
  final int tempsApprentissage;
  final int totalCours;

  TableauDeBord({
    required this.prenom,
    required this.nom,
    required this.photo,
    required this.totalFormations,
    required this.totalFormationsDisponibles,
    required this.totalCertificats,
    required this.progressionGlobale,
    required this.formationEnCours,
    required this.mesFormations,
    required this.tempsApprentissage,
    required this.totalCours,
  });

  factory TableauDeBord.fromJson(Map<String, dynamic> json) {
    final u = json['utilisateur'] as Map<String, dynamic>;
    final formationEnCoursJson = json['formation_en_cours'] as Map<String, dynamic>?;

    return TableauDeBord(
      prenom: (u['prenom'] ?? '') as String,
      nom: (u['nom'] ?? '') as String,
      photo: (u['photo'] ?? '') as String? ?? '',
      totalFormations: json['total_formations'] as int? ?? 0,
      totalFormationsDisponibles: json['total_formations_disponibles'] as int? ?? 0,
      totalCertificats: json['total_certificats'] as int? ?? 0,
      progressionGlobale: json['progression_globale'] as int? ?? 0,
      formationEnCours: formationEnCoursJson != null ? FormationEnCours.fromJson(formationEnCoursJson) : null,
      mesFormations: (json['mes_formations'] as List<dynamic>? ?? [])
          .map((f) => FormationResume.fromJson(f as Map<String, dynamic>))
          .toList(),
      tempsApprentissage: (json['temps_apprentissage'] as num?)?.toInt() ?? 0,
      totalCours: (json['total_cours'] as num?)?.toInt() ?? 0,
    );
  }
}
