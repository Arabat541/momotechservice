// test-mongo.js
const mongoose = require('mongoose');
const uri = 'mongodb+srv://admin:admin@crm-repair-shop.78y2wwl.mongodb.net/crm?retryWrites=true&w=majority&appName=crm-repair-shop';
mongoose.connect(uri)
  .then(() => console.log('Connexion OK'))
  .catch((err) => console.error('Erreur connexion:', err));