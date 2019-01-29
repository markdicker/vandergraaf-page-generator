<?php
/**
 * Class SampleTest
 *
 * @package Vandergraaf
 */

/**
 * Sample test case.
 */
class FilterTest extends WP_UnitTestCase {

	private $cwd = "";


	function __construct()
	{
		$this->cwd = getcwd();
	}

	/**
	 * A single example test.
	 */
	function test_remap_anchor_tags() {

		$dest_path = array( "output", "remap_anchor_tags" );

		$start_path = $this->cwd."/tests/";
		$from_path = $this->cwd."/tests/data";
		$from_url = "http://test.com";
		$to_url = "http://live.com";

		$VdG_page_generator = new VDG_Page_Generator();

		$to_path = $VdG_page_generator->build_protected_directory_tree( $start_path, $dest_path );

		$this->assertTrue( is_dir( $to_path ), $to_path );

		$html=file_get_contents( $from_path."/test1.html" );

		$result = $VdG_page_generator->remap_internal_anchor_tags( $html, 0, $from_url, $to_url, $from_path, $to_path );

		$this->assertFalse( ( strpos( $html, $from_url ) == 0 ) );

		$this->assertTrue( ( strpos( $result, $to_url ) != 0 ) );

		// save our file so we can eye ball it if neccesary
		$VdG_page_generator->save_file( $to_path."/test_remap_internal_anchor_tags.html", $result );

		$this->assertTrue( is_file( $to_path."/test_remap_internal_anchor_tags.html" ) );
	}

	function test_remap_internal_stylesheets() {

		$dest_path = array( "output", "remap_internal_stylesheets" );

		$start_path = $this->cwd."/tests/";

		$from_path = $this->cwd."/tests/data";
		$from_url = "http://test.com";
		$to_url = "http://live.com";

		$VdG_page_generator = new VDG_Page_Generator();

		$to_path = $VdG_page_generator->build_protected_directory_tree( $start_path, $dest_path );

		$this->assertTrue( is_dir( $to_path ), $to_path );

		$html=file_get_contents( $from_path."/test1.html" );

		$result = $VdG_page_generator->remap_internal_stylesheets( $html, 0, $from_url, $to_url, $from_path, $to_path );

		$this->assertFalse( ( strpos( $html, $from_url ) == 0 ) );

		$this->assertTrue( ( strpos( $result, $to_url ) != 0 ) );

		$this->assertTrue( is_dir( $to_path."/css" ) );

		$this->assertTrue( is_file( $to_path."/css/.htaccess" ) );

		$this->assertTrue( is_file( $to_path."/css/index.html" ) );

		$this->assertFalse( is_file( $to_path."/css/styles.css" ) );

		// save our file so we can eye ball it if neccesary
		$VdG_page_generator->save_file( $to_path."/test_remap_internal_stylesheets.html", $result );

		$this->assertTrue( is_file( $to_path."/test_remap_internal_stylesheets.html" ) );

	}

	function test_asset_destination_filter() {

		// Initialise ourpage generator

		$VdG_page_generator = new VDG_Page_Generator();

		do_action( "vandergraaf_generator_init" );

		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "css" ), "/css/" );

		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "png" ), "/images/" );
		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "jpg" ), "/images/" );
		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "jpeg" ), "/images/" );
		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "svg" ), "/images/" );

		$this->assertEquals( apply_filters( "vandergraaf_asset_destination", "/assets/", "gif" ), "/assets/" );

		$this->assertNotEquals( apply_filters( "vandergraaf_asset_destination", "/gifs/", "gif" ), "/assets/" );

	}


}
