#!/usr/bin/env node
/* eslint-env node */

const { spawnSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const overridePath = path.join( root, '.wp-env.override.json' );
const args = process.argv.slice( 2 );

if ( args.length === 0 ) {
	console.error( 'Usage: node tools/wp-env-multisite.js <wp-env command...>' );
	process.exit( 1 );
}

const hadOverride = fs.existsSync( overridePath );
const previousOverride = hadOverride ? fs.readFileSync( overridePath, 'utf8' ) : '';
let override = {};

if ( hadOverride && previousOverride.trim() !== '' ) {
	override = JSON.parse( previousOverride );
}

override.multisite = true;

const port = process.env.WP_ENV_PORT || '8890';
override.config = {
	...( override.config || {} ),
	DOMAIN_CURRENT_SITE: `localhost:${ port }`,
	PATH_CURRENT_SITE: '/',
	WP_HOME: `http://localhost:${ port }`,
	WP_SITEURL: `http://localhost:${ port }`,
};

fs.writeFileSync( overridePath, `${ JSON.stringify( override, null, 2 ) }\n` );

try {
	const wpEnvBin = path.join(
		root,
		'node_modules',
		'@wordpress',
		'env',
		'bin',
		'wp-env'
	);
	const result = spawnSync(
		process.execPath,
		[ wpEnvBin, ...args ],
		{
			cwd: root,
			env: {
				...process.env,
				WP_ENV_PORT: port,
				WP_ENV_TESTS_PORT: process.env.WP_ENV_TESTS_PORT || '8891',
			},
			stdio: 'inherit',
		}
	);

	if ( result.error ) {
		console.error( result.error.message );
	}

	process.exitCode = result.status ?? 1;
} finally {
	if ( hadOverride ) {
		fs.writeFileSync( overridePath, previousOverride );
	} else if ( fs.existsSync( overridePath ) ) {
		fs.unlinkSync( overridePath );
	}
}
