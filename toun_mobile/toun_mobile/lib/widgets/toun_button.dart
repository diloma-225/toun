import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

enum TounButtonStyle { degrade, blanc, contour }

class TounButton extends StatelessWidget {
  final String texte;
  final VoidCallback? onPressed;
  final bool chargement;
  final TounButtonStyle style;

  const TounButton({
    super.key,
    required this.texte,
    required this.onPressed,
    this.chargement = false,
    this.style = TounButtonStyle.degrade,
  });

  @override
  Widget build(BuildContext context) {
    if (style == TounButtonStyle.contour) {
      return SizedBox(
        height: 52,
        child: OutlinedButton(
          onPressed: chargement ? null : onPressed,
          style: OutlinedButton.styleFrom(
            foregroundColor: TounColors.navy,
            side: const BorderSide(color: TounColors.border),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          ),
          child: _contenu(TounColors.navy),
        ),
      );
    }

    if (style == TounButtonStyle.blanc) {
      return SizedBox(
        height: 52,
        child: ElevatedButton(
          onPressed: chargement ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.white,
            foregroundColor: TounColors.navy,
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          ),
          child: _contenu(TounColors.navy),
        ),
      );
    }

    return SizedBox(
      height: 52,
      child: DecoratedBox(
        decoration: BoxDecoration(
          gradient: TounColors.buttonGradient,
          borderRadius: BorderRadius.circular(14),
        ),
        child: ElevatedButton(
          onPressed: chargement ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.transparent,
            shadowColor: Colors.transparent,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          ),
          child: _contenu(Colors.white),
        ),
      ),
    );
  }

  Widget _contenu(Color couleur) {
    if (chargement) {
      return SizedBox(
        height: 20,
        width: 20,
        child: CircularProgressIndicator(strokeWidth: 2.4, color: couleur),
      );
    }

    return Text(
      texte,
      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: couleur),
    );
  }
}
