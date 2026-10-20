import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/formation.dart';
import '../services/lecon_service.dart';
import '../theme/app_theme.dart';
import '../widgets/toun_button.dart';
import '../widgets/toun_lecteur_video.dart';
import 'quiz_screen.dart';

class LessonScreen extends StatefulWidget {
  final int idForm;
  final String titreFormation;
  final List<ModuleFormation> modules;
  final Lecon leconInitiale;
  final List<EvaluationFormation> evaluations;

  const LessonScreen({
    super.key,
    required this.idForm,
    required this.titreFormation,
    required this.modules,
    required this.leconInitiale,
    this.evaluations = const [],
  });

  @override
  State<LessonScreen> createState() => _LessonScreenState();
}

class _LessonScreenState extends State<LessonScreen> {
  late Lecon _leconActive;
  late Future<ContenuLecon> _futurContenu;
  final Set<int> _leconsTerminees = {};
  final Set<int> _testsReussis = {};
  int _moduleOuvert = 0;

  @override
  void initState() {
    super.initState();
    _leconActive = widget.leconInitiale;
    _futurContenu = LeconService.instance.contenu(_leconActive.idLecon);

    for (final module in widget.modules) {
      _leconsTerminees.addAll(module.lecons.where((l) => l.terminee).map((l) => l.idLecon));

      if (module.test != null && module.test!.reussi) {
        _testsReussis.add(module.test!.idQuiz);
      }
    }

    for (var i = 0; i < widget.modules.length; i++) {
      if (widget.modules[i].lecons.any((l) => l.idLecon == _leconActive.idLecon)) {
        _moduleOuvert = i;
        break;
      }
    }
  }

  int _indexModule(Lecon lecon) {
    return widget.modules.indexWhere((m) => m.lecons.any((l) => l.idLecon == lecon.idLecon));
  }

  bool _moduleAccessible(int index) {
    for (var i = 0; i < index; i++) {
      final test = widget.modules[i].test;

      if (test != null && !_testsReussis.contains(test.idQuiz)) {
        return false;
      }
    }

    return true;
  }

  bool _moduleTermine(int index) {
    return widget.modules[index].lecons.every((l) => _leconsTerminees.contains(l.idLecon));
  }

