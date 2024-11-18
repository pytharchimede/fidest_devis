$(document).ready(function() {
    

    $("#exportPdfBtn").hide();

    $('#saveBtn').on('click', function(e) {
        e.preventDefault(); // Empêche le comportement de soumission du formulaire par défaut

        // Sélectionner le formulaire pour créer l'objet FormData
        var form = $('#devisForm')[0]; // Utilisez [0] pour obtenir l'élément DOM natif

        // Crée une nouvelle instance de FormData à partir du formulaire
        var formData = new FormData(form);
        
        // Ajoutez les valeurs des cases à cocher
        var tvaFacturable = $('input[name="tvaFacturable"]:checked').val() || '0';
        var publierDevis = $('input[name="publierDevis"]:checked').val() || '0';

        // Récupérez les valeurs des champs non inclus dans le formulaire
        var numeroDevis = $('#numeroDevis').val();
        var delaiLivraison = $('#delaiLivraison').val();
        var dateEmission = $('#dateEmission').val();
        var dateExpiration = $('#dateExpiration').val();
        var logoFile = $('#logoUpload')[0].files[0]; // Récupère le fichier de logo
        
        // Récupération des valeurs des champs client et offre
        var clientId = $('#clientSelect').val();
        var offreId = $('#offreSelect').val();

        // Récupération des valeurs des champs additionnels
        var termesConditions = $('#termesConditions').val() || '';
        var piedDePage = $('#piedDePage').val() || '';
        var totalHT = $('#totalHT').val() || '0';
        var totalTTC = $('#totalTTC').val() || '0';
        var tva = $('#tva').val() || '0';

        // Ajoutez les valeurs au FormData
        formData.append('numeroDevis', numeroDevis);
        formData.append('delaiLivraison', delaiLivraison);
        formData.append('dateEmission', dateEmission);
        formData.append('dateExpiration', dateExpiration);
        formData.append('termesConditions', termesConditions);
        formData.append('piedDePage', piedDePage);
        formData.append('totalHT', totalHT);
        formData.append('totalTTC', totalTTC);
        formData.append('tva', tva);
        formData.append('client_id', clientId);
        formData.append('offre_id', offreId); 
        formData.append('tvaFacturable', tvaFacturable);
        formData.append('publierDevis', publierDevis);
        if (logoFile) {
            formData.append('logo', logoFile);
        }

        // Effectuez la requête AJAX
        $.ajax({
            type: "POST",
            url: "request/generate_devis.php",
            data: formData,
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                // Traitez la réponse du serveur ici
                console.log('Réponse du serveur:', response);
                // Redirection ou autre action après succès
                //$(location).attr('href', 'signer_fiche/index.php'); 
                $("#exportPdfBtn").show();
                $("#saveBtn").hide();
            },
            error: function(xhr, status, error) {
                console.error('Erreur:', status, error);
            }
        });
    });

    $("#exportPdfBtn").on('click', function(){
        $(location).attr('href', 'request/export_pdf.php'); 
    });

});
