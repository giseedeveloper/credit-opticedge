/// Swahili copy for face-match API reason codes (`id_front:…`, `headshot:…`, `match:…`).
String localizedFaceMatchReason(String? reason) {
  if (reason == null || reason.trim().isEmpty) {
    return '';
  }

  final l = reason.trim().toLowerCase();

  if (l.startsWith('id_front:')) {
    return _idFrontMessage(l.substring('id_front:'.length));
  }
  if (l.startsWith('headshot:')) {
    return _headshotMessage(l.substring('headshot:'.length));
  }
  if (l.startsWith('match:')) {
    return _matchMessage(l.substring('match:'.length));
  }

  return _genericMessage(l);
}

String _idFrontMessage(String code) {
  switch (code) {
    case 'no_face_detected':
      return 'Picha ya NIDA haionyeshi uso vizuri upande wa kulia. Piga tena kadi nzima, laini, na hakikisha picha ya uso inaonekana wazi.';
    case 'face_too_small':
      return 'Picha ya uso kwenye NIDA ni ndogo sana. Karibia kadi kidogo au piga picha ya karibu zaidi.';
    case 'image_blurry':
      return 'Picha ya NIDA imeblur. Simamisha simu, epuka mwendo, na piga tena kadi kwa mwanga wa kutosha.';
    case 'image_too_dark':
      return 'Picha ya NIDA ni giza sana. Ongeza mwanga au piga tena mahali penye mwanga wa kutosha.';
    case 'image_too_bright':
      return 'Picha ya NIDA ina mwanga mkali sana (glare). Epuka mwanga wa moja kwa moja na piga tena.';
    case 'invalid_face_crop':
      return 'Uso kwenye NIDA haujasomwa vizuri. Piga tena kadi nzima bila kukata sehemu ya picha ya uso.';
    case 'embedding_unavailable':
      return 'Picha ya NIDA haikusomwa vizuri na mfumo. Jaribu kupiga tena kadi kwa ubora bora.';
    case 'multiple_faces_detected':
      return 'Picha ya NIDA ina nyuso zaidi ya moja. Piga kadi moja pekee yenye picha ya mteja upande wa kulia.';
    default:
      return 'Tatizo kwenye picha ya NIDA: $code. Piga tena kadi kwa ubora bora.';
  }
}

String _headshotMessage(String code) {
  switch (code) {
    case 'no_face_detected':
      return 'Uso haujaonekana kwenye skani. Weka uso katikati ya frame na ujaribu tena.';
    case 'face_too_small':
      return 'Uso uko mbali sana kwenye skani. Msogeze mteja karibu kidogo kwenye kamera.';
    case 'image_blurry':
      return 'Picha ya skani imeblur. Simamisha kamera kwa utulivu na mwanga wa kutosha.';
    case 'image_too_dark':
      return 'Skani ni giza sana. Ongeza mwanga kabla ya kupiga picha tena.';
    case 'image_too_bright':
      return 'Mwanga ni mkali sana kwenye skani. Punguza glare kwenye uso na ujaribu tena.';
    case 'multiple_faces_detected':
      return 'Nyuso zaidi ya moja zimeonekana kwenye skani. Hakikisha mtu mmoja tu anaonekana.';
    case 'invalid_image':
      return 'Picha ya skani haisomwi. Jaribu tena kupiga picha.';
    default:
      return 'Tatizo kwenye skani ya uso: $code. Jaribu tena kwa mwanga na ukuta laini.';
  }
}

String _matchMessage(String code) {
  switch (code) {
    case 'low_similarity':
      return 'Uso haulingani vya kutosha na picha ya NIDA. Hakikisha NIDA ni sahihi, piga tena kadi au skani kwa mwanga bora.';
    case 'id_quality_poor':
      return 'Picha ya NIDA ina ubora mbaya. Piga tena kadi nzima — picha ya uso upande wa kulia iwe wazi.';
    case 'headshot_quality_poor':
      return 'Skani ya uso ina ubora mbaya. Jaribu tena kwa ukuta laini na mwanga sawa.';
    default:
      return 'Ulinganisho haukufikia kiwango cha kupita. Jaribu tena NIDA na skani.';
  }
}

String _genericMessage(String l) {
  if (l.contains('face match service is not configured')) {
    return 'Huduma ya ulinganisho wa uso haijawekwa kwenye seva. Wasiliana na msimamizi wa mfumo.';
  }
  if (l.contains('face match service is unreachable')) {
    return 'Huduma ya ulinganisho haipatikani kwa sasa. Angalia mtandao au jaribu tena baada ya muda mfupi.';
  }
  if (l.contains('face match failed') && l.contains('manual')) {
    return 'Ulinganisho haukufanikiwa. Jaribu picha nyingine au omba uhakiki wa mkono.';
  }
  if (l.contains('multiple_faces_detected')) {
    return 'Nyuso zaidi ya moja zimeonekana. Hakikisha mtu mmoja tu anaonekana.';
  }
  if (l.contains('no_face_detected')) {
    return 'Uso haujaonekana vizuri. Weka uso katikati ya frame na ujaribu tena.';
  }
  if (l.contains('face_too_small')) {
    return 'Uso uko mbali sana. Msogeze mteja karibu kidogo kwenye kamera.';
  }
  if (l.contains('image_blurry')) {
    return 'Picha imeblur. Simamisha kamera kwa utulivu na mwanga wa kutosha.';
  }
  if (l.contains('image_too_dark')) {
    return 'Picha ni giza sana. Ongeza mwanga kabla ya kupiga picha tena.';
  }
  if (l.contains('image_too_bright')) {
    return 'Mwanga ni mkali sana. Punguza mwanga mkali na ujaribu tena.';
  }
  if (l.contains('invalid_image')) {
    return 'Picha haisomwi. Jaribu kupiga tena.';
  }
  if (l.contains('internal_error')) {
    return 'Hitilafu ya mfumo wakati wa ulinganisho. Jaribu tena baada ya muda mfupi.';
  }
  return l;
}
