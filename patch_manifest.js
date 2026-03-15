/**
 * Post-build manifest patch.
 * Vite adds ?commonjs-entry suffix to node_modules entries in manifest.json,
 * but Laravel's @vite() does exact key lookups. This script adds clean aliases.
 * Run after every: npm run build
 */
const fs   = require('fs');
const path = require('path');

const manifestPath = path.join(__dirname, 'public/build/manifest.json');

if (!fs.existsSync(manifestPath)) {
    console.error('manifest.json not found. Run npm run build first.');
    process.exit(1);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
let patched = 0;

for (const key of Object.keys(manifest)) {
    if (key.includes('?commonjs-entry')) {
        const cleanKey = key.replace('?commonjs-entry', '');
        if (!manifest[cleanKey]) {
            manifest[cleanKey] = manifest[key];
            patched++;
        }
    }
}

if (patched > 0) {
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
    console.log(`Manifest patched: ${patched} commonjs-entry aliases added.`);
} else {
    console.log('Manifest already clean, no patch needed.');
}
