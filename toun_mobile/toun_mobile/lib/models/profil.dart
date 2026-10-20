class ProfilDonnees {
  final String nom;
  final String prenom;
  final String email;
  final String telephone;
  final String photo;
  final String? dateInscription;

  ProfilDonnees({
    required this.nom,
    required this.prenom,
    required this.email,
    required this.telephone,
    required this.photo,
    required this.dateInscription,
  });

  String get nomComplet => '$prenom $nom'.trim();

  String get initiales {
    final p = prenom.isNotEmpty ? prenom[0] : '';
    final n = nom.isNotEmpty ? nom[0] : '';
    final resultat = '$p$n'.toUpperCase();
    return resultat.isEmpty ? 'TO' : resultat;
  }

  factory ProfilDonnees.fromJson(Map<String, dynamic> json) {
    return ProfilDonnees(
      nom: (json['nom'] ?? '') as String,
      prenom: (json['prenom'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      telephone: (json['telephone'] ?? '') as String? ?? '',
      photo: (json['photo'] ?? '') as String? ?? '',
      dateInscription: json['date_inscription'] as String?,
    );
  }
}

class NotificationTOUN {
  final String type;
  final String titre;
  final String texte;
  final String? date;

  NotificationTOUN({
    required this.type,
    required this.titre,
    required this.texte,
    required this.date,
  });

  factory NotificationTOUN.fromJson(Map<String, dynamic> json) {
    return NotificationTOUN(
      type: (json['type'] ?? '') as String,
      titre: (json['titre'] ?? '') as String,
      texte: (json['texte'] ?? '') as String,
      date: json['date'] as String?,
    );
  }
}
