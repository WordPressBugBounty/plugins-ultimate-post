/**
 * PostX Version Bumper
 * Usage:
 *   node bump-version.js 5.0.17
 *   node bump-version.js 5.0.17 --dry
 */

const fs = require('fs');
const path = require('path');

// Get args
const args = process.argv.slice(2);
const versionInput = args[0];
const isDryRun = args.includes('--dry');

// Use current working directory (important for npx)
const pluginRoot = __dirname;

// Files to update
const filesToUpdate = [
  {
    path: path.join(pluginRoot, 'ultimate-post.php'),
    replacements: [
      { pattern: /\* Version:\s+[\d.]+/, replacement: '* Version:     {VERSION}' },
      { pattern: /define\(\s*'ULTP_VER',\s*'[\d.]+'\s*\)/, replacement: "define( 'ULTP_VER', '{VERSION}' )" }
    ]
  },
  {
    path: path.join(pluginRoot, 'readme.txt'),
    replacements: [
      { pattern: /Stable tag:\s*[\d.]+/, replacement: 'Stable tag: {VERSION}' }
    ]
  },
  {
    path: path.join(pluginRoot, 'languages', 'ultimate-post.pot'),
    replacements: [
      { pattern: /"Project-Id-Version: PostX [\d.]+\\n"/, replacement: '"Project-Id-Version: PostX {VERSION}\\n"' }
    ]
  },
  {
    path: path.join(pluginRoot, 'reactjs', 'package.json'),
    replacements: [
      { pattern: /"version":\s*"[\d.]+?"/, replacement: '"version": "{VERSION}"' }
    ]
  }
];

/**
 * Get current version
 */
function getCurrentVersion() {
  try {
    const filePath = path.join(pluginRoot, 'ultimate-post.php');
    const content = fs.readFileSync(filePath, 'utf8');
    const match = content.match(/define\(\s*'ULTP_VER',\s*'([\d.]+)'\s*\)/);

    if (match && match[1]) return match[1];

    throw new Error('ULTP_VER not found');
  } catch (err) {
    console.error('❌ Error reading current version:', err.message);
    process.exit(1);
  }
}

/**
 * Validate version
 */
function validateVersion(versionStr) {
  const parts = versionStr.split('.');
  if (parts.length !== 3 || !parts.every(p => /^\d+$/.test(p))) {
    throw new Error(`Invalid version: "${versionStr}". Use X.Y.Z`);
  }
}

/**
 * Get current date
 */
function getCurrentDate() {
  const date = new Date();
  const day = String(date.getDate()).padStart(2, '0');
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  return `${day} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Add changelog
 */
function addChangelogEntry(version, isDryRun) {
  try {
    const filePath = path.join(pluginRoot, 'readme.txt');
    let content = fs.readFileSync(filePath, 'utf8');

    const date = getCurrentDate();
    const newEntry = `= ${version} – ${date} =\n* Fix:\n\n`;

    const regex = /(== Changelog ==\r?\n)/;

    if (!regex.test(content)) {
      console.warn('⚠️ Changelog section not found');
      return false;
    }

    content = content.replace(regex, `$1${newEntry}`);

    if (isDryRun) {
      console.log(`🧪 [DRY RUN] readme.txt changelog would be updated`);
    } else {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log(`✅ readme.txt - changelog entry added`);
    }

    return true;
  } catch (err) {
    console.error('❌ Error adding changelog:', err.message);
    return false;
  }
}

/**
 * Update files
 */
function updateFiles(oldVersion, newVersion) {
  const results = [];

  filesToUpdate.forEach(file => {
    try {
      if (!fs.existsSync(file.path)) {
        console.warn(`⚠️ File not found: ${file.path}`);
        return;
      }

      let content = fs.readFileSync(file.path, 'utf8');
      let updated = false;

      file.replacements.forEach(rep => {
        if (rep.pattern.test(content)) {
          const replacement = rep.replacement.replace('{VERSION}', newVersion);
          content = content.replace(rep.pattern, replacement);
          updated = true;
        }
      });

      if (updated) {
        if (isDryRun) {
          console.log(`🧪 [DRY RUN] ${path.basename(file.path)} would be updated`);
        } else {
          fs.writeFileSync(file.path, content, 'utf8');
          console.log(`✅ ${path.basename(file.path)}`);
        }
      } else {
        console.log(`⚪ ${path.basename(file.path)} (no changes)`);
      }

    } catch (err) {
      console.log(`❌ ${path.basename(file.path)} - ${err.message}`);
    }
  });

  return results;
}

/**
 * Main
 */
function main() {
  try {
    if (!versionInput) {
      console.error('❌ No version specified');
      console.log('Usage: node bump-version.js 5.0.17 [--dry]');
      process.exit(1);
    }

    validateVersion(versionInput);

    if (isDryRun) {
      console.log('🧪 DRY RUN mode enabled (no files will be modified)\n');
    }

    const currentVersion = getCurrentVersion();

    console.log(`📦 Current: ${currentVersion}`);
    console.log(`📦 New: ${versionInput}\n`);

    if (versionInput === currentVersion) {
      console.log('⚠️ Same version. No changes.');
      process.exit(0);
    }

    console.log('🔄 Updating files...\n');
    updateFiles(currentVersion, versionInput);

    console.log('\n🔄 Updating changelog...\n');
    addChangelogEntry(versionInput, isDryRun);

    if (isDryRun) {
      console.log('\n🧪 Dry run completed. No files were changed.');
    } else {
      console.log(`\n✨ Version bumped: ${currentVersion} → ${versionInput}`);
      console.log('\nNext steps:');
      console.log('1. Edit readme.txt');
      console.log('2. git diff');
      console.log(`3. git commit -m "Release: version ${versionInput}"`);
    }

  } catch (err) {
    console.error('❌ Error:', err.message);
    process.exit(1);
  }
}

main();