// test-native.js
const { MongoClient } = require('mongodb');
const uri = 'mongodb+srv://admin:admin@crm-repair-shop.78y2wwl.mongodb.net/crm?retryWrites=true&w=majority&appName=crm-repair-shop&tls=true';
MongoClient.connect(uri)
  .then(client => {
    console.log('Connexion driver natif OK');
    client.close();
  })
  .catch(err => {
    console.error('Erreur driver natif:', err);
  });