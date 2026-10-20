import 'api_client.dart';

class ContenuLecon {
  final int idLecon;
  final String titre;
  final String module;
  final int idForm;
  final String contenu;
  final String video;
  final String document;
  final String type;
  final String? duree;

  ContenuLecon({
    required this.idLecon,
    required this.titre,
    required this.module,
    required this.idForm,
    required this.contenu,
    required this.video,
    required this.document,
    required this.type,
    required this.duree,
  });

  factory ContenuLecon.fromJson(Map<String, dynamic> json) {
    final l = json['lecon'] as Map<String, dynamic>;

    return ContenuLecon(
      idLecon: l['id_lecon'] as int,
      titre: (l['titre'] ?? '') as String,
      module: (l['module'] ?? '') as String? ?? '',
      idForm: l['id_form'] as int,
      contenu: (l['contenu'] ?? '') as String? ?? '',
      video: (l['video'] ?? '') as String? ?? '',
      document: (l['document'] ?? '') as String? ?? '',
      type: (l['type'] ?? 'texte') as String? ?? 'texte',
      duree: l['duree']?.toString(),
    );
  }
}

class LeconService {
  LeconService._();

  static final LeconService instance = LeconService._();

  final ApiClient _client = ApiClient.instance;

  Future<ContenuLecon> contenu(int idLecon) async {
    final donnees = await _client.get('lecon/contenu.php', parametres: {
      'id': idLecon.toString(),
    });

    return ContenuLecon.fromJson(donnees);
  }

  Future<bool> terminerLecon(int idForm, int idLecon) async {
    final donnees = await _client.post('progression/maj.php', {
      'id_form': idForm,
      'id_lecon': idLecon,
    });

    return donnees['cours_termines'] as bool? ?? false;
  }
}
