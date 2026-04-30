/* eslint-env node */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin-config': path.resolve( __dirname, 'resources/admin/index.js' ),
		'block-panel': path.resolve( __dirname, 'resources/block-panel/index.js' ),
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'assets/build' ),
	},
};
