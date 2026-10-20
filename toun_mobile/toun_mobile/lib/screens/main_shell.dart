import 'package:flutter/material.dart';

import '../widgets/toun_bottom_nav.dart';
import 'certificats_screen.dart';
import 'dashboard_screen.dart';
import 'formations_screen.dart';
import 'my_formations_screen.dart';
import 'profile_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key, this.indexInitial = 0});

  final int indexInitial;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  late int _index = widget.indexInitial;

  void changerOnglet(int index) => setState(() => _index = index);

  @override
  Widget build(BuildContext context) {
    final pages = [
      DashboardScreen(
        onVoirFormations: () => changerOnglet(1),
        onVoirMesFormations: () => changerOnglet(2),
        onVoirCertificats: () => changerOnglet(3),
        onVoirProfil: () => changerOnglet(4),
      ),
      FormationsScreen(
        onVoirMesFormations: () => changerOnglet(2),
        onVoirCertificats: () => changerOnglet(3),
        onVoirProfil: () => changerOnglet(4),
      ),
      MyFormationsScreen(
        onVoirFormations: () => changerOnglet(1),
        onVoirCertificats: () => changerOnglet(3),
        onVoirProfil: () => changerOnglet(4),
      ),
      CertificatsScreen(onVoirMesFormations: () => changerOnglet(2)),
      ProfileScreen(
        onVoirFormations: () => changerOnglet(1),
        onVoirMesFormations: () => changerOnglet(2),
        onVoirCertificats: () => changerOnglet(3),
      ),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: TounBottomNav(index: _index, onChanged: changerOnglet),
    );
  }
}
