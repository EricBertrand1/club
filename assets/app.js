// Importer bootstrap.js (si nécessaire pour ton projet)
import './bootstrap.js';

// Importer Stimulus
import { Application } from 'stimulus';
import { definitionsFromContext } from 'stimulus/webpack-helpers';

// Démarrer l'application Stimulus
const application = Application.start();

// Charger les contrôleurs Stimulus depuis le répertoire 'controllers'
const context = require.context('./controllers', true, /\.js$/);
application.load(definitionsFromContext(context));

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
