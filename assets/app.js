import '@hotwired/turbo';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import ZonepMapController from './controllers/zonep_map_controller.js';

const app = startStimulusApp();

app.register('zonep-map', ZonepMapController);
