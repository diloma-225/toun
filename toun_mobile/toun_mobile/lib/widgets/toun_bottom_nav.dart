import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class TounBottomNav extends StatelessWidget {
  final int index;
  final ValueChanged<int> onChanged;

  const TounBottomNav({super.key, required this.index, required this.onChanged});

  static const _items = [
    (icone: Icons.home_rounded, label: 'Accueil'),
    (icone: Icons.menu_book_rounded, label: 'Formations'),
    (icone: Icons.play_circle_fill_rounded, label: 'Mes cours'),
    (icone: Icons.workspace_premium_rounded, label: 'Certificats'),
    (icone: Icons.person_rounded, label: 'Profil'),
  ];

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 16, offset: const Offset(0, -4)),
        ],
      ),
      child: SafeArea(
        child: SizedBox(
          height: 62,
          child: Row(
            children: List.generate(_items.length, (i) {
              final actif = i == index;
              final item = _items[i];

              return Expanded(
                child: InkWell(
                  onTap: () => onChanged(i),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 220),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                        decoration: BoxDecoration(
                          color: actif ? TounColors.teal.withValues(alpha: 0.14) : Colors.transparent,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Icon(item.icone, color: actif ? TounColors.blue : TounColors.gray, size: 23),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        item.label,
                        style: TextStyle(
                          fontSize: 9.5,
                          fontWeight: actif ? FontWeight.w700 : FontWeight.w500,
                          color: actif ? TounColors.blue : TounColors.gray,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}
