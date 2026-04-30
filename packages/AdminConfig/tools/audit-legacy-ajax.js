/* eslint-env node */

const fs = require('fs');
const path = require('path');

const packageRoot = path.resolve(__dirname, '..');
const scanRoots = [
	'assets/src',
	'src',
];

const legacyPattern = /admin-ajax\.php|wp_ajax_lerm_admin_config|lerm_admin_config_ajax_|lerm_admin_config_data_source|legacyAjaxEnabled|ajaxUrl|dataSourceAction|dataSourceNonce|hasLegacyAjaxTransport|requestLegacyAjax/;

const allowedFiles = new Map([
	[
		'assets/src/admin-config.js',
		'classic admin fallback branches until 0.3.0 removal',
	],
	[
		'assets/src/transport.js',
		'isolated deprecated Ajax transport wrapper',
	],
	[
		'src/Framework/Admin/OptionsPage.php',
		'classic options-page fallback handlers and localized rollout flags',
	],
	[
		'src/WordPress/LegacyAjax.php',
		'single gate for deprecated legacy Ajax compatibility',
	],
	[
		'src/WordPress/Runtime.php',
		'deprecated async field data-source fallback handler',
	],
]);

const skipDirs = new Set([
	'.git',
	'artifacts',
	'node_modules',
	'vendor',
]);

function toPosix(relativePath) {
	return relativePath.split(path.sep).join('/');
}

function walk(dir) {
	const entries = fs.readdirSync(dir, { withFileTypes: true });
	const files = [];

	for (const entry of entries) {
		const fullPath = path.join(dir, entry.name);

		if (entry.isDirectory()) {
			if (!skipDirs.has(entry.name)) {
				files.push(...walk(fullPath));
			}
			continue;
		}

		if (entry.isFile()) {
			files.push(fullPath);
		}
	}

	return files;
}

const matches = [];

for (const root of scanRoots) {
	const absoluteRoot = path.join(packageRoot, root);

	if (!fs.existsSync(absoluteRoot)) {
		continue;
	}

	for (const file of walk(absoluteRoot)) {
		const relativePath = toPosix(path.relative(packageRoot, file));
		const contents = fs.readFileSync(file, 'utf8');

		contents.split(/\r?\n/).forEach((line, index) => {
			if (legacyPattern.test(line)) {
				matches.push({
					file: relativePath,
					line: index + 1,
					text: line.trim(),
				});
			}
		});
	}
}

const unexpected = matches.filter((match) => !allowedFiles.has(match.file));

if (unexpected.length) {
	console.error('Unexpected legacy Ajax references found outside the approved rollout surface:');
	for (const match of unexpected) {
		console.error(`- ${match.file}:${match.line}: ${match.text}`);
	}
	process.exit(1);
}

const presentAllowedFiles = Array.from(new Set(matches.map((match) => match.file))).sort();

console.log('Legacy Ajax audit passed.');
console.log('Approved production references:');
for (const file of presentAllowedFiles) {
	console.log(`- ${file}: ${allowedFiles.get(file)}`);
}