  void _message(String texte) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(texte)));
  }

  void _ouvrirLecon(Lecon lecon) {
    if (!_moduleAccessible(_indexModule(lecon))) {
      _message('Réussissez le test du module précédent pour débloquer ce module.');
      return;
    }

    setState(() {
      _leconActive = lecon;
      _futurContenu = LeconService.instance.contenu(lecon.idLecon);
    });
  }

  Future<void> _marquerTerminee() async {
    setState(() => _leconsTerminees.add(_leconActive.idLecon));

    var coursTermines = false;

    try {
      coursTermines = await LeconService.instance.terminerLecon(widget.idForm, _leconActive.idLecon);
    } catch (erreur) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$erreur')));
      }
      return;
    }

    if (!mounted) return;

    final index = _indexModule(_leconActive);
    final test = index >= 0 ? widget.modules[index].test : null;

    if (test != null && !_testsReussis.contains(test.idQuiz) && _moduleTermine(index)) {
      _proposerTest(index);
      return;
    }

    if (coursTermines) {
      _proposerEvaluation();
      return;
    }

    _ouvrirLeconSuivante();
  }

  void _ouvrirLeconSuivante() {
    for (var i = 0; i < widget.modules.length; i++) {
      if (!_moduleAccessible(i)) {
        return;
      }

      final restantes = widget.modules[i].lecons.where((l) => !_leconsTerminees.contains(l.idLecon)).toList();

      if (restantes.isNotEmpty) {
        setState(() => _moduleOuvert = i);
        _ouvrirLecon(restantes.first);
        return;
      }
    }
  }

  void _proposerTest(int index) {
    showDialog<void>(
      context: context,
      builder: (dialogue) => AlertDialog(
        title: const Text('Module terminé'),
        content: const Text('Faites le petit test de ce module pour vérifier vos acquis. Il faut au moins 10/20 pour débloquer le module suivant.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogue).pop(), child: const Text('Plus tard')),
          ElevatedButton(
            onPressed: () {
              Navigator.of(dialogue).pop();
              _passerTest(index);
            },
            child: const Text('Passer le test'),
          ),
        ],
      ),
    );
  }

  Future<void> _passerTest(int index) async {
    final test = widget.modules[index].test;

    if (test == null) return;

    if (!_moduleAccessible(index)) {
      _message('Réussissez le test du module précédent pour débloquer ce module.');
      return;
    }

    if (!_moduleTermine(index)) {
      _message('Terminez toutes les leçons de ce module pour débloquer son test.');
      return;
    }

    final reussi = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => QuizScreen(idQuiz: test.idQuiz)));

    if (!mounted || reussi != true) return;

    setState(() => _testsReussis.add(test.idQuiz));

    final toutesTerminees = widget.modules.expand((m) => m.lecons).every((l) => _leconsTerminees.contains(l.idLecon));

    if (toutesTerminees) {
      _proposerEvaluation();
      return;
    }

    _ouvrirLeconSuivante();
  }

  void _proposerEvaluation() {
    final aPasser = widget.evaluations.where((e) => !e.reussi).toList();

    if (aPasser.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Vous avez terminé toutes les leçons de cette formation.')),
      );
      return;
    }

    showDialog<void>(
      context: context,
      builder: (dialogue) => AlertDialog(
        title: const Text('Toutes les leçons sont terminées'),
        content: const Text('Passez maintenant l\'évaluation finale. Une note d\'au moins 10/20 valide la formation et génère votre certificat.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogue).pop(), child: const Text('Plus tard')),
          ElevatedButton(
            onPressed: () {
              Navigator.of(dialogue).pop();
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => QuizScreen(idQuiz: aPasser.first.idQuiz)));
            },
            child: const Text('Passer l\'évaluation'),
          ),
        ],
      ),
    );
  }

  Future<void> _ouvrirDocument(String lien) async {
    final ouvert = await launchUrl(Uri.parse(lien), mode: LaunchMode.externalApplication);

    if (!ouvert && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Impossible d\'ouvrir le document.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: TounColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.white,
        flexibleSpace: Container(decoration: const BoxDecoration(gradient: TounColors.headerGradient)),
        systemOverlayStyle: SystemUiOverlayStyle.light,
        title: Text(widget.titreFormation, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w700)),
      ),
      body: Column(
        children: [
          _lecteurVideo(),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(20),
              children: [
                FutureBuilder<ContenuLecon>(
                  future: _futurContenu,
                  builder: (context, snapshot) {
                    final titre = snapshot.data?.titre ?? _leconActive.titre;
                    final contenu = snapshot.data?.contenu ?? '';
                    final document = snapshot.data?.document ?? '';
                    final terminee = _leconsTerminees.contains(_leconActive.idLecon);

                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(titre, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: TounColors.navy)),
                        if (contenu.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(contenu, style: const TextStyle(color: TounColors.textSecondary, height: 1.6)),
                        ],
                        if (document.isNotEmpty) ...[
                          const SizedBox(height: 14),
                          OutlinedButton.icon(
                            onPressed: () => _ouvrirDocument(document),
                            icon: Icon(snapshot.data?.type == 'pdf' ? Icons.picture_as_pdf_rounded : Icons.open_in_new_rounded, size: 18),
                            label: Text(snapshot.data?.type == 'pdf' ? 'Ouvrir le support PDF' : 'Ouvrir le lien'),
                          ),
                        ],
                        if (_indexModule(_leconActive) >= 0 && widget.modules[_indexModule(_leconActive)].ressources.isNotEmpty) ...[
                          const SizedBox(height: 16),
                          _ressources(widget.modules[_indexModule(_leconActive)].ressources),
                        ],
                        const SizedBox(height: 16),
                        TounButton(
                          texte: terminee ? 'Leçon terminée · leçon suivante →' : 'Marquer comme terminée →',
                          onPressed: _marquerTerminee,
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 26),
                const Text('Modules', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: TounColors.navy)),
                const SizedBox(height: 10),
                ...List.generate(widget.modules.length, (index) => _carteModule(index)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _lecteurVideo() {
    return FutureBuilder<ContenuLecon>(
      future: _futurContenu,
      builder: (context, snapshot) {
        final video = snapshot.data?.video ?? '';

        if (video.isNotEmpty) {
          return TounLecteurVideo(key: ValueKey(video), url: video);
        }

        return Container(
          width: double.infinity,
          height: 210,
          color: TounColors.navy,
          child: Center(
            child: snapshot.connectionState != ConnectionState.done
                ? const CircularProgressIndicator(color: Colors.white)
                : Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), shape: BoxShape.circle),
                        child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 34),
                      ),
                      const SizedBox(height: 10),
                      const Text('Aucune vidéo pour cette leçon', style: TextStyle(color: Colors.white70, fontSize: 12)),
                    ],
                  ),
          ),
        );
      },
    );
  }

  Widget _carteModule(int index) {
    final module = widget.modules[index];
    final ouvert = index == _moduleOuvert;
    final accessible = _moduleAccessible(index);
    final test = module.test;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(color: TounColors.background, borderRadius: BorderRadius.circular(14)),
      child: Column(
        children: [
          InkWell(
            onTap: () => setState(() => _moduleOuvert = ouvert ? -1 : index),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  if (!accessible) ...[
                    const Icon(Icons.lock_rounded, size: 16, color: TounColors.gray),
                    const SizedBox(width: 6),
                  ],
                  Expanded(child: Text('Module ${index + 1} : ${module.titre}', style: TextStyle(fontWeight: FontWeight.w700, color: accessible ? TounColors.navy : TounColors.gray))),
                  Icon(ouvert ? Icons.expand_less_rounded : Icons.expand_more_rounded, color: TounColors.gray),
                ],
              ),
            ),
          ),
          if (ouvert)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
              child: Column(
                children: [
                  ...module.lecons.map((lecon) {
                  final active = lecon.idLecon == _leconActive.idLecon;
                  final terminee = _leconsTerminees.contains(lecon.idLecon);

                  return InkWell(
                    onTap: () => _ouvrirLecon(lecon),
                    borderRadius: BorderRadius.circular(10),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Row(
                        children: [
                          Icon(
                            !accessible ? Icons.lock_outline_rounded : (terminee ? Icons.check_circle_rounded : (active ? Icons.play_circle_fill_rounded : Icons.play_circle_outline_rounded)),
                            size: 18,
                            color: !accessible ? TounColors.gray : (terminee ? TounColors.green : (active ? TounColors.teal : TounColors.gray)),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              lecon.titre,
                              style: TextStyle(fontSize: 12.5, fontWeight: active ? FontWeight.w700 : FontWeight.w500, color: active ? TounColors.navy : TounColors.textSecondary),
                            ),
                          ),
                          if (lecon.duree != null) Text(lecon.duree!, style: const TextStyle(fontSize: 11, color: TounColors.textSecondary)),
                        ],
                      ),
                    ),
                  );
                  }),
                  if (test != null) _ligneTest(index, test, accessible),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _ressources(List<RessourceCours> ressources) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: TounColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Ressources pour aller plus loin', style: TextStyle(fontWeight: FontWeight.w800, color: TounColors.navy)),
          const SizedBox(height: 6),
          ...ressources.map((ressource) => InkWell(
                onTap: () => _ouvrirDocument(ressource.lien),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 7),
                  child: Row(
                    children: [
                      Icon(ressource.type == 'pdf' ? Icons.picture_as_pdf_rounded : Icons.link_rounded, size: 18, color: TounColors.teal),
                      const SizedBox(width: 10),
                      Expanded(child: Text(ressource.titre, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: TounColors.blue))),
                    ],
                  ),
                ),
              )),
        ],
      ),
    );
  }

  Widget _ligneTest(int index, EvaluationFormation test, bool accessible) {
    final reussi = _testsReussis.contains(test.idQuiz);
    final disponible = accessible && _moduleTermine(index);

    return InkWell(
      onTap: reussi ? null : () => _passerTest(index),
      borderRadius: BorderRadius.circular(10),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Icon(
              reussi ? Icons.check_circle_rounded : (disponible ? Icons.fact_check_rounded : Icons.lock_outline_rounded),
              size: 18,
              color: reussi ? TounColors.green : (disponible ? TounColors.teal : TounColors.gray),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                reussi ? 'Test du module réussi' : 'Test du module',
                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: disponible || reussi ? TounColors.blue : TounColors.textSecondary),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
