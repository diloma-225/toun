import 'dart:convert';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiException implements Exception {
  final String message;
  final int statusCode;

  ApiException(this.message, this.statusCode);

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient._();

  static final ApiClient instance = ApiClient._();

  static String get baseUrl {
    if (kIsWeb) {
      return 'http://localhost/application%20toun/api';
    }

    if (Platform.isAndroid) {
      return 'http://localhost:8080/application%20toun/api';
    }

    return 'http://localhost/application%20toun/api';
  }

  String? _jeton;

  Future<void> chargerJeton() async {
    final prefs = await SharedPreferences.getInstance();
    _jeton = prefs.getString('jeton_toun');
  }

  Future<void> enregistrerJeton(String jeton) async {
    _jeton = jeton;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('jeton_toun', jeton);
  }

  Future<void> effacerJeton() async {
    _jeton = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('jeton_toun');
  }

  bool get estConnecte => _jeton != null;

  Map<String, String> get _entetes {
    final entetes = {'Content-Type': 'application/json'};
    if (_jeton != null) {
      entetes['Authorization'] = 'Bearer $_jeton';
    }
    return entetes;
  }

  Map<String, dynamic> _traiter(http.Response reponse) {
    Map<String, dynamic> corps;

    try {
      corps = jsonDecode(reponse.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException(
        'Réponse inattendue du serveur (code ${reponse.statusCode}). Vérifiez l\'adresse de l\'API.',
        reponse.statusCode,
      );
    }

    if (reponse.statusCode >= 200 && reponse.statusCode < 300 && corps['ok'] == true) {
      return corps['data'] as Map<String, dynamic>? ?? {};
    }

    throw ApiException(
      (corps['message'] as String?) ?? 'Une erreur est survenue.',
      reponse.statusCode,
    );
  }

  Future<Map<String, dynamic>> get(String chemin, {Map<String, String>? parametres}) async {
    final uri = Uri.parse('$baseUrl/$chemin').replace(queryParameters: parametres);
    final reponse = await http.get(uri, headers: _entetes);
    return _traiter(reponse);
  }

  Future<Map<String, dynamic>> post(String chemin, Map<String, dynamic> corps) async {
    final uri = Uri.parse('$baseUrl/$chemin');
    final reponse = await http.post(uri, headers: _entetes, body: jsonEncode(corps));
    return _traiter(reponse);
  }
}
