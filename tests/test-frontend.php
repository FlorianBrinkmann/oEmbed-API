<?php
/**
 * Test frontend stuff.
 *
 * @package WP_oEmbed
 */

/**
 * Class WP_oEmbed_Test_Frontend.
 */
class WP_oEmbed_Test_Frontend extends WP_oEmbed_TestCase {
	/**
	 * API route class instance.
	 * @var WP_oEmbed_Frontend
	 */
	protected $class;

	/**
	 * Runs before each test.
	 */
	function setUp() {
		parent::setUp();

		$this->class = new WP_oEmbed_Frontend();
	}

	/**
	 * Runs after each test.
	 */
	function tearDown() {
		parent::tearDown();

		unset( $this->class );
	}

	/**
	 * Test output of add_oembed_discovery_links.
	 */
	function test_add_oembed_discovery_links_non_singular() {
		ob_start();
		$this->class->add_oembed_discovery_links();
		$actual = ob_get_clean();
		$this->assertEquals( '', $actual );
	}

	/**
	 * Test output of add_oembed_discovery_links.
	 */
	function test_add_oembed_discovery_links() {
		$post_id = $this->factory->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertQueryTrue( 'is_single', 'is_singular' );

		ob_start();
		$this->class->add_oembed_discovery_links();
		$actual = ob_get_clean();

		$expected = '<link rel="alternate" type="application/json+oembed" href="' . esc_url( get_oembed_endpoint_url( get_permalink() ) ) . '" />' . "\n";
		$expected .= '<link rel="alternate" type="text/xml+oembed" href="' . esc_url( get_oembed_endpoint_url( get_permalink(), 'xml' ) ) . '" />' . "\n";

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test filter_oembed_result_trusted method.
	 */
	function test_filter_oembed_result_trusted() {
		$html   = '<p></p><iframe onload="alert(1)"></iframe>';
		$actual = $this->class->filter_oembed_result( $html, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' );

		$this->assertEquals( $html, $actual );
	}

	/**
	 * Test filter_oembed_result_trusted method.
	 */
	function test_filter_oembed_result_untrusted() {
		$html   = '<p></p><iframe onload="alert(1)"></iframe>';
		$actual = $this->class->filter_oembed_result( $html, '' );

		$this->assertEquals( '<iframe sandbox="allow-scripts" security="restricted"></iframe>', $actual );
	}

	/**
	 * Test that only 1 iframe is allowed, nothing else.
	 */
	function test_filter_oembed_result_multiple_tags() {
		$html   = '<div><iframe></iframe><iframe></iframe><p></p></div>';
		$actual = $this->class->filter_oembed_result( $html, '' );

		$this->assertEquals( '<iframe sandbox="allow-scripts" security="restricted"></iframe>', $actual );
	}

	/**
	 * Test filter_oembed_result_trusted method for current site.
	 */
	function test_filter_oembed_result_current_site() {
		$html   = '<p></p><iframe onload="alert(1)"></iframe>';
		$actual = $this->class->filter_oembed_result( $html, home_url( '/' ) );

		$this->assertEquals( '<iframe sandbox="allow-scripts" security="restricted"></iframe>', $actual );
	}

	/**
	 * Test filter_oembed_result_trusted method without iframe.
	 */
	function test_filter_oembed_result_no_iframe() {
		$html   = '<span>Hello</span><p>World</p>';
		$actual = $this->class->filter_oembed_result( $html, '' );

		$this->assertEquals( 'HelloWorld', $actual );

		$html   = '<div><p></p></div><script></script>';
		$actual = $this->class->filter_oembed_result( $html, '' );

		$this->assertEquals( '', $actual );
	}

	/**
	 * Test if the secret is appended to the URL.
	 */
	function test_filter_oembed_result_secret() {
		$html   = '<iframe src="https://wordpress.org"></iframe>';
		$actual = $this->class->filter_oembed_result( $html, '' );

		$matches = array();
		preg_match( '|src="https://wordpress.org#\?secret=([\w\d]+)" data-secret="([\w\d]+)"|', $actual, $matches );

		$this->assertTrue( isset( $matches[1] ) );
		$this->assertTrue( isset( $matches[2] ) );
		$this->assertEquals( $matches[1], $matches[2] );
	}

	/**
	 * Test add_host_js method.
	 */
	function test_add_host_js() {
		ob_start();
		$this->class->add_host_js();
		$actual = ob_get_clean();

		$this->assertTrue( false !== strpos( $actual, '<script type="text/javascript">' ) );
	}

	/**
	 * Test rest_oembed_output method.
	 */
	function test_rest_oembed_output() {
		$user = $this->factory->user->create_and_get( array(
			'display_name' => 'John Doe',
		) );
		$post = $this->factory->post->create_and_get( array(
			'post_author'  => $user->ID,
			'post_title'   => 'Hello World',
			'post_content' => 'Foo Bar',
		) );

		ob_start();
		$this->class->rest_oembed_output( $post );
		$actual = ob_get_clean();

		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadHTML( $actual ) );

		$this->assertTrue( false !== strpos( $doc->saveHTML(), '<p class="wp-embed-excerpt">Foo Bar</p>' ) );
	}
}
