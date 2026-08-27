import { config } from './config.js';
import { createApp } from './app.js';

createApp().listen(config.port, () => {
  console.log(`Bookbits running at ${config.baseUrl} (${config.env})`);
});
