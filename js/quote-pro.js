(function () {
  "use strict";

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [
    ...root.querySelectorAll(selector),
  ];
  const value = (selector) => $(selector)?.value?.trim() || "";
  const money = (amount) =>
    new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 }).format(
      Number(amount) || 0,
    ) + " FCFA";

  function addSectionTitle(element, icon, title) {
    if (!element || $(".editor-section-title", element)) return;
    element.classList.add("editor-section");
    element.insertAdjacentHTML(
      "afterbegin",
      `<h2 class="editor-section-title"><span><i class="fas ${icon}"></i></span>${title}</h2>`,
    );
  }

  function createRichEditor(textarea) {
    const wrapper = document.createElement("div");
    wrapper.className = "rich-field";
    textarea.parentNode.insertBefore(wrapper, textarea);
    wrapper.appendChild(textarea);
    wrapper.insertAdjacentHTML(
      "afterbegin",
      `
            <div class="rich-toolbar" aria-label="Outils de mise en forme">
                <button type="button" data-command="bold" title="Gras"><i class="fas fa-bold"></i></button>
                <button type="button" data-command="italic" title="Italique"><i class="fas fa-italic"></i></button>
                <button type="button" data-command="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                <button type="button" data-command="insertOrderedList" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
            </div>
            <div class="rich-editor" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="${textarea.placeholder || ""}"></div>`,
    );
    const editor = $(".rich-editor", wrapper);
    editor.innerText = textarea.value;
    const sync = () => {
      textarea.value = editor.innerText.trim();
      updatePreview();
    };
    editor.addEventListener("input", sync);
    $$(".rich-toolbar button", wrapper).forEach((button) =>
      button.addEventListener("click", () => {
        editor.focus();
        document.execCommand(button.dataset.command, false);
        sync();
      }),
    );
  }

  function previewRows() {
    return $$("#devisTable tbody tr")
      .map((row) => {
        const description =
          $('[name="designation[]"]', row)?.value || "Prestation";
        const quantity = $('[name="quantite[]"]', row)?.value || "0";
        const total = $(".total", row)?.value || "0";
        return `<tr><td>${escapeHtml(description)}</td><td>${escapeHtml(quantity)}</td><td>${money(total)}</td></tr>`;
      })
      .join("");
  }

  function escapeHtml(text) {
    const node = document.createElement("div");
    node.textContent = String(text);
    return node.innerHTML;
  }

  function updatePreview() {
    const panel = $("#quotePreview");
    if (!panel) return;
    $("#previewNumber").textContent = value("#numeroDevis") || "DEVIS";
    $("#previewDate").textContent = value("#dateEmission");
    $("#previewIssuer").textContent =
      value("#emisPar") || "Informations de votre entreprise";
    $("#previewRecipient").textContent =
      value("#destineA") ||
      $("#clientSelect option:checked")?.textContent?.trim() ||
      "Client";
    $("#previewRows").innerHTML = previewRows();
    $("#previewHT").textContent = money(value("#totalHT"));
    $("#previewTax").textContent = money(value("#tva"));
    $("#previewTTC").textContent = money(value("#totalTTC"));
    $("#previewTerms").textContent = value("#termesConditions");
    $("#previewFooter").textContent = value("#piedDePage");
    $("#previewDelivery").textContent =
      value("#delaiLivraison") || "À convenir";
    $("#previewContact").textContent =
      value("#correspondant") || "Non renseigné";
    $("#previewExpiration").textContent =
      value("#dateExpiration") || "Non renseignée";
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (!document.body.classList.contains("quote-editor")) return;

    const heading = $(".page-heading");
    heading?.insertAdjacentHTML(
      "afterend",
      `
            <div class="editor-topbar">
                <div class="editor-progress">
                    <div class="editor-step active"><span>1</span>Informations</div>
                    <div class="editor-step"><span>2</span>Prestations</div>
                    <div class="editor-step"><span>3</span>Finalisation</div>
                </div>
                <div class="editor-actions"><button class="btn btn-outline-primary" type="button" id="openPreview"><i class="fas fa-eye"></i> Aperçu en direct</button></div>
            </div>`,
    );
    if ($("#devisId")) {
      $("#openPreview").innerHTML =
        '<i class="fas fa-file-pdf"></i> Enregistrer et voir le PDF exact';
    }

    const container = heading?.parentElement;
    addSectionTitle(
      container?.querySelector(".row.mb-3"),
      "fa-address-book",
      "Client et référence commerciale",
    );
    addSectionTitle(
      container?.querySelector(".d-flex.align-items-start.mb-4"),
      "fa-file-signature",
      "Identité du document",
    );
    addSectionTitle(
      $("#devisForm > .row.mb-3"),
      "fa-building",
      "Émetteur et destinataire",
    );
    addSectionTitle(
      $(".footer-info"),
      "fa-circle-check",
      "Conditions et finalisation",
    );

    const tableWrapper = $("#devisTable")?.closest(".table-responsive");
    if (
      tableWrapper &&
      !tableWrapper.parentElement.classList.contains("line-items-section")
    ) {
      const section = document.createElement("section");
      section.className = "editor-section line-items-section";
      tableWrapper.parentNode.insertBefore(section, tableWrapper);
      section.appendChild(tableWrapper);
      const addButton = $("#addRow");
      if (addButton) section.appendChild(addButton);
      section.insertAdjacentHTML(
        "afterbegin",
        '<h2 class="editor-section-title"><span><i class="fas fa-layer-group"></i></span>Prestations et produits</h2>',
      );
    }

    ["#termesConditions", "#piedDePage"].forEach((selector) => {
      const textarea = $(selector);
      if (textarea) createRichEditor(textarea);
    });

    const clientSelect = $("#clientSelect");
    const recipientField = $("#destineA");
    const syncRecipient = () => {
      if (!clientSelect || !recipientField) return;
      recipientField.value =
        clientSelect.selectedOptions[0]?.dataset.recipient || "";
      updatePreview();
    };
    clientSelect?.addEventListener("change", syncRecipient);
    syncRecipient();
    const bindEditLink = (select, link, page) => {
      const update = () => {
        if (!select || !link) return;
        const id = select.value;
        link.href = id ? `${page}?id=${encodeURIComponent(id)}` : "#";
        link.classList.toggle("disabled", !id);
        link.setAttribute("aria-disabled", id ? "false" : "true");
      };
      select?.addEventListener("change", update);
      update();
    };
    bindEditLink(clientSelect, $("#editSelectedClient"), "modifier_client.php");
    bindEditLink(
      $("#offreSelect"),
      $("#editSelectedOffer"),
      "modifier_offre.php",
    );

    document.body.insertAdjacentHTML(
      "beforeend",
      `
            <div class="quote-preview-backdrop" id="previewBackdrop"></div>
            <aside class="quote-preview-panel" id="quotePreview" aria-label="Aperçu du devis">
                <div class="quote-preview-head"><strong>Aperçu du document</strong><button type="button" class="quote-preview-close" id="closePreview"><i class="fas fa-xmark"></i></button></div>
                <div class="quote-paper">
                    <div class="quote-paper__head"><img class="quote-paper__logo" id="previewLogo" src="img/logo_fidest.png" alt="FIDEST"><div class="quote-paper__title"><h2>DEVIS</h2><div id="previewNumber"></div><div id="previewDate"></div></div></div>
                    <div class="quote-paper__parties"><div><small>Émetteur</small><div id="previewIssuer"></div></div><div><small>Destinataire</small><div id="previewRecipient"></div></div></div>
                    <table><thead><tr><th>Désignation</th><th>Qté</th><th>Total</th></tr></thead><tbody id="previewRows"></tbody></table>
                    <div class="quote-paper__totals"><div class="quote-paper__total"><span>Total HT</span><strong id="previewHT"></strong></div><div class="quote-paper__total"><span>TVA</span><strong id="previewTax"></strong></div><div class="quote-paper__total quote-paper__total--grand"><span>Total TTC</span><strong id="previewTTC"></strong></div></div>
                    <div class="quote-paper__details"><div><small>Délai de livraison</small><strong id="previewDelivery"></strong></div><div><small>Correspondant</small><strong id="previewContact"></strong></div><div><small>Valable jusqu’au</small><strong id="previewExpiration"></strong></div></div>
                    <div class="quote-paper__terms"><strong>Termes et conditions</strong><div id="previewTerms"></div></div>
                    <div class="quote-paper__footer" id="previewFooter"></div>
                </div>
            </aside>`,
    );

    const togglePreview = (open) => {
      $("#quotePreview").classList.toggle("open", open);
      $("#previewBackdrop").classList.toggle("open", open);
      document.body.style.overflow = open ? "hidden" : "";
      if (open) updatePreview();
    };
    $("#openPreview")?.addEventListener("click", () => {
      if ($("#devisId")) {
        document.dispatchEvent(new CustomEvent("quote:request-exact-preview"));
        return;
      }
      togglePreview(true);
    });
    $("#closePreview")?.addEventListener("click", () => togglePreview(false));
    $("#previewBackdrop")?.addEventListener("click", () =>
      togglePreview(false),
    );
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") togglePreview(false);
    });
    document.addEventListener("input", updatePreview);
    document.addEventListener("change", updatePreview);
    document.addEventListener("click", (event) => {
      if (event.target.closest("#addRow, .remove-row"))
        setTimeout(updatePreview, 0);
    });

    $("#logoUpload")?.addEventListener("change", (event) => {
      const file = event.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        $("#previewLogo").src = reader.result;
      };
      reader.readAsDataURL(file);
    });
    updatePreview();
  });

  function notify(message, type) {
    document.querySelector(".editor-toast")?.remove();
    const toast = document.createElement("div");
    toast.className = `editor-toast editor-toast--${type}`;
    toast.innerHTML = `<i class="fas ${type === "success" ? "fa-circle-check" : "fa-circle-exclamation"}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("show"));
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 250);
    }, 4000);
  }

  document.addEventListener("quote:saved", () =>
    notify("Le devis a été enregistré avec succès.", "success"),
  );
  document.addEventListener("quote:error", (event) => {
    notify(
      event.detail?.message ||
        "L’enregistrement a échoué. Vérifiez les informations saisies.",
      "error",
    );
  });
})();
