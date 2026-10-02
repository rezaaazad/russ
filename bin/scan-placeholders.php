<?php
/**
 * Fail when public HTML still contains seed placeholder copy.
 *
 * Usage: php bin/scan-placeholders.php http://127.0.0.1:8080
 *
 * @package LifeRuss
 */

$base = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : 'http://127.0.0.1:8080';
$paths = array(
	'/',
	'/study-russia/',
	'/study-russia/bachelor/',
	'/study-russia/medicine/',
	'/study-russia/admission-steps/',
	'/study-russia/documents/',
	'/study-russia/visa/',
	'/padfak/',
	'/direct-course/',
	'/migration-russia/',
	'/migration-russia/residence/',
	'/migration-russia/work/',
	'/migration-russia/citizenship/',
	'/admission/',
	'/exchange/',
	'/exchange/transfer-to-russia/',
	'/exchange/tuition-payment/',
	'/exchange/currencies/',
	'/cargo/',
	'/cargo/air/',
	'/cargo/road/',
	'/cargo/rail/',
	'/cargo/sea/',
	'/cargo/customs/',
	'/trade/',
	'/trade/sourcing/',
	'/trade/import-export/',
	'/trade/representation/',
	'/russia-guide/',
	'/scholarships/',
	'/services/',
	'/costs/',
	'/ru/',
	'/ar/',
);
$needles = array(
	'(نمونه)',
	'متن نمونه',
	'این بخش نمونه',
	'نظر نمونه',
	'محتوای نمونه',
	'این متن نمونه',
	'سهمیه‌های نمونه',
	'پیش‌نویس منتشر نشود',
	'Higher Education · A Brighter Tomorrow',
	'Iran — Russia',
	'Iran ↔ Russia',
	'a stronger tomorrow',
	'Global connections',
	'>صافی<',
	'دادهٔ نمایشی',
	'989120000000',
	'wa.me/989120000000',
);
$failed = 0;
foreach ( $paths as $path ) {
	$html = @file_get_contents( $base . $path );
	if ( false === $html ) {
		echo "FETCH FAIL {$path}\n";
		++$failed;
		continue;
	}
	foreach ( $needles as $needle ) {
		if ( str_contains( $html, $needle ) ) {
			echo "PLACEHOLDER {$path} :: {$needle}\n";
			++$failed;
		}
	}
}
if ( $failed > 0 ) {
	echo "FAIL {$failed}\n";
	exit( 1 );
}
echo "OK " . count( $paths ) . " urls\n";
