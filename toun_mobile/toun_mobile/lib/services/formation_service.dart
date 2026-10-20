import '../models/formation.dart';
import 'api_client.dart';

class FormationService {
  FormationService._();

  static final FormationService instance = FormationService._();

  final ApiClient _client = ApiClient.instance;

  Future<List<Formation>> liste({String recherche = '', String categorie = ''}) async {
    final donnees = await _client.get('formations/liste.php', parametres: {
      'recherche': recherche,
      'categorie': categorie,
    });

    return (donnees['formations'] as List<dynamic>)
        .map((f) => Formation.fromJson(f as Map<String, dynamic>))
        .toList();
  }

  Future<FormationDetail> detail(int idForm) async {
    final donnees = await _client.get('formations/detail.php', parametres: {
      'id': idForm.toString(),
    });

    return FormationDetail.fromJson(donnees);
  }

  Future<List<MaFormation>> mesFormations() async {
    final donnees = await _client.get('mes_formations.php');

    return (donnees['formations'] as List<dynamic>)
        .map((f) => MaFormation.fromJson(f as Map<String, dynamic>))
        .toList();
  }
}
