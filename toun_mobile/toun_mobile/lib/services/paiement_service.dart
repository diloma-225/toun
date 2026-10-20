import '../models/paiement.dart';
import 'api_client.dart';

class PaiementInitie {
  final String reference;
  final String lienPaiement;
  final double montant;
  final String formation;

  PaiementInitie({
    required this.reference,
    required this.lienPaiement,
    required this.montant,
    required this.formation,
  });

  factory PaiementInitie.fromJson(Map<String, dynamic> json) {
    return PaiementInitie(
      reference: json['reference'] as String,
      lienPaiement: json['lien_paiement'] as String,
      montant: (json['montant'] as num).toDouble(),
      formation: json['formation'] as String,
    );
  }
}

class StatutPaiement {
  final String reference;
  final String statut;
  final String formation;
  final int idForm;
  final double montant;

  StatutPaiement({
    required this.reference,
    required this.statut,
    required this.formation,
    required this.idForm,
    required this.montant,
  });

  bool get estPaye => statut == 'paye';

  factory StatutPaiement.fromJson(Map<String, dynamic> json) {
    return StatutPaiement(
      reference: json['reference'] as String,
      statut: json['statut'] as String,
      formation: (json['formation'] ?? '') as String? ?? '',
      idForm: json['id_form'] as int,
      montant: (json['montant'] as num).toDouble(),
    );
  }
}

class PaiementService {
  PaiementService._();

  static final PaiementService instance = PaiementService._();

  final ApiClient _client = ApiClient.instance;

  Future<PaiementInitie> creer(int idForm, String modePaiement, {String? retourApp}) async {
    final donnees = await _client.post('paiement/creer.php', {
      'id_form': idForm,
      'mode_paiement': modePaiement,
      if (retourApp != null) 'retour_app': retourApp,
    });

    return PaiementInitie.fromJson(donnees);
  }

  Future<StatutPaiement> statut(String reference) async {
    final donnees = await _client.get('paiement/statut.php', parametres: {
      'reference': reference,
    });

    return StatutPaiement.fromJson(donnees);
  }

  Future<HistoriquePaiements> historique() async {
    final donnees = await _client.get('paiements.php');
    return HistoriquePaiements.fromJson(donnees);
  }
}
