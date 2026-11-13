import 'dart:convert';

import 'package:http/http.dart' as http;

class ProductModel {
  const ProductModel({
    required this.id,
    required this.nom,
    required this.description,
    required this.prix,
    required this.stock,
    required this.statut,
    this.imageUrl,
  });

  final int id;
  final String nom;
  final String description;
  final double prix;
  final int stock;
  final String statut;
  final String? imageUrl;

  factory ProductModel.fromJson(Map<String, dynamic> json) => ProductModel(
        id: int.tryParse('${json['id']}') ?? 0,
        nom: '${json['nom'] ?? 'Produit'}',
        description: '${json['description'] ?? ''}',
        prix: double.tryParse('${json['prix']}') ?? 0,
        stock: int.tryParse('${json['stock']}') ?? 0,
        statut: '${json['statut'] ?? 'actif'}',
        imageUrl: json['image_url']?.toString(),
      );
}

class ApiService {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  final http.Client _client = http.Client();
  String? _token;
  String? _userName;

  String? get userName => _userName;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<void> login({required String email, required String password}) async {
    final response = await _client
        .post(
          Uri.parse('$baseUrl/auth/login'),
          headers: _headers,
          body: jsonEncode({'email': email, 'password': password}),
        )
        .timeout(const Duration(seconds: 20));

    final data = _decode(response);
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(_message(data, 'Connexion impossible'), response.statusCode);
    }

    _token = data['token']?.toString();
    _userName = (data['user'] is Map ? data['user']['name'] : null)?.toString();
    if (_token == null || _token!.isEmpty) {
      throw const ApiException('Le serveur n’a pas retourné de jeton.');
    }
  }

  Future<List<ProductModel>> fetchProducts() async {
    final response = await _client
        .get(Uri.parse('$baseUrl/produits'), headers: _headers)
        .timeout(const Duration(seconds: 20));
    final data = _decode(response);
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(_message(data, 'Chargement des produits impossible'), response.statusCode);
    }

    final rows = (data['data'] as List?) ?? const [];
    return rows
        .whereType<Map>()
        .map((row) => ProductModel.fromJson(Map<String, dynamic>.from(row)))
        .toList();
  }

  Future<void> saveClientProfile({required String name, required String phone, required String address}) async {
    final response = await _client
        .post(
          Uri.parse('$baseUrl/clients/profile'),
          headers: _headers,
          body: jsonEncode({'nom': name, 'telephone': phone, 'adresse': address}),
        )
        .timeout(const Duration(seconds: 20));

    final data = _decode(response);
    if (response.statusCode < 200 || response.statusCode >= 300 || data['success'] != true) {
      throw ApiException(_message(data, 'Enregistrement du profil impossible'), response.statusCode);
    }
  }

  Future<Map<String, dynamic>> createOrder({
    required String address,
    required String phone,
    required List<Map<String, int>> items,
  }) async {
    final response = await _client
        .post(
          Uri.parse('$baseUrl/commandes'),
          headers: _headers,
          body: jsonEncode({
            'adresse_livraison': address,
            'telephone_destinataire': phone,
            'items': items,
          }),
        )
        .timeout(const Duration(seconds: 20));

    final data = _decode(response);
    if (response.statusCode != 201 || data['success'] != true) {
      throw ApiException(_message(data, 'La commande n’a pas pu être créée'), response.statusCode);
    }

    return Map<String, dynamic>.from(data['data'] as Map);
  }

  Map<String, dynamic> _decode(http.Response response) {
    try {
      final value = jsonDecode(response.body);
      if (value is Map<String, dynamic>) return value;
    } on FormatException {
      // ignore and throw below
    }
    throw ApiException('Réponse serveur illisible (HTTP ${response.statusCode}).', response.statusCode);
  }

  String _message(Map<String, dynamic> data, String fallback) {
    if (data['message'] != null) return data['message'].toString();
    if (data['errors'] is Map) {
      final errors = (data['errors'] as Map).values.expand((value) => value is List ? value : [value]);
      if (errors.isNotEmpty) return errors.first.toString();
    }
    return fallback;
  }

  void dispose() => _client.close();
}

class ApiException implements Exception {
  const ApiException(this.message, [this.statusCode]);

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}
