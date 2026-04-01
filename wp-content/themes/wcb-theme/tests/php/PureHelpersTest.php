<?php
/**
 * Testes unitários — inc/wcb-pure-helpers.php
 *
 * @package WCB_Theme
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class PureHelpersTest extends TestCase {

	public function test_normalize_cep_digits_strips_non_digits(): void {
		$this->assertSame( '01310100', wcb_normalize_cep_digits( '01310-100' ) );
		$this->assertSame( '12345678', wcb_normalize_cep_digits( '12.345-678' ) );
		$this->assertSame( '', wcb_normalize_cep_digits( 'abc' ) );
		$this->assertSame( '', wcb_normalize_cep_digits( '' ) );
	}

	public function test_promocoes_should_use_post_in_within_limit(): void {
		$ids = range( 1, 10 );
		$this->assertTrue( wcb_promocoes_should_use_post_in( $ids, 500 ) );
		$this->assertTrue( wcb_promocoes_should_use_post_in( $ids, 10 ) );
		$this->assertFalse( wcb_promocoes_should_use_post_in( $ids, 9 ) );
	}

	public function test_promocoes_should_use_post_in_empty(): void {
		$this->assertTrue( wcb_promocoes_should_use_post_in( array(), 0 ) );
		$this->assertTrue( wcb_promocoes_should_use_post_in( array(), 500 ) );
	}

	public function test_promocoes_negative_max_treated_as_zero(): void {
		$this->assertFalse( wcb_promocoes_should_use_post_in( array( 1 ), -1 ) );
	}
}
