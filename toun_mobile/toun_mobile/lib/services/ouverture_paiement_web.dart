import 'dart:js_interop';

@JS('window.location.assign')
external void _allerVers(JSString adresse);

Future<void> ouvrirPagePaiement(Uri uri) async {
  _allerVers(uri.toString().toJS);
}
