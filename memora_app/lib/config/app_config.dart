/// Konfigurasi global aplikasi Memora
class AppConfig {
  /// URL backend Laravel yang sudah di-deploy ke production.
  /// Digunakan sebagai nilai default di semua screen.
  static const String baseUrl = 'https://memora.rapip.my.id';

  /// Endpoint API login
  static const String loginEndpoint = '$baseUrl/api/auth/login';

  /// Endpoint API register
  static const String registerEndpoint = '$baseUrl/api/auth/register';
}
