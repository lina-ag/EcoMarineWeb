import './bootstrap.js';
import { trans } from './translator.js';

window.ecomarineTrans = trans;

// Ajout rating étoiles vanilla pour toutes les cellules .reservation-rating
function renderRatings() {
    document.querySelectorAll('.reservation-rating[data-editable-rating="reservation"]').forEach(function (el) {
        const rating = parseInt(el.dataset.rating || '0', 10);
        el.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('span');
            star.textContent = i <= rating ? '★' : '☆';
            star.style.cursor = 'pointer';
            star.style.color = i <= rating ? '#f59e42' : '#cbd5e1';
            star.style.fontSize = '1.1em';
            star.addEventListener('click', function () {
                // TODO: envoyer la note au serveur via fetch/ajax si besoin
                el.dataset.rating = i;
                renderRatings();
            });
            el.appendChild(star);
        }
    });
}

// Forcer le rating à côté de l'activité dans le formulaire
function renderActivityRating() {
    const ratingEl = document.getElementById('activity-rating-stars');
    const descField = document.getElementById('description-field');
    if (!ratingEl || !descField) return;

    const existingMatch = descField.value.match(/\[Note:\s*([1-5])\s+etoiles\]|\[Note:\s*([1-5])\s+étoiles\]/i);
    const existingRating = existingMatch ? parseInt(existingMatch[1] || existingMatch[2], 10) : 0;
    let rating = parseInt(ratingEl.dataset.rating || String(existingRating || 0), 10);

    if (!Number.isNaN(existingRating) && existingRating > 0) {
        ratingEl.dataset.rating = String(existingRating);
        rating = existingRating;
    }

    ratingEl.innerHTML = '';
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.textContent = i <= rating ? '★' : '☆';
        star.style.cursor = 'pointer';
        star.style.color = i <= rating ? '#f59e42' : '#cbd5e1';
        star.style.fontSize = '1.3em';
        star.addEventListener('click', function () {
            ratingEl.dataset.rating = i;
            // Ajoute ou remplace la note dans la description
            let desc = descField.value
                .replace(/\n?\[Note:\s*[1-5]\s+etoiles\]/gi, '')
                .replace(/\n?\[Note:\s*[1-5]\s+étoiles\]/gi, '')
                .trimEnd();

            descField.value = desc ? desc + '\n[Note: ' + i + ' etoiles]' : '[Note: ' + i + ' etoiles]';
            renderActivityRating();
        });
        ratingEl.appendChild(star);
    }
}

// Affichage étoiles/demi-étoiles selon data-rating (float)
function renderActivityListRatings() {
    document.querySelectorAll('.reservation-rating[data-readonly-rating="1"]').forEach(function (el) {
        let rating = parseFloat(el.dataset.rating || '0');
        el.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            if (rating >= i) {
                // Pleine étoile
                el.innerHTML += '<span style="color:#f59e42;font-size:1.1em;cursor:pointer">★</span>';
            } else if (rating >= i - 0.5) {
                // Demi-étoile
                el.innerHTML += '<span style="color:#f59e42;font-size:1.1em;cursor:pointer">⯨</span>';
            } else {
                // Vide
                el.innerHTML += '<span style="color:#cbd5e1;font-size:1.1em;cursor:pointer">☆</span>';
            }
        }
    });
}

function renderAllRatings() {
    renderRatings();
    renderActivityRating();
    renderActivityListRatings();
}

window.ecomarineRenderRatings = renderAllRatings;

document.addEventListener('DOMContentLoaded', renderAllRatings);
document.addEventListener('htmx:afterSettle', renderAllRatings); // si tu utilises htmx ou du DOM dynamique
document.addEventListener('turbo:load', renderAllRatings);
