/**
 * Formulaire contact — selects dynamiques prestation
 * Le select "Type de prestation" se met à jour en fonction du "Type de volume" choisi.
 * Pré-remplissage automatique via le paramètre URL ?volume=slug
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-prestation-selects]');
    if (!container) return;

    const volumeSelect = container.querySelector('[data-volume-select]');
    const prestationSelect = container.querySelector('[data-prestation-select]');
    if (!volumeSelect || !prestationSelect) return;

    const tarifs = JSON.parse(container.dataset.tarifs);

    function updatePrestations() {
        const slug = volumeSelect.value;

        prestationSelect.innerHTML = '';

        if (!slug || slug === 'indecise') {
            prestationSelect.innerHTML = '<option value="">—</option>';
            prestationSelect.disabled = true;
            return;
        }

        if (slug === 'depose') {
            prestationSelect.innerHTML = '<option value="depose">Dépose</option>';
            prestationSelect.disabled = false;
            return;
        }

        const categorie = tarifs[slug];
        if (!categorie) return;

        prestationSelect.disabled = false;
        prestationSelect.innerHTML = '<option value="">Sélectionnez...</option>';

        categorie.prestations.forEach((p) => {
            const option = document.createElement('option');
            option.value = p.value;
            option.textContent = p.label;
            prestationSelect.appendChild(option);
        });
    }

    volumeSelect.addEventListener('change', updatePrestations);

    // Pré-remplissage depuis l'URL (?volume=naturelle, ?volume=depose, etc.)
    const params = new URLSearchParams(window.location.search);
    const volumeParam = params.get('volume');

    if (volumeParam && volumeSelect.querySelector(`option[value="${volumeParam}"]`)) {
        volumeSelect.value = volumeParam;
        updatePrestations();
    }
});
