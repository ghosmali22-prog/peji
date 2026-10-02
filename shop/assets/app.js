(() => {
  const KEY = "peji-cart";
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const money = (n) => window.SHOP.currency + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

  function load() {
    try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
  }
  function save(cart) {
    try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {}
    render(cart);
  }
  const total = (cart) => cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  function lineHtml(item, i, editable) {
    return `<div class="line">
      <img src="${esc(item.image)}" alt="">
      <div class="line-info">
        <b>${esc(item.name)}</b>
        <span class="muted">${item.size ? "Size " + esc(item.size) + " · " : ""}${money(item.price)}</span>
        ${editable ? `<div class="qty">
          <button type="button" data-qty="${i}" data-step="-1" aria-label="One less">−</button>
          <span>${item.qty}</span>
          <button type="button" data-qty="${i}" data-step="1" aria-label="One more">+</button>
          <button type="button" class="link" data-remove="${i}">Remove</button>
        </div>` : `<span class="muted">Qty ${item.qty}</span>`}
      </div>
      <b>${money(item.price * item.qty)}</b>
    </div>`;
  }

  function render(cart) {
    const count = cart.reduce((n, item) => n + item.qty, 0);
    $$("[data-cart-count]").forEach((el) => (el.textContent = count));
    $$("[data-cart-total]").forEach((el) => (el.textContent = money(total(cart))));

    const list = $("[data-cart-items]");
    if (list) {
      list.innerHTML = cart.length
        ? cart.map((item, i) => lineHtml(item, i, true)).join("")
        : `<p class="empty">Your bag is empty.<br>Find something you like and add it here.</p>`;
    }
    const link = $("[data-checkout-link]");
    if (link) link.classList.toggle("disabled", !cart.length);

    const summary = $("[data-summary-items]");
    if (summary) {
      summary.innerHTML = cart.length
        ? cart.map((item, i) => lineHtml(item, i, false)).join("")
        : `<p class="empty">Your bag is empty. <a href="index.php">Go to the shop</a></p>`;
    }
    const input = $("[data-cart-input]");
    if (input) input.value = JSON.stringify(cart.map(({ id, size, qty }) => ({ id, size, qty })));
  }

  // Drawer
  const drawer = $("#cart");
  const backdrop = $(".drawer-backdrop");
  function openCart() { drawer.hidden = false; backdrop.hidden = false; requestAnimationFrame(() => drawer.classList.add("open")); }
  function closeCart() { drawer.classList.remove("open"); backdrop.hidden = true; setTimeout(() => (drawer.hidden = true), 250); }
  $$("[data-open-cart]").forEach((b) => b.addEventListener("click", openCart));
  $$("[data-close-cart]").forEach((b) => b.addEventListener("click", closeCart));
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !drawer.hidden) closeCart(); });

  document.addEventListener("click", (e) => {
    const qtyBtn = e.target.closest("[data-qty]");
    const removeBtn = e.target.closest("[data-remove]");
    if (!qtyBtn && !removeBtn) return;
    const cart = load();
    if (qtyBtn) {
      const item = cart[+qtyBtn.dataset.qty];
      if (item) item.qty = Math.max(1, Math.min(20, item.qty + +qtyBtn.dataset.step));
    } else {
      cart.splice(+removeBtn.dataset.remove, 1);
    }
    save(cart);
  });

  // Add to bag
  let toastTimer;
  function toast(text) {
    const el = $("[data-toast]");
    el.textContent = text; el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (el.hidden = true), 2200);
  }
  $$("[data-add-to-cart]").forEach((form) =>
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const d = form.dataset;
      const size = (new FormData(form).get("size")) || "";
      const cart = load();
      const existing = cart.find((item) => item.id === +d.id && item.size === size);
      if (existing) existing.qty = Math.min(20, existing.qty + 1);
      else cart.push({ id: +d.id, name: d.name, price: +d.price, image: d.image, size, qty: 1 });
      save(cart);
      toast(`Added ${d.name}${size ? " (" + size + ")" : ""} to your bag`);
      openCart();
    })
  );

  // Checkout
  if ($("[data-clear-cart]")) save([]);
  const checkout = $("[data-checkout-form]");
  if (checkout) {
    checkout.addEventListener("submit", (e) => {
      if (!load().length) { e.preventDefault(); toast("Your bag is empty"); }
    });
  }

  render(load());
})();
