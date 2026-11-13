import 'package:flutter/material.dart';

import 'api_service.dart';

void main() => runApp(const CiraExpressApp());

class CiraExpressApp extends StatelessWidget {
  const CiraExpressApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'Cira Express',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF087F5B)),
          useMaterial3: true,
          scaffoldBackgroundColor: const Color(0xFFF7F8F7),
        ),
        home: const AccueilPage(),
      );
}

class AccueilPage extends StatefulWidget {
  const AccueilPage({super.key});

  @override
  State<AccueilPage> createState() => _AccueilPageState();
}

class _AccueilPageState extends State<AccueilPage> {
  final ApiService _api = ApiService();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _address = TextEditingController();
  final _phone = TextEditingController();
  final Map<int, int> _cart = {};
  List<ProductModel> _products = [];
  bool _connected = false;
  bool _busy = false;
  String? _error;
  int _tab = 0;

  int get _itemCount => _cart.values.fold(0, (a, b) => a + b);
  double get _total => _products.fold(0, (sum, p) => sum + p.prix * (_cart[p.id] ?? 0));
  String _money(num value) => '${value.toStringAsFixed(0)} FCFA';

  Future<void> _login() async {
    FocusScope.of(context).unfocus();
    setState(() { _busy = true; _error = null; });
    try {
      await _api.login(email: _email.text.trim(), password: _password.text.trim());
      final products = await _api.fetchProducts();
      if (!mounted) return;
      setState(() { _products = products; _connected = true; _busy = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _busy = false; _error = e.toString(); });
    }
  }

  Future<void> _placeOrder() async {
    if (_cart.isEmpty) return;
    if (_address.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      _toast('Renseignez l’adresse et le téléphone de livraison.');
      return;
    }

    setState(() { _busy = true; _error = null; });
    try {
      await _api.saveClientProfile(
        name: _api.userName ?? 'Client',
        phone: _phone.text.trim(),
        address: _address.text.trim(),
      );

      final items = _cart.entries.map((entry) => {
        'produit_id': entry.key,
        'quantite': entry.value,
      }).toList();

      final order = await _api.createOrder(
        address: _address.text.trim(),
        phone: _phone.text.trim(),
        items: items,
      );

      if (!mounted) return;
      setState(() { _cart.clear(); _busy = false; _tab = 0; });
      _toast('Commande #${order['id']} envoyée avec succès.');
    } catch (e) {
      if (!mounted) return;
      setState(() { _busy = false; _error = e.toString(); });
      _toast(e.toString());
    }
  }

