import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

final localeProvider = StateNotifierProvider<LocaleNotifier, String>((ref) {
  return LocaleNotifier();
});

class LocaleNotifier extends StateNotifier<String> {
  LocaleNotifier() : super('uz') {
    _loadLocale();
  }

  Future<void> _loadLocale() async {
    final prefs = await SharedPreferences.getInstance();
    final locale = prefs.getString('app_locale') ?? 'uz';
    state = locale;
  }

  Future<void> changeLocale(String locale) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('app_locale', locale);
    state = locale;
  }
}

class AppTranslations {
  static const Map<String, Map<String, String>> _localizedValues = {
    'uz': {
      // Common / Auth
      'app_name': 'AgroMind',
      'login_title': 'Tizimga kirish',
      'register_title': 'Ro\'yxatdan o\'tish',
      'phone_label': 'Telefon raqam',
      'password_label': 'Parol',
      'name_label': 'Ism-familiya',
      'region_label': 'Viloyat',
      'district_label': 'Tuman',
      'login_btn': 'Kirish',
      'register_btn': 'Ro\'yxatdan o\'tish',
      'no_account': 'Sizda hisob yo\'qmi? Ro\'yxatdan o\'tish',
      'have_account': 'Sizda allaqachon hisob bormi? Kirish',
      'field_required': 'Iltimos, barcha maydonlarni to\'ldiring',
      'logout': 'Tizimdan Chiqish',
      'profile_settings': 'Profil va Sozlamalar',
      'profile_info': 'Ma\'lumotlar',
      'profile_settings_title': 'Sozlamalar',
      'app_lang': 'Ilova tili',
      'user_role': 'Fermer (Dehqon)',
      
      // Home Screen / Navigation
      'home': 'Asosiy',
      'map_nav': 'Xarita',
      'weather_nav': 'Ob-havo',
      'chat_nav': 'Muloqot',
      'listings_nav': 'Ijara',
      'profile_nav': 'Profil',
      'alerts_nav': 'Ogohlantirishlar',
      'field_map': 'Dala xaritasi',
      'weather_card': 'Ob-havo',
      'soil_card': 'Tuproq tahlili',
      'listings_card': 'E\'lonlar',
      'satellite_analysis': 'Sun\'iy yo\'ldosh tahlili',
      'select_field': 'Dala tanlash',
      'active_machinery': 'Faol texnikalar',
      'all_fields_map': 'Barcha dalalar xaritada',
      
      // GPS Map / History
      'gps_monitoring': 'GPS Monitoring',
      'machinery_status': 'Texnika holati',
      'history': 'Tarix',
      'today': 'Bugun',
      'yesterday': 'Kecha',
      'two_days_ago': 'O\'tgan kun',
      'distance_covered': 'Bosib o\'tilgan yo\'l',
      'active_work_time': 'Faol ish vaqti',
      'fuel_change': 'Yoqilg\'i o\'zgarishi',
      'avg_speed': 'O\'rtacha tezlik',
      
      // Soil Analysis
      'soil_analysis': 'Tuproq tahlili',
      'results': 'Natijalar',
      'recommendations': 'Tavsiyalar',
      'ph_level': 'Kislotalilik (pH)',
      'nitrogen': 'Azot (N)',
      'phosphorus': 'Fosfor (P)',
      'potassium': 'Kaliy (K)',
      'moisture': 'Namlik',
      
      // Weather
      'weather_forecast': 'Ob-havo ma\'lumoti',
      'weekly_weather': 'Haftalik ob-havo',
      'wind_speed': 'Shamol tezligi',
      'humidity': 'Namlik',
      'pressure': 'Bosim',
      
      // Listings
      'rental_listings': 'Ijara e\'lonlari',
      'listings_subtitle': 'Bo\'sh turgan jihozlarni ijaraga berish va olish',
      'new_listing': 'Yangi e\'lon',
      'listing_title': 'E\'lon sarlavhasi',
      'machinery_type': 'Texnika/Uskuna turi',
      'cat_all': 'Barchasi',
      'cat_tractor': 'Traktor',
      'cat_plow': 'Plug',
      'cat_chisel': 'Chizel',
      'cat_harvester': 'Kombayn',
      'cat_cultivator': 'Kultivator',
      'cat_seeder': 'Seyalka',
      'cat_other': 'Boshqa',
      'rental_price': 'Ijara narxi',
      'contact_phone': 'Bog\'lanish uchun telefon',
      'details_desc': 'Batafsil tavsif',
      'machinery_img': 'Texnika rasmi (ixtiyoriy)',
      'choose_gallery': 'Galereyadan rasm tanlang',
      'publish_btn': 'E\'lonni chop etish',
      'contact_btn': 'Bog\'lanish',
      'delete_confirm_title': 'E\'lonni o\'chirish',
      'delete_confirm_msg': 'Haqiqatdan ham ushbu e\'lonni o\'chirmoqchimisiz?',
      'delete_yes': 'Ha, o\'chirish',
      'delete_no': 'Yo\'q',
      'delete_success': 'E\'lon muvaffaqiyatli o\'chirildi.',
      'add_success': 'E\'lon muvaffaqiyatli qo\'shildi!',
      'add_error': 'E\'lon qo\'shishda xatolik yuz berdi.',
      'posted_by': 'E\'lon beruvchi',
      'location_not_specified': 'Hudud ko\'rsatilmagan',
      'no_listings': 'E\'lonlar mavjud emas',
      'no_listings_desc': 'Hozircha hech kim e\'lon joylashtirmagan.',
      'no_category_listings': 'Ushbu turdagi texnikalar bo\'yicha e\'lonlar topilmadi.',
      'agreement_price': 'Kelishuv asosida',
      'load_error': 'E\'lonlarni yuklashda xatolik yuz berdi.',
      'retry': 'Qayta urinish',
      'post_listing_btn': 'E\'lon berish',
    },
    'oz': {
      // Common / Auth
      'app_name': 'AgroMind',
      'login_title': 'Тизимга кириш',
      'register_title': 'Рўйхатдан ўтиш',
      'phone_label': 'Телефон рақам',
      'password_label': 'Парол',
      'name_label': 'Исм-фамилия',
      'region_label': 'Вилоят',
      'district_label': 'Туман',
      'login_btn': 'Кириш',
      'register_btn': 'Рўйхатдан ўтиш',
      'no_account': 'Сизда ҳисоб йўқми? Рўйхатдан ўтиш',
      'have_account': 'Сизда аллақачон ҳисоб борми? Кириш',
      'field_required': 'Илтимос, барча майдонларни тўлдиринг',
      'logout': 'Тизимдан Чиқиш',
      'profile_settings': 'Профил ва Созламалар',
      'profile_info': 'Маълумотлар',
      'profile_settings_title': 'Созламалар',
      'app_lang': 'Илова тили',
      'user_role': 'Фермер (Деҳқон)',
      
      // Home Screen / Navigation
      'home': 'Асосий',
      'map_nav': 'Харита',
      'weather_nav': 'Об-ҳаво',
      'chat_nav': 'Мулоқот',
      'listings_nav': 'Ижара',
      'profile_nav': 'Профил',
      'alerts_nav': 'Огоҳлантиришлар',
      'field_map': 'Дала харитаси',
      'weather_card': 'Об-ҳаво',
      'soil_card': 'Тупроқ таҳлили',
      'listings_card': 'Эълонлар',
      'satellite_analysis': 'Сунъий йўлдош таҳлили',
      'select_field': 'Дала танлаш',
      'active_machinery': 'Фаол техникалар',
      'all_fields_map': 'Барча далалар харитада',
      
      // GPS Map / History
      'gps_monitoring': 'GPS Мониторинг',
      'machinery_status': 'Техника ҳолати',
      'history': 'Тарих',
      'today': 'Бугун',
      'yesterday': 'Кеча',
      'two_days_ago': 'Ўтган кун',
      'distance_covered': 'Босиб ўтилган йўл',
      'active_work_time': 'Фаол иш вақти',
      'fuel_change': 'Ёқилғи ўзгариши',
      'avg_speed': 'Ўртача тезлик',
      
      // Soil Analysis
      'soil_analysis': 'Тупроқ таҳлили',
      'results': 'Натижалар',
      'recommendations': 'Тавсиялар',
      'ph_level': 'Кислоталилик (pH)',
      'nitrogen': 'Азот (N)',
      'phosphorus': 'Фосфор (P)',
      'potassium': 'Калий (K)',
      'moisture': 'Намлик',
      
      // Weather
      'weather_forecast': 'Об-ҳаво маълумоti',
      'weekly_weather': 'Ҳафталик об-ҳаво',
      'wind_speed': 'Шамол тезлиги',
      'humidity': 'Намлик',
      'pressure': 'Босим',
      
      // Listings
      'rental_listings': 'Ижара эълонлари',
      'listings_subtitle': 'Бўш турган жиҳозларни ижарага бериш ва олиш',
      'new_listing': 'Янги эълон',
      'listing_title': 'Эълон сарлавҳаси',
      'machinery_type': 'Техника/Ускуна тури',
      'cat_all': 'Барчаси',
      'cat_tractor': 'Трактор',
      'cat_plow': 'Плуг',
      'cat_chisel': 'Чизел',
      'cat_harvester': 'Комбайн',
      'cat_cultivator': 'Культиватор',
      'cat_seeder': 'Сеялка',
      'cat_other': 'Бошқа',
      'rental_price': 'Ижара нархи',
      'contact_phone': 'Боғланиш учун телефон',
      'details_desc': 'Батафсил тавсиф',
      'machinery_img': 'Техника rasmi (ихтиёрий)',
      'choose_gallery': 'Галереядан расм танланг',
      'publish_btn': 'Эълонни чоп etish',
      'contact_btn': 'Боғланиш',
      'delete_confirm_title': 'Эълонни ўчириш',
      'delete_confirm_msg': 'Ҳақиқатан ҳам ушбу эълонни ўчирмоқчимисиз?',
      'delete_yes': 'Ҳа, ўчириш',
      'delete_no': 'Йўқ',
      'delete_success': 'Эълон муваффақиятли ўчирилди.',
      'add_success': 'Эълон муваффақиятли қўшилди!',
      'add_error': 'Эълон қўшишда хатолик юз berdi.',
      'posted_by': 'Эълон берувчи',
      'location_not_specified': 'Ҳудуд кўрсатилмаган',
      'no_listings': 'Эълонлар мавжуд эмас',
      'no_listings_desc': 'Ҳозирча ҳеч ким эълон жойлаштирмаган.',
      'no_category_listings': 'Ушбу турдаги техникалар бўйича эълонлар топилмади.',
      'agreement_price': 'Келишув асосида',
      'load_error': 'Эълонларни юклашда хатолик юз берди.',
      'retry': 'Қайта уриниш',
      'post_listing_btn': 'Эълон бериш',
    },
    'ru': {
      // Common / Auth
      'app_name': 'AgroMind',
      'login_title': 'Вход в систему',
      'register_title': 'Регистрация',
      'phone_label': 'Номер телефона',
      'password_label': 'Пароль',
      'name_label': 'Имя и фамилия',
      'region_label': 'Область',
      'district_label': 'Район',
      'login_btn': 'Войти',
      'register_btn': 'Зарегистрироваться',
      'no_account': 'У вас нет аккаунта? Регистрация',
      'have_account': 'У вас уже есть аккаунт? Войти',
      'field_required': 'Пожалуйста, заполните все поля',
      'logout': 'Выйти из системы',
      'profile_settings': 'Профиль и Настройки',
      'profile_info': 'Данные',
      'profile_settings_title': 'Настройки',
      'app_lang': 'Язык приложения',
      'user_role': 'Фермер (Дехканин)',
      
      // Home Screen / Navigation
      'home': 'Главная',
      'map_nav': 'Карта',
      'weather_nav': 'Погода',
      'chat_nav': 'Чат',
      'listings_nav': 'Аренда',
      'profile_nav': 'Профиль',
      'alerts_nav': 'Предупреждения',
      'field_map': 'Карта поля',
      'weather_card': 'Погода',
      'soil_card': 'Анализ почвы',
      'listings_card': 'Объявления',
      'satellite_analysis': 'Спутниковый анализ',
      'select_field': 'Выбрать поле',
      'active_machinery': 'Активная техника',
      'all_fields_map': 'Все поля на карте',
      
      // GPS Map / History
      'gps_monitoring': 'GPS Мониторинг',
      'machinery_status': 'Статус техники',
      'history': 'История',
      'today': 'Сегодня',
      'yesterday': 'Вчера',
      'two_days_ago': 'Позавчера',
      'distance_covered': 'Пройденный путь',
      'active_work_time': 'Активное время работы',
      'fuel_change': 'Изменение топлива',
      'avg_speed': 'Средняя скорость',
      
      // Soil Analysis
      'soil_analysis': 'Анализ почвы',
      'results': 'Результаты',
      'recommendations': 'Рекомендации',
      'ph_level': 'Кислотность (pH)',
      'nitrogen': 'Азот (N)',
      'phosphorus': 'Фосфор (P)',
      'potassium': 'Калий (K)',
      'moisture': 'Влажность',
      
      // Weather
      'weather_forecast': 'Прогноз погоды',
      'weekly_weather': 'Прогноз на неделю',
      'wind_speed': 'Скорость ветра',
      'humidity': 'Влажность',
      'pressure': 'Давление',
      
      // Listings
      'rental_listings': 'Аренда техники',
      'listings_subtitle': 'Аренда свободной техники и оборудования',
      'new_listing': 'Новое объявление',
      'listing_title': 'Заголовок объявления',
      'machinery_type': 'Тип техники/оборудования',
      'cat_all': 'Все',
      'cat_tractor': 'Трактор',
      'cat_plow': 'Плуг',
      'cat_chisel': 'Чизель',
      'cat_harvester': 'Комбайн',
      'cat_cultivator': 'Культиватор',
      'cat_seeder': 'Сеялка',
      'cat_other': 'Другое',
      'rental_price': 'Стоимость аренды',
      'contact_phone': 'Телефон для связи',
      'details_desc': 'Подробное описание',
      'machinery_img': 'Фото техники (необязательно)',
      'choose_gallery': 'Выбрать фото из галереи',
      'publish_btn': 'Опубликовать объявление',
      'contact_btn': 'Связаться',
      'delete_confirm_title': 'Удалить объявление',
      'delete_confirm_msg': 'Вы действительно хотите удалить это объявление?',
      'delete_yes': 'Да, удалить',
      'delete_no': 'Нет',
      'delete_success': 'Объявление успешно удалено.',
      'add_success': 'Объявление успешно добавлено!',
      'add_error': 'Ошибка при добавлении объявления.',
      'posted_by': 'Опубликовал',
      'location_not_specified': 'Регион не указан',
      'no_listings': 'Объявлений нет',
      'no_listings_desc': 'Пока никто не разместил объявлений.',
      'no_category_listings': 'Объявления по этому типу техники не найдены.',
      'agreement_price': 'По договоренности',
      'load_error': 'Произошла ошибка при загрузке объявлений.',
      'retry': 'Повторить попытку',
      'post_listing_btn': 'Подать объявление',
    }
  };

  static String translate(String key, String locale) {
    return _localizedValues[locale]?[key] ?? key;
  }
}

extension TranslateRef on WidgetRef {
  String tr(String key) {
    final locale = watch(localeProvider);
    return AppTranslations.translate(key, locale);
  }
}
