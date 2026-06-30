<?php
/**
 * Standalone check for the money formula. Run: php tests/calc-test.php
 *
 * Uses an explicit comparison (not assert()) so it fails loudly regardless of zend.assertions.
 *
 * @package WooCustomerGroupDiscount
 */

require __DIR__ . '/../includes/calc.php';

$fail = 0;
function check( $got, $want, $label, &$fail ) {
	if ( $got !== $want ) {
		fwrite( STDERR, "FAIL {$label}: got {$got} want {$want}\n" );
		$fail++;
	}
}

check( wcgd_calc_discount( 100, 15 ), 15.0, '100 @ 15%', $fail );
check( wcgd_calc_discount( 100, 0 ), 0.0, '0%', $fail );
check( wcgd_calc_discount( 0, 50 ), 0.0, 'zero amount', $fail );
check( wcgd_calc_discount( 99.99, 10 ), 10.0, 'rounding 99.99 @ 10%', $fail );
check( wcgd_calc_discount( 100, 150 ), 100.0, 'percent clamped to 100', $fail );
check( wcgd_calc_discount( 100, -5 ), 0.0, 'percent clamped to 0', $fail );

if ( $fail ) {
	fwrite( STDERR, "calc-test: {$fail} failure(s)\n" );
	exit( 1 );
}
echo "calc-test: OK\n";
