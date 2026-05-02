
import '@hotwired/turbo';
import app from './bootstrap.js';
import LeafletMapController from '@symfony/ux-leaflet-map';
import ZonepMapController from './controllers/zonep_map_controller.js';

app.register('symfony--ux-leaflet-map--map', LeafletMapController);
app.register('zonep-map', ZonepMapController);

import { trans } from './translator.js';

window.ecomarineTrans = trans;

function createReadonlyStar(fillRatio) {
    const star = document.createElement('span');
    star.style.display = 'inline-block';
    star.style.position = 'relative';
    star.style.width = '1.1em';
    star.style.height = '1.1em';
    star.style.lineHeight = '1.1em';
    star.style.fontSize = '1.1em';
    star.style.marginRight = '1px';

    const empty = document.createElement('span');
    empty.textContent = '\u2606'; // ☆
    empty.style.color = '#cbd5e1';

    const filled = document.createElement('span');
    filled.textContent = '\u2605'; // ★
    filled.style.color = '#f59e42';
    filled.style.position = 'absolute';
    filled.style.left = '0';
    filled.style.top = '0';
    filled.style.width = `${Math.max(0, Math.min(1, fillRatio)) * 100}%`;
    filled.style.overflow = 'hidden';
    filled.style.whiteSpace = 'nowrap';

    star.appendChild(empty);
    star.appendChild(filled);
    return star;
}

function renderReservationRatingEditors() {
    document.querySelectorAll('.reservation-rating[data-editable-rating="reservation"]').forEach(function (el) {
        const rating = parseInt(el.dataset.rating || '0', 10);
        el.innerHTML = '';

        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('span');
            star.textContent = i <= rating ? '\u2605' : '\u2606';
            star.style.cursor = 'pointer';
            star.style.color = i <= rating ? '#f59e42' : '#cbd5e1';
            star.style.fontSize = '1.1em';
            star.addEventListener('click', function () {
                el.dataset.rating = String(i);
                renderReservationRatingEditors();
            });
            el.appendChild(star);
        }
    });
}

function renderActivityRatingEditor() {
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
        star.textContent = i <= rating ? '\u2605' : '\u2606';
        star.style.cursor = 'pointer';
        star.style.color = i <= rating ? '#f59e42' : '#cbd5e1';
        star.style.fontSize = '1.3em';
        star.addEventListener('click', function () {
            ratingEl.dataset.rating = String(i);

            let desc = descField.value
                .replace(/\n?\[Note:\s*[1-5]\s+etoiles\]/gi, '')
                .replace(/\n?\[Note:\s*[1-5]\s+étoiles\]/gi, '')
                .trimEnd();

            descField.value = desc ? `${desc}\n[Note: ${i} etoiles]` : `[Note: ${i} etoiles]`;
            renderActivityRatingEditor();
        });
        ratingEl.appendChild(star);
    }
}

function renderReadonlyRatings() {
    document.querySelectorAll('.reservation-rating[data-readonly-rating="1"]').forEach(function (el) {
        const rating = parseFloat(el.dataset.rating || '0');
        el.innerHTML = '';

        for (let i = 1; i <= 5; i++) {
            if (rating >= i) {
                el.appendChild(createReadonlyStar(1));
            } else if (rating >= i - 0.5) {
                el.appendChild(createReadonlyStar(0.5));
            } else {
                el.appendChild(createReadonlyStar(0));
            }
        }
    });
}

function renderAllRatings() {
    renderReservationRatingEditors();
    renderActivityRatingEditor();
    renderReadonlyRatings();
}

window.ecomarineRenderRatings = renderAllRatings;

document.addEventListener('DOMContentLoaded', renderAllRatings);
document.addEventListener('turbo:load', renderAllRatings);

