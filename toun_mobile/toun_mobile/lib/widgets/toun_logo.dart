import 'package:flutter/material.dart';

class TounLogo extends StatelessWidget {
  final double hauteur;
  final bool blanc;

  const TounLogo({super.key, this.hauteur = 46, this.blanc = false});

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      blanc ? 'assets/images/logo_blanc.png' : 'assets/images/logo_couleur.png',
      height: hauteur,
      fit: BoxFit.contain,
    );
  }
}

class TounHeaderMark extends StatelessWidget {
  final double hauteur;

  const TounHeaderMark({super.key, this.hauteur = 26});

  @override
  Widget build(BuildContext context) {
    return TounLogo(hauteur: hauteur);
  }
}
