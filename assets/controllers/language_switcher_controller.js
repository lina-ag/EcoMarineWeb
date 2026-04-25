import { Controller } from '@hotwired/stimulus';
import { trans } from '../translator.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        url: String,
        currentLocale: String
    }

    connect() {
        // Initialisation si nécessaire
    }

    switch(event) {
        event.preventDefault();
        
        // Exemple d'utilisation de UX Translator en JS
        const message = trans('common.switch_language');
        console.log(`[LanguageSwitcher] ${message} -> ${this.urlValue}`);

        // Redirection vers la route de changement de locale
        window.location.href = this.urlValue;
    }
}
