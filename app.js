const products = [
  { id: 1, name: "Téléphone Android 128 Go", category: "Électronique", price: 189000, oldPrice: 215000, emoji: "📱" },
  { id: 2, name: "Casque Bluetooth sans fil", category: "Électronique", price: 28000, oldPrice: 35000, emoji: "🎧" },
  { id: 3, name: "Montre intelligente Sport", category: "Mode", price: 32000, oldPrice: 40000, emoji: "⌚" },
  { id: 4, name: "Sac à dos urbain", category: "Mode", price: 24000, oldPrice: 29000, emoji: "🎒" },
  { id: 5, name: "Chaussures de course", category: "Mode", price: 42000, oldPrice: 50000, emoji: "👟" },
  { id: 6, name: "Robot mélangeur multifonction", category: "Maison", price: 65000, oldPrice: 78000, emoji: "🥣" },
  { id: 7, name: "Machine à café compacte", category: "Maison", price: 95000, oldPrice: 110000, emoji: "☕" },
  { id: 8, name: "Batterie portable 20 000 mAh", category: "Électronique", price: 25000, oldPrice: 31000, emoji: "🔋" }
];

const categories = [
  { name: "Tout", emoji: "✨" },
  { name: "Électronique", emoji: "📱" },
  { name: "Mode", emoji: "👕" },
  { name: "Maison", emoji: "🏠" },
  { name: "Beauté", emoji: "💄" }
];

let activeCategory = "Tout";
let cart = JSON.parse(localStorage.getItem("cira-cart") || "[]");
let orders = JSON.parse(localStorage.getItem("cira-orders") || "[]");
let messages = JSON.parse(localStorage.getItem("cira-messages") || "[]");

const main = document.querySelector("#main-content");
const toast = document.querySelector("#toast");

function formatPrice(value) {
  return new Intl.NumberFormat("fr-FR").format(value) + " FCFA";
}

function saveCart() {
  localStorage.setItem("cira-cart", JSON.stringify(cart));
  updateCartCount();
}

function updateCartCount() {
  document.querySelector("#cart-count").textContent =
    cart.reduce((sum, item) => sum + item.quantity, 0);
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(showToast.timeout);
  showToast.timeout = setTimeout(() => toast.classList.remove("show"), 2200);
}

function setView(view) {
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.toggle("active", item.dataset.view === view);
  });
  if (view === "home") renderHome();
  if (view === "search") renderSearch();
  if (view === "orders") renderOrders();
  if (view === "profile") renderProfile();
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function productCard(product) {
  return `
    <article class="product-card">
      <button class="product-image" data-product="${product.id}" aria-label="Voir ${product.name}">
        ${product.emoji}
      </button>
      <div class="product-info">
        <h3 class="product-name">${product.name}</h3>
        <div>
          <span class="price">${formatPrice(product.price)}</span>
          <span class="old-price">${formatPrice(product.oldPrice)}</span>
        </div>
        <button class="add-button" data-add="${product.id}">Ajouter au panier</button>
      </div>
    </article>`;
}

function renderHome() {
  const visibleProducts = activeCategory === "Tout"
    ? products
    : products.filter((product) => product.category === activeCategory);

  main.innerHTML = `
    <section class="hero">
      <h1>Vos produits préférés, livrés vite</h1>
      <p>Trouvez les meilleures offres et commandez facilement avec Cira Express.</p>
    </section>

    <div class="section-title"><h2>Catégories</h2></div>
    <div class="category-row">
      ${categories.map((category) => `
        <button class="category ${activeCategory === category.name ? "active" : ""}" data-category="${category.name}">
          <span class="emoji">${category.emoji}</span>
          <span>${category.name}</span>
        </button>`).join("")}
    </div>

    <div class="section-title"><h2>Produits populaires</h2></div>
    <div class="product-grid">${visibleProducts.map(productCard).join("")}</div>`;
}

function renderSearch() {
  main.innerHTML = `
    <div class="section-title"><h2>Rechercher un produit</h2></div>
    <input class="search-box" id="search-input" type="search" placeholder="Nom ou catégorie..." autofocus />
    <div class="product-grid" id="search-results"></div>`;

  const input = document.querySelector("#search-input");
  const results = document.querySelector("#search-results");

  function search() {
    const query = input.value.trim().toLowerCase();
    const matches = products.filter((product) =>
      `${product.name} ${product.category}`.toLowerCase().includes(query));
    results.innerHTML = matches.length
      ? matches.map(productCard).join("")
      : '<div class="empty">Aucun produit trouvé.</div>';
  }

  input.addEventListener("input", search);
  search();
}

