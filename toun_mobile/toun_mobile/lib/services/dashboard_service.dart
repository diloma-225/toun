import '../models/dashboard.dart';
import 'api_client.dart';

class DashboardService {
  DashboardService._();

  static final DashboardService instance = DashboardService._();

  final ApiClient _client = ApiClient.instance;

  Future<TableauDeBord> charger() async {
    final donnees = await _client.get('dashboard.php');
    return TableauDeBord.fromJson(donnees);
  }
}
