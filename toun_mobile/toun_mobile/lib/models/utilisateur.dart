class Utilisateur {
  final int idUser;
  final String nom;
  final String prenom;
  final String email;
  final String telephone;
  final String photo;

  Utilisateur({
    required this.idUser,
    required this.nom,
    required this.prenom,
    required this.email,
    required this.telephone,
    required this.photo,
  });

  String get nomComplet => '$prenom $nom'.trim();

  String get initiales {
    final p = prenom.isNotEmpty ? prenom[0] : '';
    final n = nom.isNotEmpty ? nom[0] : '';
    final resultat = '$p$n'.toUpperCase();
    return resultat.isEmpty ? 'TO' : resultat;
  }

  factory Utilisateur.fromJson(Map<String, dynamic> json) {
    return Utilisateur(
      idUser: json['id_user'] as int,
      nom: (json['nom'] ?? '') as String,
      prenom: (json['prenom'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      telephone: (json['telephone'] ?? '') as String? ?? '',
      photo: (json['photo'] ?? '') as String? ?? '',
    );
  }
}