function renderCart() {
  document.querySelectorAll(".nav-item").forEach((item) => item.classList.remove("active"));

  if (!cart.length) {
    main.innerHTML = `
      <div class="section-title"><h2>Mon panier</h2></div>
      <div class="panel empty">Votre panier est vide.</div>`;
    return;
  }

  const total = cart.reduce((sum, item) => {
    const product = products.find((entry) => entry.id === item.id);
    return sum + product.price * item.quantity;
  }, 0);

  main.innerHTML = `
    <div class="section-title"><h2>Mon panier</h2></div>
    <div class="panel">
      ${cart.map((item) => {
        const product = products.find((entry) => entry.id === item.id);
        return `
          <div class="cart-item">
            <div class="cart-thumb">${product.emoji}</div>
            <div>
              <div class="cart-name">${product.name}</div>
              <div class="quantity">
                <button data-quantity="${product.id}" data-change="-1">−</button>
                <strong>${item.quantity}</strong>
                <button data-quantity="${product.id}" data-change="1">+</button>
              </div>
            </div>
            <strong>${formatPrice(product.price * item.quantity)}</strong>
          </div>`;
      }).join("")}
      <div class="summary"><span>Total</span><strong>${formatPrice(total)}</strong></div>
    </div>
    <button class="checkout" id="checkout-button">Commander maintenant</button>`;
}

function renderOrders() {
  main.innerHTML = `
    <div class="section-title"><h2>Mes commandes</h2></div>
    ${orders.length ? `
      <div class="panel">
        ${orders.map((order) => `
          <div class="order-card">
            <span>📦</span>
            <div>
              <strong>Commande ${order.id}</strong>
              <div>${order.items} article(s) · ${formatPrice(order.total)}</div>
              <div class="status">${order.status}</div>
            </div>
          </div>`).join("")}
      </div>` : '<div class="panel empty">Vous n’avez pas encore passé de commande.</div>'}`;
}

function renderProfile() {
  main.innerHTML = `
    <div class="section-title"><h2>Mon compte</h2></div>
    <div class="panel profile-head">
      <div class="avatar">👤</div>
      <div>
        <strong>Bienvenue sur Cira Express</strong>
        <div style="color: var(--muted); margin-top:4px">Créer mon compte</div>
      </div>
    </div>
    <div class="panel">
      <button class="menu-row" data-view="orders"><span>Mes commandes</span><span>›</span></button>
      <button class="menu-row" data-chat="true"><span>Contacter le vendeur</span><span>›</span></button>
      <button class="menu-row" data-review="true"><span>Mes avis</span><span>›</span></button>
    </div>`;
}

function renderChat() {
  document.querySelectorAll(".nav-item").forEach((item) => item.classList.remove("active"));
  main.innerHTML = `
    <div class="section-title"><h2>Support Cira Express</h2></div>
    <div class="panel chat">
      <div class="chat-messages" id="chat-messages">
        <div class="bubble">Bonjour ! Comment pouvons-nous vous aider ?</div>
        ${messages.map((message) => `<div class="bubble me">${escapeHtml(message)}</div>`).join("")}
      </div>
      <form class="chat-form" id="chat-form">
        <input id="chat-input" aria-label="Votre message" placeholder="Écrivez votre message..." required />
        <button>Envoyer</button>
      </form>
    </div>`;

  const messagesContainer = document.querySelector("#chat-messages");
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function escapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = value;
  return element.innerHTML;
}

function addToCart(id) {
  const existing = cart.find((item) => item.id === id);
  if (existing) existing.quantity += 1;
  else cart.push({ id, quantity: 1 });
  saveCart();
  showToast("Produit ajouté au panier");
}

document.addEventListener("click", (event) => {
  const navItem = event.target.closest(".nav-item");
  if (navItem) setView(navItem.dataset.view);

  const category = event.target.closest("[data-category]");
  if (category) {
    activeCategory = category.dataset.category;
    renderHome();
  }

  const addButton = event.target.closest("[data-add]");
  if (addButton) addToCart(Number(addButton.dataset.add));

  const quantityButton = event.target.closest("[data-quantity]");
  if (quantityButton) {
    const id = Number(quantityButton.dataset.quantity);
    const change = Number(quantityButton.dataset.change);
    const item = cart.find((entry) => entry.id === id);
    item.quantity += change;
    if (item.quantity <= 0) cart = cart.filter((entry) => entry.id !== id);
    saveCart();
    renderCart();
  }

  if (event.target.closest("#cart-button")) renderCart();
  if (event.target.closest("#home-button")) setView("home");
  if (event.target.closest("[data-chat]")) renderChat();
  if (event.target.closest("[data-review]")) showToast("La notation du produit sera disponible prochainement");

  const profileView = event.target.closest('.menu-row[data-view]');
  if (profileView) setView(profileView.dataset.view);

  const checkout = event.target.closest("#checkout-button");
  if (checkout) {
    const total = cart.reduce((sum, item) => {
      const product = products.find((entry) => entry.id === item.id);
      return sum + product.price * item.quantity;
    }, 0);
    const items = cart.reduce((sum, item) => sum + item.quantity, 0);
    orders.unshift({ id: "CE-" + Date.now().toString().slice(-6), items, total, status: "En préparation" });
    localStorage.setItem("cira-orders", JSON.stringify(orders));
    cart = [];
    saveCart();
    showToast("Commande enregistrée");
    renderOrders();
  }
});

document.addEventListener("submit", (event) => {
  if (event.target.id !== "chat-form") return;
  event.preventDefault();
  const input = document.querySelector("#chat-input");
  const value = input.value.trim();
  if (!value) return;
  messages.push(value);
  localStorage.setItem("cira-messages", JSON.stringify(messages));
  renderChat();
});

updateCartCount();
renderHome();

if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => navigator.serviceWorker.register("sw.js"));
}
