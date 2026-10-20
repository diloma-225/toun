class RendezVous {
  final int id;
  final String motif;
  final String formateur;
  final String date;
  final String heure;
  final String statut;

  RendezVous({
    required this.id,
    required this.motif,
    required this.formateur,
    required this.date,
    required this.heure,
    required this.statut,
  });

  factory RendezVous.fromJson(Map<String, dynamic> json) {
    return RendezVous(
      id: (json['id_rdv'] as num?)?.toInt() ?? 0,
      motif: (json['motif'] ?? '') as String,
      formateur: (json['formateur'] ?? '') as String,
      date: (json['date'] ?? '') as String,
      heure: (json['heure'] ?? '') as String,
      statut: (json['statut'] ?? 'en_attente') as String,
    );
  }
}

class Formateur {
  final int id;
  final String nom;

  Formateur({required this.id, required this.nom});

  factory Formateur.fromJson(Map<String, dynamic> json) {
    return Formateur(
      id: (json['id_formateur'] as num?)?.toInt() ?? 0,
      nom: (json['nom'] ?? '') as String,
    );
  }
}

class DonneesRendezVous {
  final List<RendezVous> rendezVous;
  final List<Formateur> formateurs;

  DonneesRendezVous({required this.rendezVous, required this.formateurs});

  factory DonneesRendezVous.fromJson(Map<String, dynamic> json) {
    return DonneesRendezVous(
      rendezVous: (json['rendez_vous'] as List<dynamic>? ?? [])
          .map((e) => RendezVous.fromJson(e as Map<String, dynamic>))
          .toList(),
      formateurs: (json['formateurs'] as List<dynamic>? ?? [])
          .map((e) => Formateur.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
