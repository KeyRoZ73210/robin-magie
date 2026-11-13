// Tests de qualité : SITE_URL=https://mon-site.local npm test
const { defineConfig } = require('@playwright/test');
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });

module.exports = defineConfig({
  testDir: './tests',
  testMatch: '**/*.spec.js',
  timeout: 30000,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.SITE_URL,
    ignoreHTTPSErrors: true,
  },
});
