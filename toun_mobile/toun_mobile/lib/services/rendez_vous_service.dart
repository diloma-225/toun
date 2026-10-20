import '../models/rendez_vous.dart';
import 'api_client.dart';

class RendezVousService {
  RendezVousService._();

  static final RendezVousService instance = RendezVousService._();

  final ApiClient _client = ApiClient.instance;

  Future<DonneesRendezVous> charger() async {
    final donnees = await _client.get('rendez_vous.php');
    return DonneesRendezVous.fromJson(donnees);
  }

  Future<DonneesRendezVous> demander({
    required String motif,
    required String date,
    required String heure,
    int idFormateur = 0,
  }) async {
    final donnees = await _client.post('rendez_vous.php', {
      'motif': motif,
      'date_rdv': date,
      'heure_rdv': heure,
      'id_formateur': idFormateur,
    });
    return DonneesRendezVous.fromJson(donnees);
  }
}
