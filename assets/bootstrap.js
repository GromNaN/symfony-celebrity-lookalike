import { startStimulusApp } from '@symfony/stimulus-bundle';
import { startStimulusApp } from '@symfony/stimulus-bundle';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bundle/controllers',
    true,
    /\.[jt]sx?$/
));

// Register your own controllers
import webcam from './controllers/webcam_controller.js';
app.register('webcam', webcam);
const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
