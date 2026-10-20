import '../models/profil.dart';
import 'api_client.dart';

class ProfilService {
  ProfilService._();

  static final ProfilService instance = ProfilService._();

  final ApiClient _client = ApiClient.instance;

  Future<ProfilDonnees> charger() async {
    final donnees = await _client.get('profil.php');
    return ProfilDonnees.fromJson(donnees);
  }

  Future<List<NotificationTOUN>> notifications() async {
    final donnees = await _client.get('notifications.php');
    final liste = donnees['notifications'] as List<dynamic>? ?? [];
    return liste.map((e) => NotificationTOUN.fromJson(e as Map<String, dynamic>)).toList();
  }
}
