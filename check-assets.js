// Script Node.js pour vérifier la présence des assets sur un site déployé (ex: Netlify)
// Usage: node check-assets.js https://votre-site.netlify.app

const https = require('https');
const assets = [
  '/',
  '/manifest.json',
  '/favicon.svg',
  '/offline.html'
];

const site = process.argv[2];
if (!site) {
  console.error('Usage: node check-assets.js <URL_SITE>');
  process.exit(1);
}

function checkAsset(url) {
  return new Promise((resolve) => {
    https.get(url, (res) => {
      resolve({ url, status: res.statusCode });
    }).on('error', () => {
      resolve({ url, status: 'ERROR' });
    });
  });
}

(async () => {
  for (const asset of assets) {
    const url = site.replace(/\/$/, '') + asset;
    const result = await checkAsset(url);
    console.log(`${result.url} => ${result.status}`);
  }
})();
