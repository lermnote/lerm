/* eslint-env node */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');

const requiredFiles = [
	'assets/build/admin-config.js',
	'assets/build/admin-config.asset.php',
	'assets/build/block-panel.js',
	'assets/build/block-panel.asset.php',
];

const errors = [];

const read = (relativePath) => {
	const file = path.join(root, relativePath);

	if (!fs.existsSync(file)) {
		errors.push(`${relativePath} was not generated.`);
		return '';
	}

	const stat = fs.statSync(file);

	if (stat.size <= 0) {
		errors.push(`${relativePath} is empty.`);
		return '';
	}

	return fs.readFileSync(file, 'utf8');
};

for (const file of requiredFiles) {
	read(file);
}

const assertAssetMetadata = (relativePath) => {
	const contents = read(relativePath);

	if (!contents.includes("'dependencies' => array(")) {
		errors.push(`${relativePath} does not declare dependencies.`);
	}

	if (!contents.includes("'wp-api-fetch'")) {
		errors.push(`${relativePath} must include wp-api-fetch.`);
	}

	if (!/'version' => '[^']+'/.test(contents)) {
		errors.push(`${relativePath} does not declare a generated version.`);
	}
};

assertAssetMetadata('assets/build/admin-config.asset.php');
assertAssetMetadata('assets/build/block-panel.asset.php');

if (errors.length) {
	console.error('Build asset check failed:');
	for (const error of errors) {
		console.error(`- ${error}`);
	}
	process.exit(1);
}

console.log('Build asset check passed.');
