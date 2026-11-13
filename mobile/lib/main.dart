import 'package:flutter/material.dart';

void main() {
  runApp(const LivraisonApp());
}

class LivraisonApp extends StatelessWidget {
  const LivraisonApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Livraison Express',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF087F5B)),
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFFF7F8F7),
      ),
      home: const AccueilPage(),
    );
  }
}

class Produit {
  const Produit({
    required this.nom,
    required this.description,
    required this.prix,
    required this.emoji,
    required this.categorie,
  });

  final String nom;
  final String description;
  final int prix;
  final String emoji;
  final String categorie;
}

const produits = <Produit>[
  Produit(nom: 'Poulet braisé', description: 'Servi avec accompagnement', prix: 4500, emoji: '🍗', categorie: 'Repas'),
  Produit(nom: 'Riz sauté', description: 'Riz parfumé aux légumes', prix: 2500, emoji: '🍚', categorie: 'Repas'),
  Produit(nom: 'Jus frais', description: 'Jus naturel du jour', prix: 1200, emoji: '🥤', categorie: 'Boissons'),
  Produit(nom: 'Beignets', description: 'Préparés chaque matin', prix: 1000, emoji: '🍩', categorie: 'Desserts'),
];

class AccueilPage extends StatefulWidget {
  const AccueilPage({super.key});

  @override
  State<AccueilPage> createState() => _AccueilPageState();
}

class _AccueilPageState extends State<AccueilPage> {
  final Map<Produit, int> panier = {};
  int onglet = 0;

  int get nombreArticles => panier.values.fold(0, (total, qte) => total + qte);
  int get total => panier.entries.fold(0, (somme, item) => somme + item.key.prix * item.value);

  String prix(int valeur) => '${valeur.toString()} FCFA';

  void ajouter(Produit produit) {
    setState(() => panier[produit] = (panier[produit] ?? 0) + 1);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text('${produit.nom} ajouté au panier'), duration: const Duration(seconds: 1)));
  }

  void retirer(Produit produit) {
    setState(() {
      final qte = panier[produit] ?? 0;
      if (qte <= 1) {
        panier.remove(produit);
      } else {
        panier[produit] = qte - 1;
      }
    });
  }

  void confirmerCommande() {
    if (panier.isEmpty) return;
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Commande enregistrée'),
        content: Text('Votre commande de ${prix(total)} est prête à être envoyée. La connexion au serveur sera ajoutée ensuite.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Fermer')),
          FilledButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() => panier.clear());
              ScaffoldMessenger.of(this.context).showSnackBar(const SnackBar(content: Text('Prototype : aucune commande réelle n’a été envoyée.')));
            },
            child: const Text('Terminer'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Livraison Express', style: TextStyle(fontWeight: FontWeight.bold)),
          Text('Livraison à Douala', style: TextStyle(fontSize: 12, fontWeight: FontWeight.normal)),
        ]),
        actions: [
          IconButton(
            tooltip: 'Panier',
            onPressed: () => setState(() => onglet = 1),
            icon: Badge(label: Text('$nombreArticles'), isLabelVisible: nombreArticles > 0, child: const Icon(Icons.shopping_bag_outlined)),
          ),
        ],
      ),
      body: onglet == 0 ? _catalogue() : _panier(),
      bottomNavigationBar: NavigationBar(
        selectedIndex: onglet,
        onDestinationSelected: (index) => setState(() => onglet = index),
        destinations: [
          const NavigationDestination(icon: Icon(Icons.storefront_outlined), selectedIcon: Icon(Icons.storefront), label: 'Catalogue'),
          NavigationDestination(icon: const Icon(Icons.shopping_cart_outlined), selectedIcon: const Icon(Icons.shopping_cart), label: 'Panier${nombreArticles > 0 ? ' ($nombreArticles)' : ''}'),
        ],
      ),
    );
  }

  Widget _catalogue() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(color: const Color(0xFFE1F3EA), borderRadius: BorderRadius.circular(20)),
          child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Vos envies, livrées chez vous', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            SizedBox(height: 6),
            Text('Choisissez vos articles et ajoutez-les au panier.'),
          ]),
        ),
        const SizedBox(height: 22),
        const Text('Nos produits', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 10),
        ...produits.map(_carteProduit),
      ],
    );
  }

  Widget _carteProduit(Produit produit) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(children: [
          Container(
            width: 64,
            height: 64,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: const Color(0xFFF0F4F1), borderRadius: BorderRadius.circular(16)),
            child: Text(produit.emoji, style: const TextStyle(fontSize: 32)),
          ),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(produit.nom, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Text(produit.description, style: TextStyle(color: Colors.grey.shade700)),
            const SizedBox(height: 5),
            Text(prix(produit.prix), style: const TextStyle(color: Color(0xFF087F5B), fontWeight: FontWeight.bold)),
          ])),
          IconButton.filledTonal(onPressed: () => ajouter(produit), icon: const Icon(Icons.add)),
        ]),
      ),
    );
  }

  Widget _panier() {
    if (panier.isEmpty) {
      return const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.shopping_bag_outlined, size: 64, color: Colors.grey),
        SizedBox(height: 12),
        Text('Votre panier est vide', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
        Text('Ajoutez des produits depuis le catalogue.'),
      ]));
    }

    return Column(children: [
      Expanded(child: ListView(
        padding: const EdgeInsets.all(16),
        children: panier.entries.map((item) => Card(
          child: ListTile(
            leading: Text(item.key.emoji, style: const TextStyle(fontSize: 28)),
            title: Text(item.key.nom),
            subtitle: Text('${item.value} × ${prix(item.key.prix)}'),
            trailing: Row(mainAxisSize: MainAxisSize.min, children: [
              IconButton(onPressed: () => retirer(item.key), icon: const Icon(Icons.remove_circle_outline)),
              Text('${item.value}', style: const TextStyle(fontWeight: FontWeight.bold)),
              IconButton(onPressed: () => ajouter(item.key), icon: const Icon(Icons.add_circle_outline)),
            ]),
          ),
        )).toList(),
      )),
      SafeArea(child: Container(
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 16),
        decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE5E7E5)))),
        child: Column(children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Text('Total provisoire', style: TextStyle(fontSize: 16)),
            Text(prix(total), style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ]),
          const SizedBox(height: 12),
          SizedBox(width: double.infinity, child: FilledButton.icon(
            onPressed: confirmerCommande,
            icon: const Icon(Icons.check_circle_outline),
            label: const Text('Passer la commande'),
          )),
        ]),
      )),
    ]);
  }
}
