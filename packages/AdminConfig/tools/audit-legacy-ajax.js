/* eslint-env node */

const fs = require('fs');
const path = require('path');

const packageRoot = path.resolve(__dirname, '..');
const scanRoots = [
	'resources',
	'src',
];

const legacyPattern = /admin-ajax\.php|wp_ajax_lerm_admin_config|lerm_admin_config_ajax_|lerm_admin_config_data_source|legacyAjaxEnabled|ajaxUrl|dataSourceAction|dataSourceNonce|hasLegacyAjaxTransport|requestLegacyAjax/;

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

if (matches.length) {
	console.error('Legacy Ajax references are not allowed in production sources:');
	for (const match of matches) {
		console.error(`- ${match.file}:${match.line}: ${match.text}`);
	}
	process.exit(1);
}

console.log('Legacy Ajax audit passed: no production references remain.');
