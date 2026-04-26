#!/usr/bin/env node
/* eslint-env node */

const { spawnSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const overridePath = path.join( root, '.wp-env.override.json' );
const multisiteHome = path.join( root, '.wp-env-multisite' );
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
const testsPort = process.env.WP_ENV_TESTS_PORT || '8891';
const composeProjectName = process.env.COMPOSE_PROJECT_NAME || 'lerm_admin_config_multisite';
const sharedConfig = { ...( override.config || {} ) };

for ( const key of [ 'DOMAIN_CURRENT_SITE', 'PATH_CURRENT_SITE', 'WP_HOME', 'WP_SITEURL', 'WP_TESTS_DOMAIN' ] ) {
	delete sharedConfig[ key ];
}

function siteConfig( targetPort ) {
	return {
		DOMAIN_CURRENT_SITE: `localhost:${ targetPort }`,
		PATH_CURRENT_SITE: '/',
		WP_HOME: `http://localhost:${ targetPort }`,
		WP_SITEURL: `http://localhost:${ targetPort }`,
		WP_TESTS_DOMAIN: `localhost:${ targetPort }`,
	};
}

override.config = sharedConfig;
override.env = {
	...( override.env || {} ),
	development: {
		...( override.env?.development || {} ),
		config: {
			...( override.env?.development?.config || {} ),
			...siteConfig( port ),
		},
	},
	tests: {
		...( override.env?.tests || {} ),
		config: {
			...( override.env?.tests?.config || {} ),
			...siteConfig( testsPort ),
		},
	},
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
				COMPOSE_PROJECT_NAME: composeProjectName,
				WP_ENV_HOME: process.env.WP_ENV_HOME || multisiteHome,
				WP_ENV_MULTISITE: '1',
				WP_ENV_PORT: port,
				WP_ENV_TESTS_PORT: testsPort,
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
