document.addEventListener("DOMContentLoaded", () => {
  const center = document.querySelector(".notification-center"); if (!center) return;
  const bell = center.querySelector(".notification-bell"), panel = center.querySelector(".notification-panel"), list = center.querySelector(".notification-list"), badge = center.querySelector(".notification-count"); let registration = null;
  if ("serviceWorker" in navigator) navigator.serviceWorker.register("service-worker.js").then(value => { registration = value; }).catch(() => {});
  const mark = (key, dismiss = false) => fetch("request/notifications.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ key, dismiss }), keepalive: true });
  const iconFor = item => item.type === "announcement" ? "fa-bullhorn" : item.type === "shop_order" ? "fa-cart-shopping" : "fa-file-invoice-dollar";
  async function systemNotify(item) {
    if (!("Notification" in window) || item.read || item.type !== "shop_order" || Notification.permission !== "granted") return;
    const key = `fidest-system-notification:${item.key}`, lastSent = Number(localStorage.getItem(key) || 0), retryDelay = 5 * 60 * 1000;
    if (Date.now() - lastSent < retryDelay) return; localStorage.setItem(key, String(Date.now()));
    const options = { body: item.message, icon: "img/logo_fidest.png", badge: "img/logo_fidest.png", tag: item.key, data: { url: item.url, key: item.key }, requireInteraction: item.level === "warning", renotify: true };
    if (registration) await registration.showNotification(item.title, options); else new Notification(item.title, options);
  }
  function showToast(item) {
    if (!item || item.read || sessionStorage.getItem(`fidest-toast:${item.key}`)) return; sessionStorage.setItem(`fidest-toast:${item.key}`, "1");
    const toast = document.createElement("div"); toast.className = "billing-toast"; toast.innerHTML = `<i class="fa-solid ${iconFor(item)}"></i>`;
    const content = document.createElement("div"), title = document.createElement("strong"), message = document.createElement("div"); title.textContent = item.title || ""; message.textContent = item.message || ""; content.append(title, message); toast.append(content); document.body.append(toast);
    toast.onclick = () => { mark(item.key); window.location.href = item.url || "#"; }; setTimeout(() => toast.classList.add("show"), 300); setTimeout(() => { toast.classList.remove("show"); setTimeout(() => toast.remove(), 400); }, 7500);
  }
  async function loadNotifications(showPopup = false) {
    try {
      const response = await fetch("request/notifications.php", { headers: { Accept: "application/json" }, cache: "no-store" }); if (!response.ok) return; const data = await response.json();
      badge.textContent = data.unread; badge.classList.toggle("show", data.unread > 0); list.replaceChildren();
      if (!data.items.length) { const empty = document.createElement("div"); empty.className = "notification-empty"; empty.innerHTML = '<i class="fa-regular fa-circle-check"></i><br>Aucune notification'; list.append(empty); }
      data.items.forEach(item => {
        const row = document.createElement("a"); row.className = `notification-item ${item.level}${item.read ? " is-read" : ""}`; row.href = item.url || "#";
        const icon = document.createElement("span"); icon.className = "notification-item__icon"; icon.innerHTML = `<i class="fa-solid ${iconFor(item)}"></i>`;
        const content = document.createElement("span"), title = document.createElement("strong"), message = document.createElement("small"); title.textContent = item.title || ""; message.textContent = item.message || ""; content.append(title, message);
        const dismiss = document.createElement("button"); dismiss.className = "notification-dismiss"; dismiss.type = "button"; dismiss.title = "Ignorer pour aujourd’hui"; dismiss.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        row.append(icon, content, dismiss); row.addEventListener("click", () => mark(item.key)); dismiss.addEventListener("click", event => { event.preventDefault(); event.stopPropagation(); mark(item.key, true).then(() => loadNotifications()); }); list.append(row);
      });
      const firstUnread = data.items.find(item => !item.read); if (showPopup) showToast(firstUnread); data.items.forEach(systemNotify);
    } catch (error) { console.error("Impossible de charger les notifications.", error); }
  }
  bell.addEventListener("click", async event => { event.preventDefault(); panel.classList.toggle("open"); if ("Notification" in window && Notification.permission === "default") { const permission = await Notification.requestPermission(); if (permission === "granted") loadNotifications(); } });
  document.addEventListener("click", event => { if (!center.contains(event.target)) panel.classList.remove("open"); }); loadNotifications(true); window.setInterval(() => loadNotifications(true), 60000);
});
