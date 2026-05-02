import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];
    static values = {
        defaultStyle: { type: String, default: 'satellite' },
    };

    connect() {
        this.map = null;
        this.leaflet = null;
        this.activeLayer = null;
        this.currentStyle = this.defaultStyleValue;
        this.boundRegisterMap = this.registerMap.bind(this);

        this.element.addEventListener('ux:map:connect', this.boundRegisterMap);
    }

    disconnect() {
        this.element.removeEventListener('ux:map:connect', this.boundRegisterMap);
    }

    registerMap(event) {
        this.map = event.detail.map;
        this.leaflet = event.detail.L;

        this.applyStyle(this.currentStyle);
        window.setTimeout(() => {
            if (this.map) {
                this.map.invalidateSize();
            }
        }, 0);
    }

    switchStyle(event) {
        const nextStyle = event.params.style;

        if (!nextStyle || nextStyle === this.currentStyle) {
            return;
        }

        this.applyStyle(nextStyle);
    }

    applyStyle(styleName) {
        if (!this.map || !this.leaflet) {
            this.currentStyle = styleName;
            this.updateButtons();

            return;
        }

        const style = this.styles[styleName] ?? this.styles[this.defaultStyleValue];

        this.removeExistingTileLayers();

        this.activeLayer = this.leaflet.tileLayer(style.url, {
            attribution: style.attribution,
            subdomains: style.subdomains ?? undefined,
            maxZoom: style.maxZoom ?? 20,
        }).addTo(this.map);

        this.currentStyle = styleName;
        this.element.dataset.mapTheme = styleName;
        this.updateButtons();
        this.map.invalidateSize();
    }

    removeExistingTileLayers() {
        this.map.eachLayer((layer) => {
            if (layer instanceof this.leaflet.TileLayer) {
                this.map.removeLayer(layer);
            }
        });
    }

    updateButtons() {
        this.buttonTargets.forEach((button) => {
            const isActive = button.dataset.styleName === this.currentStyle;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    get styles() {
        return {
            night: {
                url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20,
            },
            minimal: {
                url: 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png',
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20,
            },
            satellite: {
                url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            },
        };
    }
}
