import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/settings_provider.dart';

/// Lightweight localization — no codegen needed.
/// Access via `ref.watch(stringsProvider)` or `S.of(ref)`.
class S {
  final AppLanguage _lang;
  const S._(this._lang);

  factory S.of(WidgetRef ref) =>
      S._(ref.watch(settingsProvider).language);

  String get locale => _lang == AppLanguage.sw ? 'sw' : 'en';

  // ─── Common ─────────────────────────────────────────
  String get appName => _t('Opticedge FO', 'Opticedge FO');
  String get fieldOfficerPortal => _t('Field Officer Portal', 'Programu ya Afisa wa Saha');
  String get settings => _t('Settings', 'Mipangilio');
  String get profile => _t('My Profile', 'Wasifu Wangu');
  String get dashboard => _t('Dashboard', 'Dashibodi');
  String get customers => _t('Customers', 'Wateja');
  String get register => _t('Register', 'Sajili');
  String get search => _t('Search', 'Tafuta');
  String get drafts => _t('Drafts', 'Rasimu');
  String get saveAsDraft => _t('Save as draft', 'Hifadhi rasimu');
  String get draftSavedMessage => _t(
        'Draft saved. Open Drafts to continue later.',
        'Rasimu imehifadhiwa. Fungua Rasimu kuendelea baadaye.',
      );
  String get online => _t('Online', 'Mtandaoni');

  // ─── Greetings ──────────────────────────────────────
  String get goodMorning => _t('Good morning', 'Habari za asubuhi');
  String get goodAfternoon => _t('Good afternoon', 'Habari za mchana');
  String get goodEvening => _t('Good evening', 'Habari za jioni');

  // ─── Login ──────────────────────────────────────────
  String get welcomeBack => _t('Welcome back', 'Karibu tena');
  String get signInSubtitle => _t('Sign in to continue to your account', 'Ingia kuendelea na akaunti yako');
  String get emailOrPhone => _t('Email or Phone', 'Barua pepe au Simu');
  String get password => _t('Password', 'Nenosiri');
  String get keepMeSignedIn => _t('Keep me signed in', 'Nikumbuke');
  String get signIn => _t('Sign In', 'Ingia');
  String get securityNote => _t(
    'Your session is encrypted and secure. Only authorized field officers can access this app.',
    'Kipindi chako kimesimbwa kwa usalama. Maafisa wa saha walioidhinishwa tu ndio wanaoweza kufikia programu hii.',
  );
  String get required => _t('Required', 'Inahitajika');

  // ─── Dashboard ──────────────────────────────────────
  String get quickActions => _t('Quick Actions', 'Vitendo vya Haraka');
  String get recentCustomers => _t('Recent Customers', 'Wateja wa Hivi Karibuni');
  String get seeAll => _t('See all', 'Ona wote');
  String get registerCustomer => _t('Register Customer', 'Sajili Mteja');
  String get myCustomers => _t('My Customers', 'Wateja Wangu');
  String get searchCustomers => _t('Search customers...', 'Tafuta wateja...');
  String get noCustomersYet => _t('No customers yet', 'Hakuna wateja bado');
  String get registerFirstCustomer => _t('Register your first customer to get started', 'Sajili mteja wako wa kwanza kuanza');
  String get total => _t('Total', 'Jumla');
  String get pending => _t('Pending', 'Inasubiri');
  String get verified => _t('Verified', 'Imethibitishwa');
  String get declined => _t('Declined', 'Imekataliwa');

  // ─── Profile ────────────────────────────────────────
  String get email => _t('Email', 'Barua pepe');
  String get phone => _t('Phone', 'Simu');
  String get status => _t('Status', 'Hali');
  String get active => _t('Active', 'Hai');
  String get inactive => _t('Inactive', 'Haifanyi kazi');
  String get canRegisterCustomers => _t('Can Register Customers', 'Anaweza Kusajili Wateja');
  String get adminAccess => _t('Admin Access', 'Ufikiaji wa Msimamizi');
  String get yes => _t('Yes', 'Ndiyo');
  String get no => _t('No', 'Hapana');
  String get signOut => _t('Sign Out', 'Ondoka');
  String get signOutConfirm => _t('Are you sure you want to sign out?', 'Una uhakika unataka kuondoka?');
  String get cancel => _t('Cancel', 'Ghairi');

  // ─── Settings ───────────────────────────────────────
  String get appearance => _t('Appearance', 'Muonekano');
  String get language => _t('Language', 'Lugha');
  String get security => _t('Security', 'Usalama');
  String get about => _t('About', 'Kuhusu');
  String get account => _t('Account', 'Akaunti');
  String get system => _t('System', 'Mfumo');
  String get light => _t('Light', 'Mwanga');
  String get dark => _t('Dark', 'Giza');
  String get biometricLogin => _t('Biometric Login', 'Ingia kwa Alama za Kidole');
  String get biometricSubtitle => _t('Use fingerprint or Face ID to unlock', 'Tumia alama ya kidole au Face ID kufungua');
  String get pushNotifications => _t('Push Notifications', 'Arifa');
  String get pushNotificationsSubtitle => _t(
        'In-app preference saved; FCM push when Firebase is configured',
        'Mapendeleo yamehifadhiwa; arifa za push zitafuatia usanidi wa Firebase',
      );
  String get privacyPolicy => _t('Privacy Policy', 'Sera ya Faragha');
  String get termsOfService => _t('Terms of Service', 'Masharti ya Huduma');
  String get helpAndSupport => _t('Help & Support', 'Msaada');
  String get aboutApp => _t('About Opticedge FO', 'Kuhusu Opticedge FO');
  String get clearLocalData => _t('Clear Local Data', 'Futa Data ya Ndani');
  String get clearDataConfirm => _t(
    'This will remove cached data and KYC drafts. You will not be logged out.',
    'Hii itaondoa data zilizohifadhiwa na rasimu za KYC. Hutaondolewa kwenye akaunti.',
  );
  String get clear => _t('Clear', 'Futa');
  String get localDataCleared => _t('Local data cleared', 'Data ya ndani imefutwa');
  String get logOut => _t('Log Out', 'Ondoka');
  String get logOutConfirm => _t('Are you sure you want to log out?', 'Una uhakika unataka kuondoka?');
  String get madeWithLoveInTanzania => _t('Made with ♥ in Tanzania', 'Imetengenezwa kwa ♥ Tanzania');

  // ─── Days / Months ──────────────────────────────────
  List<String> get weekdays => _lang == AppLanguage.sw
      ? ['Jumatatu','Jumanne','Jumatano','Alhamisi','Ijumaa','Jumamosi','Jumapili']
      : ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  List<String> get months => _lang == AppLanguage.sw
      ? ['Jan','Feb','Mac','Apr','Mei','Jun','Jul','Ago','Sep','Okt','Nov','Des']
      : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  // ─── Status Labels ──────────────────────────────────
  String statusLabel(String key) {
    const en = {'draft':'Draft','pending':'Pending','approved':'Approved','rejected':'Rejected','needs_correction':'Correction'};
    const sw = {'draft':'Rasimu','pending':'Inasubiri','approved':'Imeidhinishwa','rejected':'Imekataliwa','needs_correction':'Marekebisho'};
    return (_lang == AppLanguage.sw ? sw[key] : en[key]) ?? key;
  }

  // ─── Helper ─────────────────────────────────────────
  String _t(String en, String sw) => _lang == AppLanguage.sw ? sw : en;
}

final stringsProvider = Provider<S>((ref) {
  final lang = ref.watch(settingsProvider).language;
  return S._(lang);
});
