document.addEventListener("DOMContentLoaded", () => {
  const page =
    document.body.dataset.page ||
    location.pathname.split("/").pop() ||
    "dashboard.php";
  const guides = {
    "dashboard.php": [
      "Accueil",
      "Consultez les indicateurs clés, les derniers devis et les échéances importantes.",
    ],
    "generer_devis.php": [
      "Nouveau devis",
      "Sélectionnez un client et une offre, ajoutez les lignes, puis vérifiez les totaux avant l’enregistrement.",
    ],
    "modifier_devis.php": [
      "Modifier un devis",
      "Mettez à jour les informations, les prestations ou les conditions, puis enregistrez les changements.",
    ],
    "liste_devis.php": [
      "Devis",
      "Recherchez un devis, ouvrez son aperçu et utilisez les actions disponibles selon vos droits.",
    ],
    "liste_facture.php": [
      "Facturation",
      "Programmez les dates prévues de facturation et suivez les échéances depuis cette page ou la cloche.",
    ],
    "liste_client.php": [
      "Clients",
      "Ajoutez et mettez à jour les coordonnées utilisées dans vos devis.",
    ],
    "liste_offre.php": [
      "Offres",
      "Gérez les offres commerciales réutilisables dans vos devis.",
    ],
    "catalogue.php": [
      "Catalogue",
      "Consultez les produits, leurs prix agrégés et leur disponibilité.",
    ],
    "catalogue_media.php": [
      "Bibliothèque produit",
      "Choisissez un produit puis importez ses visuels pour enrichir le catalogue.",
    ],
    "boutique.php": [
      "Boutique",
      "Visualisez le catalogue publié et les médias associés aux produits.",
    ],
    "liste_bl.php": [
      "Livraisons",
      "Suivez les bons de livraison signés et non signés.",
    ],
    "liste_corbeille.php": [
      "Corbeille",
      "Retrouvez les devis masqués et restaurez ceux qui doivent redevenir actifs.",
    ],
    "liste_utilisateur.php": [
      "Équipe",
      "Gérez les utilisateurs et leurs permissions selon votre rôle.",
    ],
    "profil.php": [
      "Profil",
      "Mettez à jour vos informations personnelles et vos éléments de signature.",
    ],
  };
  const guide = guides[page] || [
    document.title || "Cette page",
    "Utilisez les actions proposées pour consulter ou mettre à jour les informations.",
  ];
  const key = `fidest-guide-seen:${page}`;
  const modal = document.createElement("div");
  modal.className = "first-use-guide";
  modal.innerHTML = `<div class="first-use-guide__backdrop"></div><section class="first-use-guide__dialog" role="dialog" aria-modal="true" aria-labelledby="guide-title"><button type="button" class="first-use-guide__close" aria-label="Fermer">&times;</button><span class="first-use-guide__eyebrow">Première utilisation</span><h2 id="guide-title"></h2><p></p><button type="button" class="btn btn-primary first-use-guide__done">C’est compris</button></section>`;
  modal.querySelector("h2").textContent = guide[0];
  modal.querySelector("p").textContent = guide[1];
  document.body.appendChild(modal);
  const close = () => {
    modal.classList.remove("is-open");
    localStorage.setItem(key, "1");
  };
  modal
    .querySelector(".first-use-guide__close")
    .addEventListener("click", close);
  modal
    .querySelector(".first-use-guide__backdrop")
    .addEventListener("click", close);
  modal
    .querySelector(".first-use-guide__done")
    .addEventListener("click", close);
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") close();
  });
  if (!localStorage.getItem(key))
    setTimeout(() => modal.classList.add("is-open"), 450);
  const help = document.createElement("button");
  help.type = "button";
  help.className = "first-use-guide__help";
  help.title = "Afficher le guide de cette page";
  help.setAttribute("aria-label", "Afficher le guide de cette page");
  help.innerHTML = '<i class="fas fa-circle-question"></i>';
  help.addEventListener("click", () => modal.classList.add("is-open"));
  document.body.appendChild(help);
});
