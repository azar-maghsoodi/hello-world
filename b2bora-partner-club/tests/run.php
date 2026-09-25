<?php
/**
 * Tiny standalone test runner (no PHPUnit/Composer dependency required).
 * Usage: php tests/run.php
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/test-plugin.php';

$functions = get_defined_functions()['user'];
$tests     = array_values( array_filter( $functions, static fn( $name ) => str_starts_with( $name, 'test_' ) ) );
sort( $tests );

$passed = 0;
$failed = 0;

foreach ( $tests as $test ) {
	b2bora_test_reset_state();

	try {
		$test();
		echo "PASS  {$test}\n";
		++$passed;
	} catch ( Throwable $e ) {
		echo "FAIL  {$test}\n      " . $e->getMessage() . "\n";
		++$failed;
	}
}

printf( "\n%d passed, %d failed, %d total\n", $passed, $failed, $passed + $failed );

exit( $failed > 0 ? 1 : 0 );
