const { test, expect } = require( '@playwright/test' );
const { execFileSync } = require( 'child_process' );

const HASH = 'ef301f25d1b8e2bca70fafc1316f1a92';

const screens = [
	[ 'liferuss', '/wp-admin/admin.php?page=liferuss' ],
	[ 'hub', '/wp-admin/admin.php?page=lr-academy' ],
	[ 'courses', '/wp-admin/admin.php?page=lr-academy-courses' ],
	[ 'outline', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=outline' ],
	[ 'price', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=price' ],
	[ 'publish', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=publish' ],
	[ 'info', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=info' ],
	[ 'quiz', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=quiz' ],
	[ 'seo', '/wp-admin/admin.php?page=lr-academy-course&id=6&step=seo' ],
	[ 'students', '/wp-admin/admin.php?page=lr-academy-students' ],
	[ 'student', '/wp-admin/admin.php?page=lr-academy-student&id=1' ],
	[ 'orders', '/wp-admin/admin.php?page=lr-academy-orders' ],
	[ 'order', '/wp-admin/admin.php?page=lr-academy-order&id=14' ],
	[ 'plans', '/wp-admin/admin.php?page=lr-academy-plans' ],
	[ 'qa', '/wp-admin/admin.php?page=lr-academy-qa' ],
	[ 'instructors', '/wp-admin/admin.php?page=lr-academy-instructors' ],
	[ 'reports', '/wp-admin/admin.php?page=lr-academy-reports' ],
	[ 'academy-settings', '/wp-admin/admin.php?page=lr-academy-settings' ],
	[ 'crm', '/wp-admin/admin.php?page=lr-crm' ],
	[ 'leads', '/wp-admin/admin.php?page=lr-leads' ],
	[ 'my-leads', '/wp-admin/admin.php?page=lr-my-leads' ],
	[ 'kanban', '/wp-admin/admin.php?page=lr-kanban' ],
	[ 'tasks', '/wp-admin/admin.php?page=lr-tasks' ],
	[ 'funnel', '/wp-admin/admin.php?page=lr-funnel' ],
	[ 'export', '/wp-admin/admin.php?page=lr-export' ],
	[ 'admission', '/wp-admin/admin.php?page=lr-req-admission' ],
	[ 'exchange', '/wp-admin/admin.php?page=lr-req-exchange' ],
	[ 'cargo', '/wp-admin/admin.php?page=lr-req-cargo' ],
	[ 'trade', '/wp-admin/admin.php?page=lr-req-trade' ],
	[ 'immigration', '/wp-admin/admin.php?page=lr-req-immigration' ],
	[ 'finance', '/wp-admin/admin.php?page=lr-finance' ],
	[ 'finance-report', '/wp-admin/admin.php?page=lr-finance-report' ],
	[ 'payments', '/wp-admin/admin.php?page=lr-payments' ],
	[ 'seo-health', '/wp-admin/admin.php?page=lr-seo-health' ],
	[ 'seo-keywords', '/wp-admin/admin.php?page=lr-seo-keywords' ],
	[ 'stale', '/wp-admin/admin.php?page=lr-stale' ],
	[ 'lead-sources', '/wp-admin/admin.php?page=lr-sources' ],
];

function authCookies() {
	const code = [
		"$user = get_user_by( 'login', 'admin' );",
		'$exp = time() + 12 * HOUR_IN_SECONDS;',
		"echo wp_json_encode( array( 'logged_in' => wp_generate_auth_cookie( $user->ID, $exp, 'logged_in' ), 'auth' => wp_generate_auth_cookie( $user->ID, $exp, 'auth' ) ) );",
	].join( ' ' );
	const raw = execFileSync( 'php', [ '/tmp/wp-cli.phar', 'eval', code, '--path=/tmp/liferuss-wp' ], { encoding: 'utf8' } );
	const start = raw.indexOf( '{' );
	return JSON.parse( raw.slice( start ) );
}

test.describe( 'admin screens do not scroll sideways', () => {
	let jar;

	test.beforeAll( () => {
		jar = authCookies();
	} );

	for ( const width of [ 1280, 1440 ] ) {
		for ( const [ name, path ] of screens ) {
			test( `${ name } at ${ width }`, async ( { browser } ) => {
				const context = await browser.newContext( { viewport: { width, height: 900 } } );
				const page = await context.newPage();
				const client = await context.newCDPSession( page );
				for ( const [ cookieName, value ] of [
					[ `wordpress_logged_in_${ HASH }`, jar.logged_in ],
					[ `wordpress_${ HASH }`, jar.auth ],
				] ) {
					await client.send( 'Network.setCookie', {
						name: cookieName,
						value,
						domain: '127.0.0.1',
						path: '/',
						httpOnly: true,
					} );
				}
				await page.goto( path, { waitUntil: 'domcontentloaded' } );
				const fit = await page.evaluate( () => document.documentElement.scrollWidth <= window.innerWidth );
				expect( fit, `${ name } overflows at ${ width }` ).toBe( true );
				await context.close();
			} );
		}
	}
} );
