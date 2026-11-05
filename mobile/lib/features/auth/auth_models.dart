import '../../core/json.dart';

class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    this.role = 'staff',
  });

  final int id;
  final String name;
  final String email;
  final String role;

  bool get isAdmin => role == 'admin';

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
    id: asInt(json['id']),
    name: asString(json['name']) ?? '',
    email: asString(json['email']) ?? '',
    role: asString(json['role']) ?? 'staff',
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'role': role,
  };
}

/// A signed-in session: which server, which token, who.
class Session {
  const Session({
    required this.baseUrl,
    required this.token,
    required this.user,
  });

  final String baseUrl;
  final String token;
  final AppUser user;
}
