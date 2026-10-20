import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';
import 'toun_logo.dart';

class TounEnTete extends StatelessWidget {
  final String titre;
  final String? sousTitre;
  final VoidCallback? onMenu;
  final VoidCallback? onRetour;
  final List<Widget> actions;
  final bool afficherLogo;
  final Widget? bas;
  final double espaceBas;

  const TounEnTete({
    super.key,
    required this.titre,
    this.sousTitre,
    this.onMenu,
    this.onRetour,
    this.actions = const [],
    this.afficherLogo = false,
    this.bas,
    this.espaceBas = 0,
  });

  Widget _boutonGauche() {
    if (onRetour != null) {
      return IconButton(
        onPressed: onRetour,
        icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
      );
    }

    if (onMenu != null) {
      return IconButton(
        onPressed: onMenu,
        icon: const Icon(Icons.menu_rounded, color: Colors.white),
      );
    }

    return const SizedBox(width: 8);
  }

  Widget _titre(double taille) {
    return Text(
      titre,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: TextStyle(fontSize: taille, fontWeight: FontWeight.w800, color: Colors.white),
    );
  }

  Widget _sousTitre() {
    return Text(
      sousTitre!,
      style: TextStyle(fontSize: 12.5, color: Colors.white.withValues(alpha: 0.82), height: 1.4),
    );
  }

  @override
  Widget build(BuildContext context) {
    final hautEcran = MediaQuery.paddingOf(context).top;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Container(
        width: double.infinity,
        clipBehavior: Clip.antiAlias,
        decoration: const BoxDecoration(
          gradient: TounColors.headerGradient,
          borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
        ),
        child: Stack(
          children: [
            Positioned(
              top: -40,
              right: -30,
              child: Container(
                width: 160,
                height: 160,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withValues(alpha: 0.07),
                ),
              ),
            ),
            Positioned(
              bottom: -50,
              left: -20,
              child: Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withValues(alpha: 0.05),
                ),
              ),
            ),
            Padding(
              padding: EdgeInsets.fromLTRB(8, hautEcran + 6, 16, 22 + espaceBas),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _boutonGauche(),
                      const SizedBox(width: 4),
                      Expanded(
                        child: afficherLogo
                            ? const Align(
                                alignment: Alignment.centerLeft,
                                child: TounLogo(hauteur: 24, blanc: true),
                              )
                            : _titre(19),
                      ),
                      ...actions,
                    ],
                  ),
                  if (afficherLogo) ...[
                    const SizedBox(height: 14),
                    Padding(
                      padding: const EdgeInsets.only(left: 12),
                      child: _titre(21),
                    ),
                  ],
                  if (sousTitre != null) ...[
                    const SizedBox(height: 4),
                    Padding(
                      padding: EdgeInsets.only(left: afficherLogo ? 12 : 52),
                      child: _sousTitre(),
                    ),
                  ],
                  if (bas != null) ...[
                    const SizedBox(height: 16),
                    Padding(
                      padding: const EdgeInsets.only(left: 12),
                      child: bas!,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
