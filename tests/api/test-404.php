<?php

class RedirectionApi404Test extends Redirection_Api_Test {
	public static function setupBeforeClass() {
		global $wpdb;

		$wpdb->query( "TRUNCATE {$wpdb->prefix}redirection_404" );
	}

	private function isAthenB( $result ) {
		$this->assertEquals( 'test1', $result['items'][0]['url'] );
		$this->assertEquals( 'test2', $result['items'][1]['url'] );
	}

	private function isBthenA( $result ) {
		$this->assertEquals( 'test2', $result['items'][0]['url'] );
		$this->assertEquals( 'test1', $result['items'][1]['url'] );
	}

	private function createAB( $total = 2 ) {
		for ( $i = 0; $i < $total; $i++ ) {
			RE_404::create( 'test' . ( $i + 1 ), 'agent', '192.168.1.' . ( $i + 1 ), 'referrer' );
		}

		$this->setNonce();
	}

	public function testNoPermission() {
		$this->setUnauthorised();
		$result = $this->callApi( '404' );

		$this->assertEquals( 403, $result->status );
		$this->assertEquals( 'rest_forbidden', $result->data['code'] );
	}

	public function testListBadOrderBy() {
		$this->createAB();

		$result = $this->callApi( '404', array( 'orderby' => 'cats' ) );

		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testListBadDirection() {
		$this->createAB();
		$result = $this->callApi( '404', array( 'direction' => 'cats' ) );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testGet404() {
		$this->createAB();

		$result = $this->callApi( '404' );

		$this->isBthenA( $result->data );
	}

	public function testListBadPerPage() {
		$this->createAB( 5 );

		$result = $this->callApi( '404', array( 'per_page' => 'cats' ) );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testListPerPageMin() {
		$this->createAB( 20 );

		$result = $this->callApi( '404', array( 'per_page' => 5 ) );
		$this->assertEquals( 5, count( $result->data['items'] ) );
	}

	public function testListPerPageMax() {
		$this->createAB( 201 );

		$result = $this->callApi( '404', array( 'per_page' => 201 ) );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testListBadPage() {
		$this->createAB( 20 );

		$result = $this->callApi( '404', array( 'per_page' => 10, 'page' => 'cats' ) );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testIPFilter() {
		$this->createAB( 5 );

		$result = $this->callApi( '404', array( 'filterBy' => [ 'ip' => '192.168.1.1' ] ) );
		$this->assertEquals( 1, count( $result->data['items'] ) );
	}

	public function testBadIPFilter() {
		$this->createAB();

		$result = $this->callApi( '404', array( 'filterBy' => 'cats' ) );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );

		$result = $this->callApi( '404', [ 'filterBy' => [ 'cats' => 'thing' ] ] );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testDeleteAll() {
		$this->createAB( 5 );

		$result = $this->callApi( '404', array( 'filterBy' => 'cat' ), 'POST' );
		$this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testDeleteFilter() {
		$this->createAB( 5 );

		// Create 5 log entries, delete 1 of them, 4 are left
		$result = $this->callApi( '404', [ 'filterBy' => [ 'ip' => '192.168.1.1' ] ], 'POST' );
		$result = $this->callApi( '404' );

		$this->assertEquals( 4, count( $result->data['items'] ) );
	}

	public function testBadBulk() {
		$this->createAB();
		$group = Red_Group::create( 'test', 1 );

		$result = $this->callApi( 'bulk/404/cats', array( 'items' => '1' ), 'POST' );
		$this->assertEquals( 'rest_no_route', $result->data['code'] );

		// $result = $this->callApi( 'bulk/404/delete', array( 'items' => 'x' ), 'POST' );
		// $this->assertEquals( 'rest_invalid_param', $result->data['code'] );
	}

	public function testDeleteBulk() {
		$this->setNonce();
		RE_404::create( 'test1', 'agent', '192.168.1.1', 'referrer' );
		$last = RE_404::create( 'test2', 'agent', '192.168.1.2', 'referrer' );

		$result = $this->callApi( 'bulk/404/delete', array( 'items' => $last ), 'POST' );
		$this->assertEquals( 1, count( $result->data['items'] ) );
	}
}
