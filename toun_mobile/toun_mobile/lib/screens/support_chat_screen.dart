import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/toun_en_tete.dart';

class _MessageChat {
  final String texte;
  final bool deMoi;
  final DateTime heure;

  const _MessageChat({
    required this.texte,
    required this.deMoi,
    required this.heure,
  });
}

class SupportChatScreen extends StatefulWidget {
  const SupportChatScreen({super.key});

  @override
  State<SupportChatScreen> createState() => _SupportChatScreenState();
}

class _SupportChatScreenState extends State<SupportChatScreen> {
  final TextEditingController _controleur = TextEditingController();
  final ScrollController _defilement = ScrollController();

  final List<_MessageChat> _messages = [
    _MessageChat(
      texte:
          'Bonjour 👋 Je suis votre formateur TOUN. Posez-moi vos questions '
          'sur vos formations, un paiement ou un certificat, je vous réponds '
          'rapidement.',
      deMoi: false,
      heure: DateTime.now(),
    ),
  ];

  @override
  void dispose() {
    _controleur.dispose();
    _defilement.dispose();
    super.dispose();
  }

  void _envoyer() {
    final texte = _controleur.text.trim();
    if (texte.isEmpty) return;

    setState(() {
      _messages.add(_MessageChat(texte: texte, deMoi: true, heure: DateTime.now()));
    });
    _controleur.clear();
    _deferAuBas();

    Future.delayed(const Duration(milliseconds: 700), () {
      if (!mounted) return;

      setState(() {
        _messages.add(
          _MessageChat(
            texte: 'Merci pour votre message. Un formateur TOUN vous répondra très bientôt.',
            deMoi: false,
            heure: DateTime.now(),
          ),
        );
      });
      _deferAuBas();
    });
  }

  void _deferAuBas() {
    Future.delayed(const Duration(milliseconds: 80), () {
      if (!_defilement.hasClients) return;

      _defilement.animateTo(
        _defilement.position.maxScrollExtent,
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOut,
      );
    });
  }

  String _formaterHeure(DateTime heure) {
    final h = heure.hour.toString().padLeft(2, '0');
    final m = heure.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            TounEnTete(
              titre: 'Assistance TOUN',
              sousTitre: 'Formateurs disponibles',
              onRetour: () => Navigator.of(context).pop(),
            ),
            const SizedBox(height: 12),

            Expanded(
              child: ListView.builder(
                controller: _defilement,
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final message = _messages[index];

                  return Align(
                    alignment: message.deMoi ? Alignment.centerRight : Alignment.centerLeft,
                    child: Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
                      decoration: BoxDecoration(
                        color: message.deMoi ? TounColors.teal : Colors.white,
                        borderRadius: BorderRadius.only(
                          topLeft: const Radius.circular(14),
                          topRight: const Radius.circular(14),
                          bottomLeft: Radius.circular(message.deMoi ? 14 : 2),
                          bottomRight: Radius.circular(message.deMoi ? 2 : 14),
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.05),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            message.texte,
                            style: TextStyle(
                              fontSize: 13,
                              height: 1.4,
                              color: message.deMoi ? Colors.white : TounColors.navy,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _formaterHeure(message.heure),
                            style: TextStyle(
                              fontSize: 10,
                              color: message.deMoi
                                  ? Colors.white.withValues(alpha: 0.75)
                                  : TounColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),

            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(26),
                        border: Border.all(color: TounColors.border),
                      ),
                      child: TextField(
                        controller: _controleur,
                        textCapitalization: TextCapitalization.sentences,
                        minLines: 1,
                        maxLines: 4,
                        decoration: const InputDecoration(
                          border: InputBorder.none,
                          hintText: 'Écrivez votre message...',
                          hintStyle: TextStyle(fontSize: 13, color: TounColors.textSecondary),
                        ),
                        style: const TextStyle(fontSize: 13, color: TounColors.navy),
                        onSubmitted: (_) => _envoyer(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  InkWell(
                    onTap: _envoyer,
                    borderRadius: BorderRadius.circular(24),
                    child: Container(
                      width: 46,
                      height: 46,
                      decoration: const BoxDecoration(
                        gradient: TounColors.buttonGradient,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
