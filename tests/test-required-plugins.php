<?php
/**
 * Class SampleTest
 *
 * @package Vandergraaf
 */

/**
 * Sample test case.
 */
class RequiredPluginTest extends WP_UnitTestCase {

	private $cwd = "";


	function __construct()
	{
		$this->cwd = getcwd();
	}

	/**
	 * A single example test.
	 */

	function test_page_generator_installed() {

		$this->assertTrue( is_plugin_active('vandergraaf/vandergraaf.php') );
		$this->assertTrue( is_plugin_active('vandergraaf-page-generator/vandergraaf-page-generator.php') );

		do_action( "vandergraaf_generator_init" );

		$this->assertFalse( empty( apply_filters( "vandergraaf_process_post_types", array() ) ), print_r( apply_filters( "vandergraaf_process_post_types", array() ), true ) );

	}


	function test_page_types_registered() {

		do_action( "vandergraaf_generator_init" );

		$this->assertTrue( has_filter( "vandergraaf_generate_page" ) );
		$this->assertTrue( has_filter( "vandergraaf_generate_post" ) );
		$this->assertFalse( has_filter( "vandergraaf_generate_custom" ) );

	}

	function test_page_generator_filters_registered() {

		$VdG_page_generator = new VDG_Page_Generator();

		do_action( "vandergraaf_generator_init" );

		$this->assertTrue( $VdG_page_generator != null, "VDG Page Generator not created" );

		$this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_anchor_tags" ) ) == 10 );

        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_stylesheets" ) ) == 10 );
		//$this->assertTrue( has_filter( "vandergraaf_css_filter", array( $VdG_page_generator, "remap_internal_stylesheet_refs" ) ) == 10 );

        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_canonical_tags" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_icon_tags" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_seo_tags" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "process_ms_webapp_icon" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_scripts" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "remap_internal_images" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "process_inline_assets" ) ) == 10 );
        $this->assertTrue( has_filter( "vandergraaf_page_generator", array( $VdG_page_generator, "process_conditional_comments" ) ) == 10 );


        // Tell Van der Graaf which post types we handle
        $this->assertTrue( has_filter( "vandergraaf_process_post_types", array( $VdG_page_generator, "process_post_types" ) ) ==10 );


		// Register the destinations depending on extension
		$this->assertTrue( has_filter( "vandergraaf_asset_destination", array( $VdG_page_generator, "asset_destinations" ) ) == 10 );

	}


}