  void _toast(String message) => ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(message)));

  void _changeQuantity(ProductModel product, int delta) {
    setState(() {
      final next = (_cart[product.id] ?? 0) + delta;
      if (next <= 0) {
        _cart.remove(product.id);
      } else if (next <= product.stock) {
        _cart[product.id] = next;
      } else {
        _toast('Stock disponible : ${product.stock}');
      }
    });
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _address.dispose();
    _phone.dispose();
    _api.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Cira Express', style: TextStyle(fontWeight: FontWeight.bold)),
          Text('Connectée à Laravel', style: TextStyle(fontSize: 12, fontWeight: FontWeight.normal)),
        ]),
        actions: [
          if (_connected)
            IconButton(
              tooltip: 'Actualiser',
              onPressed: _busy ? null : () async {
                try {
                  final result = await _api.fetchProducts();
                  if (mounted) setState(() => _products = result);
                } catch (e) {
                  if (mounted) _toast(e.toString());
                }
              },
              icon: Badge(label: Text('$_itemCount'), isLabelVisible: _itemCount > 0, child: const Icon(Icons.refresh)),
            ),
        ],
      ),
      body: _connected ? _store() : _loginForm(),
      bottomNavigationBar: _connected
          ? NavigationBar(
              selectedIndex: _tab,
              onDestinationSelected: (index) => setState(() => _tab = index),
              destinations: [
                const NavigationDestination(
                  icon: Icon(Icons.storefront_outlined),
                  selectedIcon: Icon(Icons.storefront),
                  label: 'Catalogue',
                ),
                NavigationDestination(
                  icon: const Icon(Icons.shopping_cart_outlined),
                  selectedIcon: const Icon(Icons.shopping_cart),
                  label: 'Panier ($_itemCount)',
                ),
              ],
            )
          : null,
    );
  }

  Widget _loginForm() => Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const Icon(Icons.delivery_dining, size: 76, color: Color(0xFF087F5B)),
              const SizedBox(height: 12),
              const Text('Connexion', textAlign: TextAlign.center, style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
              const SizedBox(height: 24),
              TextField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(labelText: 'Adresse e-mail', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _password,
                obscureText: true,
                onSubmitted: (_) => _login(),
                decoration: const InputDecoration(labelText: 'Mot de passe', border: OutlineInputBorder()),
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(_error!, style: const TextStyle(color: Colors.red)),
                ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: _busy ? null : _login,
                icon: const Icon(Icons.login),
                label: Text(_busy ? 'Connexion...' : 'Se connecter'),
              ),
              const SizedBox(height: 14),
              const Text(
                'Utilisez un compte déjà créé dans l’API. Emulateur Android : http://10.0.2.2:8000/api',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey),
              ),
            ]),
          ),
        ),
      );

  Widget _store() => _tab == 0 ? _catalogue() : _cartView();

  Widget _catalogue() {
    if (_busy && _products.isEmpty) return const Center(child: CircularProgressIndicator());
    if (_products.isEmpty) return const Center(child: Text('Aucun produit actif dans le catalogue.'));

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(color: const Color(0xFFE1F3EA), borderRadius: BorderRadius.circular(18)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Vos envies, livrées chez vous', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              Text('Connecté${_api.userName == null ? '' : ' en tant que ${_api.userName}'} · ${_products.length} produits'),
            ],
          ),
        ),
        const SizedBox(height: 18),
        ..._products.where((p) => p.statut == 'actif' && p.stock > 0).map(_productCard),
      ],
    );
  }

  Widget _productCard(ProductModel product) => Card(
        margin: const EdgeInsets.only(bottom: 12),
        child: ListTile(
          contentPadding: const EdgeInsets.all(12),
          leading: CircleAvatar(
            backgroundColor: const Color(0xFFE9F7EE),
            child: const Icon(Icons.fastfood, color: Color(0xFF087F5B)),
          ),
          title: Text(product.nom, style: const TextStyle(fontWeight: FontWeight.bold)),
          subtitle: Text('${product.description}\n${_money(product.prix)} · Stock ${product.stock}'),
          isThreeLine: true,
          trailing: IconButton.filledTonal(
            onPressed: () => _changeQuantity(product, 1),
            icon: const Icon(Icons.add),
          ),
        ),
      );

  Widget _cartView() {
    final items = _cart.entries.where((e) => e.value > 0).toList();
    if (items.isEmpty) return const Center(child: Text('Votre panier est vide.'));

    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: items.map((entry) {
              final product = _products.firstWhere((p) => p.id == entry.key);
              return Card(
                child: ListTile(
                  title: Text(product.nom),
                  subtitle: Text('${entry.value} × ${_money(product.prix)}'),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(onPressed: () => _changeQuantity(product, -1), icon: const Icon(Icons.remove_circle_outline)),
                      Text('${entry.value}'),
                      IconButton(onPressed: () => _changeQuantity(product, 1), icon: const Icon(Icons.add_circle_outline)),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: Column(
            children: [
              TextField(
                controller: _address,
                decoration: const InputDecoration(labelText: 'Adresse de livraison', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'Téléphone du destinataire', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Sous-total'),
                  Text(_money(_total), style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _busy ? null : _placeOrder,
                  icon: const Icon(Icons.check_circle_outline),
                  label: Text(_busy ? 'Envoi...' : 'Confirmer la commande'),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
