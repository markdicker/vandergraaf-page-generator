<?php
/**
 * Plugin Name:     VdG Page Generator
 * Description:     Filters the html to generate a static page
 * Author:          Mark Dicker
 * Author URI:      vandergraaf.io
 * Text Domain:     vandergraaf-page-generator
 * Domain Path:     /languages
 * Version:         0.0.2
 *
 * @package         Vandergraaf_Page_Generator
 * 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

/**
 *	Copyright (C) 2012-2017 Mark Dicker (email: mark@markdicker.co.uk)
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require ( "vpg-updater.php" );

require ( "class-page-generator.php" );

if ( ! class_exists( 'VDG_Page_Generator' ) ) :


// May need to move this inside the class
define ( 'CSS_URLS_REGEX', '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i' );
define ( 'CSS_IMPORTS_REGEX' , '/(@import) (url)\(([^>]*?)\)/' );

class VDG_Page_Generator extends Van_der_Graaf_Page_Generator
{

    /*
     *
     * Perform any initialisation now
     *
     */

    private $Updater = null;

	private $VdG = null;

    public $Storage = null;

    function __construct()
    {

		// Setup our page generator
		add_action( "vandergraaf_generator_init", array( $this, "setup_page_generator" ), 10 );

        $Updater = new VanderGraaf_Page_Generator_Updater();

	}


	function setup_page_generator( $vandergraaf )
	{

        //  Add our processing filters here

        add_action( "vandergraaf_generate_page", array( $this, "generate_page" ) , 10 );
        add_action( "vandergraaf_generate_post", array( $this, "generate_post" ) , 10 );
        // add_action( "vandergraaf_generate_category", array( $this, "generate_category_page" ) , 10 );
		// add_action( "vandergraaf_generate_tag", array( $this, "generate_tag_page" ) , 10 );
		add_action( "vandergraaf_generate_category", array( $this, "generate_category_page" ) , 10 );
		add_action( "vandergraaf_generate_post_tag", array( $this, "generate_post_tag_page" ) , 10 );

        // These will do all the work
        //add_filter( "vandergraaf_page_generator", array( $this, "process_html" ) , 10, 6 );  // for debuging
        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_anchor_tags" ) , 9, 7 );

        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_stylesheets" ) , 10, 7 );
			add_filter( "vandergraaf_page_generator_css_filter", array( $this, "remap_internal_stylesheet_refs" ) , 10, 6 );

        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_canonical_tags" ) , 10, 7 );
        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_icon_tags" ) , 10, 7 );
        //add_filter( "vandergraaf_page_generator", array( $this, "remap_seo_tags" ) , 10, 6 );
        add_filter( "vandergraaf_page_generator", array( $this, "process_ms_webapp_icon" ) , 10, 7 );
        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_scripts" ) , 10, 7 );
        add_filter( "vandergraaf_page_generator", array( $this, "remap_internal_images" ) , 10, 7 );
        add_filter( "vandergraaf_page_generator", array( $this, "process_inline_assets" ) , 10, 7 );
        add_filter( "vandergraaf_page_generator", array( $this, "process_conditional_comments" ) , 10, 7 );


        // Tell Van der Graaf which post types we handle
        add_filter( "vandergraaf_process_post_types", array( $this, "process_post_types" ), 10 );


        // Tell Van der Graaf which post types we handle
        add_filter( "vandergraaf_process_taxonomy_types", array( $this, "process_taxonomy_types" ), 10 );


		// Register the destinations depending on extension
		add_filter( "vandergraaf_asset_destination", array( $this, "asset_destinations" ), 10, 2 );

    }

    function process_post_types( $post_types )
    {
        return array_merge( $post_types, array( "post", "page" ) );
    }


    function process_taxonomy_types( $taxonomies )
    {
        return array_merge( $taxonomies, array( "category", "post_tag" ) );
    }


    function generate_page( $id )
    {

        // $storage_class = get_option( "VDG_DEPLOY_TO" );

        // $storage_options = apply_filters( "vandergraaf_deploy_to", array() );

        // $this->Storage = new $storage_options[ $storage_class ][1]();

        //wp_die( print_r( $this->Storage ) );

        $permalink = get_permalink( $id );

        //$html_generator = new VDG_Page_Generator();

		$this->generate_page_from_path( $permalink, $id, "page" );

	}

    function generate_post( $id )
    {

        // $storage_class = get_option( "VDG_DEPLOY_TO" );

        // $storage_options = apply_filters( "vandergraaf_deploy_to", array() );

        // $this->Storage = new $storage_options[ $storage_class ][1]();

        $permalink = get_permalink( $id );

        //$html_generator = new VDG_Page_Generator();

		$this->generate_page_from_path( $permalink, $id, "post" );

	}

    function generate_category_page( $id )
    {

        // $storage_class = get_option( "VDG_DEPLOY_TO" );

        // $storage_options = apply_filters( "vandergraaf_deploy_to", array() );

        // $this->Storage = new $storage_options[ $storage_class ][1]();

        $permalink = get_category_link( $id );

        //$html_generator = new VDG_Page_Generator();

		$this->generate_page_from_path( $permalink, $id, "category" );

	}


    // function generate_category_page( $id )
    // {

    //     $permalink = get_term_link( (int)$id );

	// 	//$html_generator = new VDG_Page_Generator();

	// 	$this->generate_page_from_path( $permalink, $id, "category" );

	// }


    function generate_post_tag_page( $id )
    {

        // $storage_class = get_option( "VDG_DEPLOY_TO" );

        // $storage_options = apply_filters( "vandergraaf_deploy_to", array() );

        // $this->Storage = new $storage_options[ $storage_class ][1]();

        $permalink = get_term_link( (int)$id );

		//$html_generator = new VDG_Page_Generator();

		$this->generate_page_from_path( $permalink, $id, "post_tag" );

	}



	function generate_page_from_path( $permalink, $id, $post_type )
	{
	global $VdG;

        $storage_class = get_option( "VDG_DEPLOY_TO" );

        $storage_options = apply_filters( "vandergraaf_deploy_to", array() );

        $this->Storage = new $storage_options[ $storage_class ][1]();

		//var_dump( $VdG );
        // echo "<small>Processing ".$permalink." - </small>"; flush();

        $VdG->send_SSE( 0, "Comment", "generate page from path" );
        
		$VdG->incStatus( $id, $post_type );

        $base_path = $this->to_path( );

        //echo "<pre>base_path = ". $base_path. "</pre>";

        // write_log( $this->to_url() );

        $site_permalink = $this->replace_urls( $permalink, $this->to_url(), $this->from_url() );

        $VdG->send_SSE( 0, "Comment", "site_permalink = ".$site_permalink );
        
        $_permalink = trailingslashit( $permalink )."index";

        $VdG->send_SSE( 0, "Comment", "_permalink = ".$_permalink );

        $paths = explode( "/",
                    $this->replace_urls( $_permalink,
                                 array ( $this->to_url(), $this->from_url() ),
                                 array ( "", "" ) ).".html" );

        $VdG->send_SSE( 0, "Comment", "paths = ".print_r( $paths, true )  );
                                 
        $file = array_pop( $paths );

        $home_path = $this->from_path();

        $VdG->send_SSE( 0, "Comment", "home_paths = ".$home_path  );

        // $final_path = $base_path;

        $VdG->send_SSE( 0, "Comment", "build protected directory tree" );

        $final_path = $this->build_protected_directory_tree( $this->to_path(), $paths );

        $VdG->send_SSE( 0, "Comment", "final_path = ".$final_path  );
        
        if ( is_file( $final_path.".html" ) )
        {
            chmod( $final_path.".html", 0666 );
            unlink( $final_path.".html" );
        }

        $final_path .= '/'.$file;

        // Get the web page into a dom style system
        $html = $this->file_get_html( $site_permalink );

        $VdG->send_SSE( 0, "Comment", $html );
        
        $VdG->send_SSE( 0, "Comment", "got page -> apply filters"  );

        write_log( "->to_path = ". $this->to_path() );
        write_log( "->from_path = ". $this->from_path() );
        
        $html = apply_filters( "vandergraaf_page_generator", $html, $id, $post_type, $this->from_url(), $this->to_url(), $this->from_path(), $this->to_path() );

        $VdG->send_SSE( 0, "Comment", "save generated html"  );

        // $this->build_html_page( $permalink );

        $this->save_file( $final_path, $html );

        $VdG->send_SSE( 0, "Clear up our shit"  );
		// clear up any memory allocation issues
		$html->clear();
		unset($html);

        $VdG->send_SSE( 0, "Comment", "generate page from path [end]" );
        
		$VdG->setStatus( $id, $post_type, 99999 );

        // echo "<small><b>Complete</b> - ".$final_path."</small><br />";  flush();

    }


    function remap_stylesheet ( $href, $to_path, $from_path, $from_url, $to_url )
    {
	global $VdG;

        // echo "<pre>".$src."</pre>";

        // write_log( "href = [".$href."]");

        $parts = parse_url( $href );

        write_log( $parts );

		$old_path = $href ;

        // echo "<pre>".print_r( $parts, true )."</pre>";

        $new_path = md5( $parts['path'] ).".".pathinfo( $parts['path'], PATHINFO_EXTENSION);

		// $VdG->send_SSE( 0, "Comment", "Checking cache for ".$href );
        
        write_log( "to_path = [".$to_path."]");

		if ( !$VdG->is_cached_file( $old_path ) )
		{


			$bdp = $this->build_protected_directory_tree ( $to_path, array('css') );

			// echo "bdp = ".$bdp."\n";

			// echo "<pre>".trailingslashit( $from_path )."</pre>";
			// echo "parts\n".$parts['path']."\n";

			$full_src_path = "/".implode( "/", array_merge( explode( "/", trim( $from_path, "/" ) ), explode( "/", trim ( $parts['path'], "/" ) ) ) );

			// echo "fsp = ".$full_src_path."\n";

			$full_dest_path = "/".implode( "/", array_merge( explode( "/", trim( $to_path, "/" ) ), explode( "/", "css/".trim ( $new_path, "/" ) ) ) );

			// echo "fdp = ".$full_dest_path."\n";

            write_log( "fsp = [".$full_src_path."]");
            write_log( "fdp = [".$full_dest_path."]");

			$this->copy_css( $full_src_path, $full_dest_path, $to_path, $from_path, $from_url, $to_url );

			$href = $to_url. "/css/" .$new_path;

			if ( isset( $parts[ 'query' ] ) )
				$href .= "?".$parts[ 'query' ];


			// Cache the new file
			$VdG->cache_file( $old_path, $href );

		}
		else
		{
			// echo "<pre>Using Cache</pre>";
			// $VdG->send_SSE( 0, "Comment", "Using cache for ".$href );

			$href = $VdG->cached_file( $old_path );

			$VdG->send_SSE( 0, "Comment", "Cached file = ".$href );
		}

        $VdG->send_SSE( 0, "Comment", "remap stylesheet" );
        
		//$VdG->incStatus( $id, $post_type );

        return $href;
    }


    function remap_script ( $src, $to_path, $from_path, $from_url, $to_url )
    {
	global $VdG;
        // echo "<pre>".$src."</pre>";

        $parts = parse_url( $src );

        // echo "<pre>".print_r( $parts, true )."</pre>";


        $new_path = md5( $parts['path'] ).".".pathinfo( $parts['path'], PATHINFO_EXTENSION);

		$old_path = $src;

		// $VdG->send_SSE( 0, "Comment", "Checking cache for ".$src );

		if ( !$VdG->is_cached_file( $old_path ) )
		{
			$this->build_protected_directory_tree ( $to_path, array( 'js' ) );

			// echo "<pre>".trailingslashit( $from_path )."</pre>";
			// echo "<pre>".$parts['path']."</pre>";

            write_log( "from_path = ".$from_path );
            write_log(  trailingslashit( $from_path ).$parts['path'] );

			$this->copy( trailingslashit( $from_path ).$parts['path'], trailingslashit( $to_path )."/js/".$new_path );
            // $this->copy( trailingslashit( $from_path ).$parts['path'], trailingslashit( $to_path )."/assets/".$new_path );

			$src = $to_url."/js/".$new_path;

			if ( isset( $parts[ 'query' ] ) )
				$src .= "?".$parts[ 'query' ];

			// Cache the new file
			$VdG->cache_file( $old_path, $src );

		}
		else
		{
			// echo "<pre>Using Cache</pre>";
			// $VdG->send_SSE( 0, "Comment", "Using cache for ".$src );

			$src = $VdG->cached_file( $old_path );
		}

		// $VdG->incStatus( $id, $post_type );

        return $src;
    }



    function remap_file ( $src, $to_path, $from_path, $from_url, $to_url )
    {
	global $VdG;
        // echo "<pre>".$src."</pre>";

        $parts = parse_url( $src );

        // echo "<pre>".print_r( $parts, true )."</pre>";

        $new_path = md5( $parts['path'] ).".".pathinfo( $parts['path'], PATHINFO_EXTENSION);

		$old_path = $src;

		// $VdG->send_SSE( 0, "Comment", "Checking cache for ".$src );

		if ( !$VdG->is_cached_file( $old_path ) )
		{
			$this->build_protected_directory_tree ( $to_path, array( 'assets' ) );

			// echo "<pre>".trailingslashit( $from_path )."</pre>";
			// echo "<pre>".$parts['path']."</pre>";

			$this->copy(  trailingslashit( $from_path ).$parts['path'], trailingslashit( $to_path )."/assets/".$new_path );

			$src = $this->to_url()."/assets/".$new_path;

			if ( isset( $parts[ 'query' ] ) )
				$src .= "?".$parts[ 'query' ];
			// Cache the new file
			$VdG->cache_file( $old_path, $src );

		}
		else
		{
			// echo "<pre>Using Cache</pre>";
			// $VdG->send_SSE( 0, "Comment", "Using cache for ".$src );

			$src = $VdG->cached_file( $old_path );
		}

		// $VdG->incStatus( $id, $post_type );


        return $src;
    }


    function move_image( $src, $to_path, $from_path, $from_url, $to_url )
    {
	global $VdG;

        $img_src = $this->replace_urls( $src, $from_url, "" );

        $abs_img_src = $this->removeParams( $this->replace_urls( $src, $from_url, $from_path ) );

        write_log( "abs_img_src = ".$abs_img_src );
        write_log( "to_path = ".$to_path );

        $paths = explode( "/", $img_src );

        write_log( $paths );

        $img_name = array_pop( $paths );

        write_log( "Image Name = ".$img_name );

		$old_path = $src;

		// $VdG->send_SSE( 0, "Comment", "Checking cache for ".$src );

		if ( !$VdG->is_cached_file( $old_path ) )
		{
        // remove the img name from the array

			$images_path = $this->build_protected_directory_tree( $to_path, array( "images", md5( implode ( "/", $paths) ) ) );

            write_log ( "from ". $abs_img_src );
            write_log ( "to ". trailingslashit( $images_path ).$img_name );

			$this->copy( $abs_img_src, trailingslashit( $images_path ).$img_name );


            $final_src = trailingslashit( $to_url.implode('/', array( "", "images", md5( implode ( "/", $paths) ) ))).$img_name;

			// Cache the new file
			$VdG->cache_file( $old_path, $final_src );

		}
		else
		{
			// $VdG->send_SSE( 0, "Comment", "Using cache for ".$src );

			// echo "<pre>Using Cache</pre>";
			$final_src = $VdG->cached_file( $old_path );
		}

        return $final_src;

    }

	function removeParams( $url ) {
		$url = strtok( $url, "?;" ) ;
		return $url;
	}

    // function process_html( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    // {

    //     var_dump( $id );
    //     var_dump( $from_url );
    //     var_dump( $to_url );
    //     var_dump( $from_path );
    //     var_dump( $to_path );

    //     return $html;
    // }


    function remap_internal_anchor_tags( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;

        $VdG->send_SSE( 0, "Comment", "remap internal anchor tags" );

        $VdG->send_SSE( 0, "Comment", $html );
        
        //ensure we have an object
        $html = str_get_html( $html );

        $VdG->send_SSE( 0, "Comment", "got html" );

        $VdG->send_SSE( 0, "Comment", $html );
        
        // remap internal anchor tags
        foreach($html->find('a') as $idx => $element)
        {
            $VdG->send_SSE( 0, "Comment", "looping around anchor tags" );
            
            if ( isset( $element->href )  )
            {
                $VdG->send_SSE( 0, "Comment", "its a href [".$element->href."]" );

                if ( strpos( $element->href, $from_url ) !== false )
                {
                    // echo $element->href . '<br>';

                    $element->href = $this->replace_urls( $element->href, $from_url, $to_url );
                }
            }
        }

        $VdG->send_SSE( 0, "Comment", "remap internal anchor tags [end]" );
    
		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_internal_stylesheets( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;
        //ensure we have an object
        $html = str_get_html( $html );

        // remap internal link tags
        foreach($html->find('link') as $idx => $element)
        {
            if ( isset( $element->href ) && isset ( $element->rel ) )
            {
                // process any internal stylesheet
                if ( $element->rel == "stylesheet" )
                {
                    if ( strpos( $element->href, $from_url ) !== false )
                    {
                        $element->href = $this->remap_stylesheet( $element->href, $to_path, $from_path, $from_url, $to_url  );
                    }
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_internal_canonical_tags( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;

        //ensure we have an object
        $html = str_get_html( $html );

        foreach($html->find('link') as $idx => $element)
        {
            if ( isset( $element->href ) && isset ( $element->rel ) )
            {
                // Correct any internal canonical tag
                if ( $element->rel == "canonical" )
                {
                    if ( strpos( $element->href, $from_url ) !== false )
                    {

                        $element->href = $this->replace_urls( $element->href, $from_url, $to_url );

                    }
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_internal_icon_tags( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;
        //ensure we have an object
        $html = str_get_html( $html );

        foreach($html->find('link') as $idx => $element)
        {
            if ( isset( $element->href ) && isset ( $element->rel ) )
            {
                // Move any icon tags
                if ( $element->rel == "icon" || $element->rel == "apple-touch-icon-precomposed" )
                {
                    if ( strpos( $element->href, $from_url ) !== false )
                    {
                        $element->href = $this->move_image( $element->href, $to_path, $from_path, $from_url, $to_url );
                    }
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_seo_tags( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;
        //ensure we have an object
        $html = str_get_html( $html );

        // Get the SEO settings
        $VDG_SEO_noindex = get_option( "VDG_SEO_noindex" );
        $VDG_SEO_nofollow = get_option( "VDG_SEO_nofollow" );

        // Process the meta tags
        foreach($html->find('meta') as $idx => $element)
        {
            if ( isset( $element->name ) )
            {
                // Do robots
                if ( $element->name == "robots" )
                {
                    $meta_tag = array();

                    if ( $VDG_SEO_noindex == 'Y' )
                        $meta_tag[] = "noindex";
                    else
                        $meta_tag[] = "index";

                    if ( $VDG_SEO_nofollow == 'Y' )
                        $meta_tag[] = "nofollow";
                    else
                        $meta_tag[] = "follow";

                    // Modify Seo meta tag
                    $element->content = implode( ",", $meta_tag );
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function process_ms_webapp_icon( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;
        //ensure we have an object
        $html = str_get_html( $html );

        // Process the meta tags
        foreach($html->find('meta') as $idx => $element)
        {
            if ( isset( $element->name ) )
            {
                // Handle MS webapp icon
                if ( $element->name == "msapplication-TileImage" )
                {
                    if ( strpos( $element->content, $from_url ) !== false )
                    {
                        $element->content = $this->move_image( $element->content, $to_path, $from_path, $from_url, $to_url  );
                    }
                }

            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_internal_scripts( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;
        //ensure we have an object
        $html = str_get_html( $html );

        // remap any internal scripts
        foreach($html->find('script') as $idx => $element)
        {
            if ( isset( $element->src ) )
            {
                if ( strpos( $element->src, $from_url ) !== false )
                {
                    $element->src = $this->remap_script( $element->src, $to_path, $from_path, $from_url, $to_url  );
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function remap_internal_images( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;

        //ensure we have an object
        $html = str_get_html( $html );

        $uploads_dir = wp_upload_dir( );

        $content_subfolder = str_replace( $from_url, "", content_url() );

        $content_dir =  $from_path. $content_subfolder;

        // remap any internal images
        foreach($html->find('img') as $idx => $element)
        {
            if ( isset( $element->src ) )
            {
                if ( strpos( $element->src, $from_url ) !== false )
                {
                    $element->src = $this->move_image( $element->src, $to_path, $from_path, $from_url, $to_url  );
                }

                // Deal with any html5 srcset entries
                if ( isset( $element->srcset ) )
                {
                    $srcset = array();

                    $srcsets = explode(",", trim( $element->srcset) );

                    foreach( $srcsets as $s )
                    {
                        $srcset[] = explode( " ", trim( $s ) );
                    }

                    $new_srcset = "";
                    $comma="";

                    // Process the srcset
                    foreach( $srcset as $s )
                    {
                        // only proicess our own images
                        if ( strpos( $s[0], $from_url ) !== false )
                        {
                            $s[0] = $this->move_image( $s[0], $to_path, $from_path, $from_url, $to_url  );
                        }

                        $new_srcset .= $comma.$s[0]." ".$s[1];
                        $comma=",";
                    }

                    $element->srcset = $new_srcset;
                }
            }
        }

		$VdG->incStatus( $id, $post_type );

        return $html;
    }


    function process_conditional_comments( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;

        //ensure we have an object
        $html = str_get_html( $html );

        // Process any IE comments pointing internlly
        foreach( $html->find("comment") as $element )
        {
            $splits = $this->split_comment( $element );

            $comment = "";

            foreach ($splits as $split )
            {
                $fragment = str_get_html( $split );

                if ( $fragment->find( "script", 0 ) != null )
                {
                    foreach ( $fragment->find( "script" ) as $frag )
                    {
                        if ( isset( $frag->src ) )
                        {
                            if ( strpos( $frag->src, $from_url ) !== false )
                            {

                                $frag->src = $this->remap_script( $frag->src, $to_path, $from_path, $from_url, $to_url  );

                            }
                        }

                        $comment.= $frag;
                    }
                }
                else if ( $fragment->find( "link", 0 ) != null )
                {
                    foreach ( $fragment->find( "link" ) as $frag )
                    {
                        if ( isset( $frag->href ) && isset( $frag->rel ) )
                        {
                            if ( $frag->rel == "stylesheet" )
                            {

                                if ( strpos( $frag->href, $from_url ) !== false )
                                {

                                    $frag->href = $this->remap_stylesheet( $frag->href, $to_path, $from_path, $from_url, $to_url  );

                                }

                                $comment.= $frag;
                            }
                            else
                                $comment.= $split;
                        }
                        else
                            $comment.= $split;
                    }
                }
                else
                    $comment.= $split;

            }

            $element->innertext = $comment;
        }

		$VdG->incStatus( $id, $post_type );
        return $html;
    }

    function process_inline_assets( $html, $id, $post_type, $from_url, $to_url, $from_path, $to_path )
    {
	global $VdG;

        // Process any internal assets in inline styles
        preg_match_all( CSS_URLS_REGEX, $html, $matches, PREG_PATTERN_ORDER);

        $old = array();
        $new = array();

        foreach( $matches[3] as $match )
        {

            if ( substr( $match, 0, 1 ) == "." )
            {
                $parts = parse_url( $match );

                write_log( $parts );

                $mod_src_path = $this->modifyPath( $src, $parts['path'] );
                $mod_dest_path = $this->modifyPath( $dest, $parts['path'] );

                $paths = explode( "/", str_replace( $to_path, "", $mod_dest_path ) );

                $asset_name = array_pop( $paths );

                $asset_path = $this->build_protected_directory_tree( $to_path, $paths );

                $this->copy( $mod_src_path, $mod_dest_path );

                $old[] = $match;
                $new[] = trailingslashit( $to_url. implode( "/", $paths )).$asset_name;
            }
            else if ( strpos( $match, $from_url ) !== false )
            {
                $old[] = $match;
                $new[] = $this->move_image( $match, $to_path, $from_path, $from_url, $to_url  );
            }
        }

        $final_html = str_replace( $old, $new, $html );

		$VdG->incStatus( $id, $post_type );

        return $final_html;
    }

	function remap_internal_stylesheet_refs( $cssFileContent, $root_path, $to_path, $from_path, $from_url, $to_url  )
	{
	global $VdG;

        preg_match_all( CSS_URLS_REGEX, $cssFileContent, $matches, PREG_PATTERN_ORDER );

        $old = array();
        $new = array();

		$VdG->send_SSE( 0, "Comment", "Remap Internal Stylesheet Refs" );

        write_log( $matches );

        foreach( $matches[3] as $match )
        {


            write_log( $match );
            write_log( (strpos( $match, $from_url ) !== false ? "true" : "false" ) );
            write_log( (substr( $match, 0, 1 ) == "." ? "true" : "false" ) );
            write_log( ( strpos( $match, "http" ) === false  ? "true" : "false" ) );
            write_log( strpos( $match, "data" ) );

            if ( substr( $match, 0, 1 ) == "." )
            {
                // write_log( $match." starts with a dot" );

                $parts = parse_url( $match );

                write_log( "Internal stylesheet refs" );
                write_log( $parts );


				$ext = pathinfo( $parts['path'], PATHINFO_EXTENSION );

                // echo "<pre>".print_r( $parts, true ) . '</pre>';

				// echo $from_path."<br />\n";

                $mod_src_path = $this->modifyPath( $root_path, $parts['path'] );
                $mod_dest_path = $this->modifyPath( $to_path.apply_filters( "vandergraaf_asset_destination", "/assets/", $ext ), $parts['path'] );

				// echo $mod_src_path."<br />\n";
				// echo $mod_dest_path."<br />\n";

                $paths = explode( "/", str_replace( $to_path, "", $mod_dest_path ) );

                // array_unshift( $paths, 'css' );
                $asset_name = array_pop( $paths );

                $asset_path = $this->build_protected_directory_tree( $to_path, $paths );

				// echo "asset path=".$mod_dest_path."\n";

                // write_log( "msp = [".$mod_src_path."]");

                //$this->copy(  trailingslashit( implode( "/", $file_paths ) ) .$asset_name, trailingslashit( $base_path. implode( "/", $paths ) ).$asset_name );
                if( $ext == "css" )
					$this->copy_css( $mod_src_path, $mod_dest_path, $to_path, $from_path, $from_url, $to_url );
				else
					$this->copy( $mod_src_path, $mod_dest_path );

                $old[] = $match;
                $new[] = trailingslashit( $to_url. implode( "/", $paths )).$asset_name;
            }
            else if ( strpos( $match, $from_url ) !== false )
            {

                // write_log( $match." does not contain ".$from_url );

                $parts = parse_url( $match );

                write_log( "Internal stylesheet refs" );
                write_log( $parts );

				$ext = pathinfo( $parts['path'], PATHINFO_EXTENSION );



                $file_src = trim( str_replace(
                                $from_url,
                                "",
                                $match
                            ), "/");

                $abs_file_src = trim( str_replace(
                                $from_url,
                                $from_path,
                                $match
                            ), "/");

				// echo "<pre>from_url = ". $from_url . '</pre>';
                // echo "<pre>from_path = ". $from_path . '</pre>';

                // echo "<pre>file src = ". $file_src . '</pre>';
                // echo "<pre>abs_file src = ". $abs_file_src . '</pre>';

                $paths = explode( "/", $file_src );

                //array_unshift( $paths, 'images' );
                $file_name = array_pop( $paths );

                $asset_path = $this->build_protected_directory_tree( $to_path, array( apply_filters( "vandergraaf_asset_destination", "/assets/", $ext ), md5( implode ( "/", $paths) ) ) );

                // echo "<pre>img path = ". trailingslashit( $asset_path ).$file_name . '</pre>';

                if( $ext == "css" )
					$this->copy_css( "/".$abs_file_src, trailingslashit( $asset_path ).$file_name, $to_path, $from_path, $from_url, $to_url );
				else
	                $this->copy( "/".$abs_file_src, trailingslashit( $asset_path ).$file_name );

                $old[] = $match;
                $new[] = trailingslashit( $to_url. implode( "/", array( "", trim( apply_filters( "vandergraaf_asset_destination", "/assets/", $ext ), "/" ), md5( implode ( "/", $paths) ) ) )).$file_name;

            }
            else if ( strpos( $match, "data:" ) == 0 )
            {
                // ignore
            }
            else if ( strpos( $match, "http" ) === false )
            {

                // write_log( $match." does not contain http at start" );

                $parts = parse_url( $match );

                write_log( "Internal stylesheet refs" );
                write_log( $parts );

                // we only rewrite http* paths - ignoring all others

                $ext = pathinfo( $parts['path'], PATHINFO_EXTENSION );

                // write_log( $parts );

                $file_src = $parts['path'];

                $abs_file_src = trailingslashit( $root_path ).$parts['path'];

                if ( isset( $parts[ 'fragment' ] ) )
                    $fragment = $parts[ 'fragment' ];
                else
                    $fragment = "";

                // write_log( "rp = ".$root_path );          
                // write_log( "fu = ".$from_url );          
                // write_log( "fp = ".$from_path );          
                // write_log( "abs = ".$abs_file_src );          

                // echo "<pre>from_url = ". $from_url . '</pre>';
                // echo "<pre>from_path = ". $from_path . '</pre>';

                // echo "<pre>file src = ". $file_src . '</pre>';
                // echo "<pre>abs_file src = ". $abs_file_src . '</pre>';

                $paths = explode( "/", $file_src );

                //array_unshift( $paths, 'images' );
                $file_name = array_pop( $paths );

                // // write_log( $paths );

                $asset_path = $this->build_protected_directory_tree( $to_path, $paths );

                // echo "<pre>img path = ". trailingslashit( $asset_path ).$file_name . '</pre>';

                // // write_log( trailingslashit( $asset_path ).$file_name );

                if( $ext == "css" )
                    $this->copy_css( "/".$abs_file_src, trailingslashit( $asset_path ).$file_name, $to_path, $from_path, $from_url, $to_url );
                else
                    $this->copy( "/".$abs_file_src, trailingslashit( $asset_path ).$file_name );

                $old[] = $match;
                $new_name = trailingslashit( $asset_path ).$file_name;

                if ( trim( $fragment ) !== "" )
                    $new_name .= "#".$fragment;

                $new[] = $new_name;

            }

        }

        $cssFileContent =  str_replace( $old, $new, $cssFileContent );

		return $cssFileContent;

	}

	function asset_destinations ( $folder="/assets/", $ext = "" )
	{

		switch ( strtolower( $ext ) )
		{
			case 'css' :
				$folder = "/css/";
				break;

			case 'jpg' :
			case 'jpeg' :
			case 'png' :
			case 'svg' :
				$folder = "/images/";
				break;
		}

		return $folder;

	}


	// function remap_internal_stylesheet_imports ( $cssFileContent, $to_path, $from_path, $from_url, $to_url  )
	// {

    //     preg_match_all( CSS_IMPORTS_REGEX, $cssFileContent, $matches, PREG_PATTERN_ORDER );

    //     $old = array();
    //     $new = array();

    //     foreach( $matches[3] as $match )
    //     {

    //         if ( substr( $match, 0, 1 ) == "." )
    //         {
    //             $parts = parse_url( $match );
    //             // echo "<pre>".print_r( $parts, true ) . '</pre>';

    //             $mod_src_path = $this->modifyPath( $from_path, $parts['path'] );
    //             $mod_dest_path = $this->modifyPath( $to_path."/css/", $parts['path'] );

    //             $paths = explode( "/", str_replace( $to_path, "", $mod_dest_path ) );

    //             // array_unshift( $paths, 'css' );
    //             $asset_name = array_pop( $paths );

    //             $asset_path = $this->build_protected_directory_tree( $to_path, $paths );

	// 			echo "msp = ".$mod_src_path."\n";
    //             echo "mdp = ".$mod_dest_path."\n";
    //             $this->copy( $mod_src_path, $mod_dest_path );

    //             $old[] = $match;
    //             $new[] = trailingslashit( $to_url. implode( "/", $paths )).$asset_name;
    //         }
    //         else if ( strpos( $match, $from_url ) !== false )
    //         {

    //             $file_src = trim( str_replace(
    //                             $from_url,
    //                             "",
    //                             $match
    //                         ), "/");

    //             $abs_file_src = trim( str_replace(
    //                             $from_url,
    //                             $from_path,
    //                             $match
    //                         ), "/");

    //             $paths = explode( "/", $file_src );

    //             $file_name = array_pop( $paths );

    //             $images_path = $this->build_protected_directory_tree( $to_path, array( "images", md5( implode ( "/", $paths) ) ) );

    //             $this->copy( "/".$abs_file_src, trailingslashit( $images_path ).$file_name );

    //             $old[] = $match;
    //             $new[] = trailingslashit( $to_url. implode( "/", array( "", "images", md5( implode ( "/", $paths) ) ) )).$file_name;

    //         }

    //     }

    //     $cssFileContent =  str_replace( $old, $new, $cssFileContent );

	// 	return $cssFileContent;
    // }
}
$VdG_page_generator = new VDG_Page_Generator();

endif;
