import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

import '../theme/app_theme.dart';

class TounLecteurVideo extends StatefulWidget {
  final String url;
  final String image;

  const TounLecteurVideo({super.key, required this.url, this.image = ''});

  @override
  State<TounLecteurVideo> createState() => _TounLecteurVideoState();
}

class _TounLecteurVideoState extends State<TounLecteurVideo> {
  VideoPlayerController? _controleur;
  bool _demarre = false;
  bool _erreur = false;

  @override
  void initState() {
    super.initState();
    _preparer();
  }

  @override
  void didUpdateWidget(TounLecteurVideo ancien) {
    super.didUpdateWidget(ancien);
    if (ancien.url != widget.url) {
      _controleur?.dispose();
      _controleur = null;
      _demarre = false;
      _erreur = false;
      _preparer();
    }
  }

  Future<void> _preparer() async {
    final controleur = VideoPlayerController.networkUrl(Uri.parse(widget.url));
    _controleur = controleur;
    controleur.addListener(_actualiser);

    try {
      await controleur.initialize();
    } catch (_) {
      if (mounted && _controleur == controleur) {
        setState(() {
          _erreur = true;
        });
      }
      return;
    }

    if (mounted && _controleur == controleur) {
      setState(() {});
    }
  }

  void _actualiser() {
    if (mounted) {
      setState(() {});
    }
  }

  @override
  void dispose() {
    _controleur?.removeListener(_actualiser);
    _controleur?.dispose();
    super.dispose();
  }

  void _basculer() {
    final controleur = _controleur;
    if (controleur == null || !controleur.value.isInitialized) return;

    if (controleur.value.isPlaying) {
      controleur.pause();
    } else {
      if (controleur.value.position >= controleur.value.duration) {
        controleur.seekTo(Duration.zero);
      }
      controleur.play();
      _demarre = true;
    }
  }

  String _temps(Duration duree) {
    final minutes = duree.inMinutes.remainder(60).toString().padLeft(2, '0');
    final secondes = duree.inSeconds.remainder(60).toString().padLeft(2, '0');
    return duree.inHours > 0 ? '${duree.inHours}:$minutes:$secondes' : '$minutes:$secondes';
  }

  @override
  Widget build(BuildContext context) {
    final controleur = _controleur;
    final pret = controleur != null && controleur.value.isInitialized;
    final enLecture = pret && controleur.value.isPlaying;
    final ratio = pret && controleur.value.aspectRatio > 0 ? controleur.value.aspectRatio : 16 / 9;

    return Container(
      color: Colors.black,
      child: AspectRatio(
        aspectRatio: ratio,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (pret) VideoPlayer(controleur),
            if (!_demarre && widget.image.isNotEmpty)
              Image.network(
                widget.image,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => const SizedBox.shrink(),
              ),
            if (!_demarre) Container(color: TounColors.navy.withValues(alpha: 0.35)),
            if (_erreur)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: Text(
                    'Impossible de lire cette vidéo.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white70, fontSize: 12.5),
                  ),
                ),
              )
            else if (!pret)
              const Center(
                child: SizedBox(
                  width: 30,
                  height: 30,
                  child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                ),
              )
            else
              GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: _basculer,
                child: AnimatedOpacity(
                  duration: const Duration(milliseconds: 250),
                  opacity: enLecture ? 0 : 1,
                  child: Center(
                    child: Container(
                      width: 62,
                      height: 62,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: TounColors.buttonGradient,
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 14),
                        ],
                      ),
                      child: Icon(
                        enLecture ? Icons.pause_rounded : Icons.play_arrow_rounded,
                        color: Colors.white,
                        size: 36,
                      ),
                    ),
                  ),
                ),
              ),
            if (pret && _demarre)
              Positioned(
                left: 0,
                right: 0,
                bottom: 0,
                child: Container(
                  padding: const EdgeInsets.fromLTRB(12, 18, 12, 8),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.bottomCenter,
                      end: Alignment.topCenter,
                      colors: [Colors.black.withValues(alpha: 0.6), Colors.transparent],
                    ),
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: _basculer,
                        child: Icon(
                          enLecture ? Icons.pause_rounded : Icons.play_arrow_rounded,
                          color: Colors.white,
                          size: 22,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: VideoProgressIndicator(
                          controleur,
                          allowScrubbing: true,
                          padding: const EdgeInsets.symmetric(vertical: 6),
                          colors: VideoProgressColors(
                            playedColor: const Color(0xFF67E0E6),
                            bufferedColor: Colors.white.withValues(alpha: 0.35),
                            backgroundColor: Colors.white.withValues(alpha: 0.15),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '${_temps(controleur.value.position)} / ${_temps(controleur.value.duration)}',
                        style: const TextStyle(color: Colors.white, fontSize: 10.5),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
