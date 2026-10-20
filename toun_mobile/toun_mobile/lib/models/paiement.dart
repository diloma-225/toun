class Paiement {
  final String reference;
  final String numeroRecu;
  final String formation;
  final int idForm;
  final double montant;
  final String mode;
  final String statut;
  final String? date;

  Paiement({
    required this.reference,
    required this.numeroRecu,
    required this.formation,
    required this.idForm,
    required this.montant,
    required this.mode,
    required this.statut,
    this.date,
  });

  bool get estPaye => statut == 'paye';

  bool get estEnAttente => statut == 'en_attente';

  factory Paiement.fromJson(Map<String, dynamic> json) {
    return Paiement(
      reference: (json['reference'] ?? '') as String,
      numeroRecu: (json['numero_recu'] ?? '') as String,
      formation: (json['formation'] ?? 'Formation TOUN') as String,
      idForm: (json['id_form'] as num?)?.toInt() ?? 0,
      montant: (json['montant'] as num?)?.toDouble() ?? 0,
      mode: (json['mode'] ?? '') as String,
      statut: (json['statut'] ?? '') as String,
      date: json['date'] as String?,
    );
  }
}

class HistoriquePaiements {
  final List<Paiement> paiements;
  final int total;
  final double totalDepense;
  final int payes;
  final int enAttente;
  final bool collaborateur;

  HistoriquePaiements({
    required this.paiements,
    required this.total,
    required this.totalDepense,
    required this.payes,
    required this.enAttente,
    required this.collaborateur,
  });

  factory HistoriquePaiements.fromJson(Map<String, dynamic> json) {
    final resume = json['resume'] as Map<String, dynamic>? ?? {};
    final liste = json['paiements'] as List<dynamic>? ?? [];

    return HistoriquePaiements(
      paiements: liste.map((e) => Paiement.fromJson(e as Map<String, dynamic>)).toList(),
      total: (resume['total'] as num?)?.toInt() ?? 0,
      totalDepense: (resume['total_depense'] as num?)?.toDouble() ?? 0,
      payes: (resume['payes'] as num?)?.toInt() ?? 0,
      enAttente: (resume['en_attente'] as num?)?.toInt() ?? 0,
      collaborateur: json['collaborateur'] == true,
    );
  }
}
