document.addEventListener("DOMContentLoaded", async () => {
  const center = document.querySelector(".notification-center");
  if (!center) return;
  const bell = center.querySelector(".notification-bell");
  const panel = center.querySelector(".notification-panel");
  const list = center.querySelector(".notification-list");
  const badge = center.querySelector(".notification-count");

  const mark = (key, dismiss = false) =>
    fetch("request/notifications.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ key, dismiss }),
    });

  try {
    const response = await fetch("request/notifications.php", {
      headers: { Accept: "application/json" },
    });
    if (!response.ok) return;
    const data = await response.json();
    badge.textContent = data.unread;
    badge.classList.toggle("show", data.unread > 0);
    list.replaceChildren();
    if (!data.items.length) {
      const empty = document.createElement("div");
      empty.className = "notification-empty";
      empty.innerHTML =
        '<i class="fa-regular fa-circle-check"></i><br>Aucune notification';
      list.append(empty);
    }
    data.items.forEach((item) => {
      const row = document.createElement("a");
      row.className = `notification-item ${item.level}`;
      row.href = item.url || "#";
      const icon = document.createElement("span");
      icon.className = "notification-item__icon";
      icon.innerHTML = `<i class="fa-solid ${item.type === "announcement" ? "fa-bullhorn" : "fa-file-invoice-dollar"}"></i>`;
      const content = document.createElement("span");
      const title = document.createElement("strong");
      title.textContent = item.title || "";
      const message = document.createElement("small");
      message.textContent = item.message || "";
      content.append(title, message);
      const dismiss = document.createElement("button");
      dismiss.className = "notification-dismiss";
      dismiss.type = "button";
      dismiss.title = "Ignorer";
      dismiss.innerHTML = '<i class="fa-solid fa-xmark"></i>';
      row.append(icon, content, dismiss);
      row.addEventListener("click", () => mark(item.key));
      dismiss.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        mark(item.key, true);
        row.remove();
      });
      list.append(row);
    });
    if (data.items[0]) {
      const first = data.items[0];
      const toast = document.createElement("div");
      toast.className = "billing-toast";
      const icon = document.createElement("i");
      icon.className = `fa-solid ${first.type === "announcement" ? "fa-bullhorn" : "fa-bell"}`;
      const content = document.createElement("div");
      const title = document.createElement("strong");
      title.textContent = first.title || "";
      const message = document.createElement("div");
      message.textContent = first.message || "";
      content.append(title, message);
      toast.append(icon, content);
      document.body.append(toast);
      setTimeout(() => toast.classList.add("show"), 500);
      setTimeout(() => toast.classList.remove("show"), 6500);
    }
  } catch (error) {
    console.error("Impossible de charger les notifications.", error);
  }

  bell.addEventListener("click", (event) => {
    event.preventDefault();
    panel.classList.toggle("open");
  });
  document.addEventListener("click", (event) => {
    if (!center.contains(event.target)) panel.classList.remove("open");
  });
});
