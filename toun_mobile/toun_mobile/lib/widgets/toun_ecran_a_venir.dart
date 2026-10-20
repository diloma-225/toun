import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'toun_en_tete.dart';

class EcranAVenir extends StatelessWidget {
  final String titre;
  final IconData icone;
  final String? description;
  final bool afficherRetour;

  const EcranAVenir({
    super.key,
    required this.titre,
    required this.icone,
    this.description,
    this.afficherRetour = true,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: titre,
              onRetour: afficherRetour ? () => Navigator.of(context).pop() : null,
            ),
            Expanded(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          color: TounColors.teal.withValues(alpha: 0.10),
                          borderRadius: BorderRadius.circular(22),
                        ),
                        child: Icon(icone, size: 34, color: TounColors.teal),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        titre,
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: TounColors.navy),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        description ?? 'Cette section arrive très bientôt sur l\'application TOUN.',
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 12.5, color: TounColors.textSecondary, height: 1.5),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
