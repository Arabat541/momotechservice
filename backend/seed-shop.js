/**
 * Migration script: Create Shop "Abengourou Cours Royal" and associate all existing data.
 * Run: node seed-shop.js
 */
const mongoose = require('mongoose');

const uri = 'mongodb://admin:admin@ac-a0fplr5-shard-00-00.78y2wwl.mongodb.net:27017,ac-a0fplr5-shard-00-01.78y2wwl.mongodb.net:27017,ac-a0fplr5-shard-00-02.78y2wwl.mongodb.net:27017/crm?ssl=true&authSource=admin&retryWrites=true&w=majority';

async function migrate() {
  await mongoose.connect(uri);
  const db = mongoose.connection.db;
  console.log('Connected to MongoDB');

  // 1. Find the patron user (creator)
  const patron = await db.collection('users').findOne({ role: 'patron' });
  if (!patron) throw new Error('No patron user found');
  console.log(`Patron: ${patron.nom} ${patron.prenom} (${patron._id})`);

  // 2. Check if shop already exists
  const existingShop = await db.collection('shops').findOne({ nom: 'Abengourou Cours Royal' });
  if (existingShop) {
    console.log('Shop already exists:', existingShop._id);
    await mongoose.disconnect();
    return;
  }

  // 3. Create the Shop
  const shopResult = await db.collection('shops').insertOne({
    nom: 'Abengourou Cours Royal',
    adresse: "Situé juste Derrière la Cour Royale d'Abengourou",
    telephone: '0576336739/0708511620',
    createdBy: patron._id,
    createdAt: new Date(),
    updatedAt: new Date(),
  });
  const shopId = shopResult.insertedId;
  console.log(`Shop created: ${shopId}`);

  // 4. Update all repairs with shopId
  const repairResult = await db.collection('repairs').updateMany(
    { shopId: { $exists: false } },
    { $set: { shopId: shopId } }
  );
  console.log(`Repairs updated: ${repairResult.modifiedCount}`);

  // 5. Update all stocks with shopId
  const stockResult = await db.collection('stocks').updateMany(
    { shopId: { $exists: false } },
    { $set: { shopId: shopId } }
  );
  console.log(`Stocks updated: ${stockResult.modifiedCount}`);

  // 6. Update settings with shopId
  const settingsResult = await db.collection('settings').updateMany(
    { shopId: { $exists: false } },
    { $set: { shopId: shopId } }
  );
  console.log(`Settings updated: ${settingsResult.modifiedCount}`);

  // 7. Update all SAVs with shopId (if any)
  const savResult = await db.collection('savs').updateMany(
    { shopId: { $exists: false } },
    { $set: { shopId: shopId } }
  );
  console.log(`SAVs updated: ${savResult.modifiedCount}`);

  // 8. Add shop to all users' shops array
  const userResult = await db.collection('users').updateMany(
    {},
    { $addToSet: { shops: shopId } }
  );
  console.log(`Users updated: ${userResult.modifiedCount}`);

  // 9. Verify
  const shop = await db.collection('shops').findOne({ _id: shopId });
  console.log('\n=== Verification ===');
  console.log('Shop:', shop.nom, shop._id);
  console.log('Repairs with shopId:', await db.collection('repairs').countDocuments({ shopId }));
  console.log('Stocks with shopId:', await db.collection('stocks').countDocuments({ shopId }));
  console.log('Settings with shopId:', await db.collection('settings').countDocuments({ shopId }));

  await mongoose.disconnect();
  console.log('\nMigration complete!');
}

migrate().catch(err => {
  console.error('Migration failed:', err);
  process.exit(1);
});
