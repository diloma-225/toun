import '../models/utilisateur.dart';
import 'api_client.dart';

class AuthService {
  AuthService._();

  static final AuthService instance = AuthService._();

  final ApiClient _client = ApiClient.instance;

  Future<Utilisateur> connexion(String email, String motDePasse) async {
    final donnees = await _client.post('auth/login.php', {
      'email': email,
      'mot_de_passe': motDePasse,
    });

    await _client.enregistrerJeton(donnees['jeton'] as String);
    return Utilisateur.fromJson(donnees['utilisateur'] as Map<String, dynamic>);
  }

  Future<Utilisateur> inscription({
    required String nom,
    required String prenom,
    required String email,
    required String telephone,
    required String motDePasse,
    required String confirmation,
  }) async {
    final donnees = await _client.post('auth/inscription.php', {
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'telephone': telephone,
      'mot_de_passe': motDePasse,
      'confirmation': confirmation,
    });

    await _client.enregistrerJeton(donnees['jeton'] as String);
    return Utilisateur.fromJson(donnees['utilisateur'] as Map<String, dynamic>);
  }

  Future<void> deconnexion() async {
    try {
      await _client.post('auth/deconnexion.php', {});
    } catch (_) {
    } finally {
      await _client.effacerJeton();
    }
  }

  Future<void> chargerSession() => _client.chargerJeton();

  bool get estConnecte => _client.estConnecte;
}
