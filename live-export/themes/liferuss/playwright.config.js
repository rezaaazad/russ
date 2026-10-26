const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests',
	timeout: 90000,
	expect: { timeout: 15000 },
	use: {
		baseURL: 'http://127.0.0.1:8080',
		launchOptions: {
			executablePath: '/usr/local/bin/google-chrome',
			args: [ '--no-sandbox', '--disable-dev-shm-usage' ],
		},
	},
} );
