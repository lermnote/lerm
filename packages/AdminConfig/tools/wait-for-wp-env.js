#!/usr/bin/env node
/* eslint-env node */

const http = require( 'http' );

const port = process.env.WP_ENV_PORT || '8888';
const timeoutMs = Number.parseInt( process.env.WP_ENV_WAIT_TIMEOUT || '60000', 10 );
const intervalMs = 1000;
const startedAt = Date.now();
const target = `http://localhost:${ port }/wp-login.php`;
const maxBodyBytes = 128 * 1024;

function redirectsAwayFromTargetPort( response ) {
	const location = response.headers.location;

	if ( ! location ) {
		return false;
	}

	try {
		const redirect = new URL( location, target );

		return (
			[ 'localhost', '127.0.0.1' ].includes( redirect.hostname ) &&
			redirect.port !== '' &&
			redirect.port !== port
		);
	} catch ( error ) {
		return false;
	}
}

function wait() {
	let settled = false;
	const request = http.get( target, ( response ) => {
		let body = '';

		response.setEncoding( 'utf8' );
		response.on( 'data', ( chunk ) => {
			if ( body.length < maxBodyBytes ) {
				body += chunk;
			}
		} );
		response.on( 'end', () => {
			if ( settled ) {
				return;
			}

			settled = true;

			if ( isReadyResponse( response, body ) ) {
				process.stdout.write( `wp-env is ready at ${ target }\n` );
				process.exit( 0 );
				return;
			}

			retry();
		} );
	} );

	request.on( 'error', () => {
		if ( settled ) {
			return;
		}

		settled = true;
		retry();
	} );
	request.setTimeout( intervalMs, () => {
		if ( settled ) {
			return;
		}

		settled = true;
		request.destroy();
		retry();
	} );
}

function isReadyResponse( response, body ) {
	if ( ! response.statusCode || response.statusCode >= 500 || redirectsAwayFromTargetPort( response ) ) {
		return false;
	}

	if ( body.includes( 'The site you have requested is not installed' ) || body.includes( 'wp-admin/install.php' ) ) {
		return false;
	}

	if ( body.includes( 'id="loginform"' ) || body.includes( 'name="log"' ) ) {
		return true;
	}

	if ( response.statusCode >= 300 && response.statusCode < 400 ) {
		return false;
	}

	return false;
}

function retry() {
	if ( Date.now() - startedAt > timeoutMs ) {
		process.stderr.write( `Timed out waiting for ${ target }\n` );
		process.exitCode = 1;
		return;
	}

	setTimeout( wait, intervalMs );
}

wait();
