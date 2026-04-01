<?php
/**
 * Runner mínimo de testes (sem PHPUnit) — mesma cobertura que PureHelpersTest.
 * Use quando `composer install` ainda não instalou o PHPUnit (ex.: PHP sem ext-zip).
 *
 * @package WCB_Theme
 */

declare( strict_types=1 );

require __DIR__ . '/bootstrap.php';

$failed = 0;

$assert = static function ( bool $ok, string $msg ) use ( &$failed ): void {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: {$msg}\n" );
		++$failed;
	}
};

// wcb_normalize_cep_digits
$assert( wcb_normalize_cep_digits( '01310-100' ) === '01310100', 'CEP com hífen' );
$assert( wcb_normalize_cep_digits( '12.345-678' ) === '12345678', 'CEP com pontos' );
$assert( wcb_normalize_cep_digits( 'abc' ) === '', 'CEP só letras' );
$assert( wcb_normalize_cep_digits( '' ) === '', 'CEP vazio' );

// wcb_promocoes_should_use_post_in
$ids = range( 1, 10 );
$assert( wcb_promocoes_should_use_post_in( $ids, 500 ) === true, 'post_in dentro do limite 500' );
$assert( wcb_promocoes_should_use_post_in( $ids, 10 ) === true, 'post_in no limite exato' );
$assert( wcb_promocoes_should_use_post_in( $ids, 9 ) === false, 'post_in acima do limite' );
$assert( wcb_promocoes_should_use_post_in( array(), 0 ) === true, 'lista vazia max 0' );
$assert( wcb_promocoes_should_use_post_in( array( 1 ), -1 ) === false, 'max negativo tratado como 0' );

if ( $failed > 0 ) {
	fwrite( STDERR, "\n{$failed} falha(s).\n" );
	exit( 1 );
}

echo "OK: tests/php/run-pure-tests.php (" . ( 9 ) . " asserts)\n";
exit( 0 );
