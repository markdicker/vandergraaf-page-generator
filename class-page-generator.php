<?php

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


require_once( "simple_html_dom.php" );


if ( ! class_exists( 'Van_der_Graaf_Page_Generator' ) ) :

class Van_der_Graaf_Page_Generator
{

    /*
     *
     * Perform any initialisation now
     *
     */

    function __construct()
    {
        //  Add our processing filters here

    }


    function build_protected_directory_tree( $root_path, $paths, $htaccess=array() )
    {
        // $htaccess is now deprecated

        global $VdG_page_generator;

        // write_log("Build Protected Directory Tree");
        // write_log( $root_path );

        return $VdG_page_generator->Storage->createFolder( $root_path, $paths );

    }


    function file_get_html($url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=false, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
    {
        // We DO force the tags to be terminated.
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
        // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
        $contents = $this->get_html( $url );
        // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
        // $contents = retrieve_url_contents($url);

        if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
        {
            return false;
        }
        // The second parameter can force the selectors to all be lowercase.
        $dom->load($contents, $lowercase, $stripRN);
        return $dom;
    }


    function get_html($url)
    {
        //$authorised_header_name = "X-".get_option( "VDG_authorised_header_name" );
        $authorised_header_name = str_replace( "_", "-", get_option( "VDG_authorised_header_name" ) );
        $authorised_header_id = get_option( "VDG_authorised_header_id" );

        $ch = curl_init();
        $timeout = 500;
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array (
            strtoupper( $authorised_header_name ) .': '.$authorised_header_id,
            // strtoupper( $authorised_header_name ) .': X',
            // "X-ORIGINATION_ID: ".strtoupper( $authorised_header_name ) .'-'.$authorised_header_id,
            // "X-ORIGINATION-ID: ".strtoupper( $authorised_header_name ) .'-'.$authorised_header_id
            )
        );

        $data = curl_exec($ch);

        $err = curl_error($ch);
        
        curl_close($ch);

        if ( $err )
            return $err;

        return $data;
    }




    function save_file( $filename, $payload = "", $perms = 0644 )
    {

        global $VdG_page_generator;

        return $VdG_page_generator->Storage->writeFile( $filename, $payload, $perms );

    }

    function split_comment( $src_comment )
    {
//        // echo "<pre>". str_replace( array("\n", "\r", "\r\n", "\n\r" ), array( "", "", "", "" ), $comment) . "</pre>";

        $i = 0;
        $parts = array();
        $in_token = false;
        $token = "";
        $token_start = 0;

        $comment = trim( $src_comment );

        // // echo "<pre>[".$comment."]</pre>";

        while ( $i < strlen( $comment ) )
        {
            $chr = substr( $comment, $i, 1 );

            // // echo "<pre>".$chr."</pre>";

            if ( ord( $chr ) > 32 && ord( $chr ) != 127 )
            {
                if ( $chr == '<' )
                {
                    $in_token = true;
                    $token_start = $i;
                }

                if ( $chr == '>' )
                {
                    if ( $token_start != $i )
                    {
                        $in_token = false;
                        $parts[] = substr( $comment, $token_start, ($i - $token_start + 1  ) );
                    }
                }
            }

            $i++;
        }

        return $parts;
    }


    function from_url( )
    {
        return site_url();
    }


    function from_path()
    {
        $cwd = realpath( dirname( __FILE__ ) );

        write_log( "cwd => ".$cwd ); 


        $parts = explode( DIRECTORY_SEPARATOR, $cwd );

        // we are 3 folders from the root.  ROOT/WP_CONTENT/PLUGINS/THIS_PLUGIN

        array_pop( $parts );	// ROOT/WP_CONTENT/PLUGINS
        array_pop( $parts );	// ROOT/WP_CONTENT
        array_pop( $parts );	// ROOT

        $path = implode( "/", $parts );

        return $path;
    }


    function to_url()
    {
        return get_option( "VDG_STATIC_URL" );
    }


    function to_path( )
    {
        return get_option( "VDG_STATIC_ROOT" );
    }


    function modifyPath( $start = "/", $mods = "" )
    {
        $final = "";

        $paths1 = explode ( "/", $start );
        $file1 = array_pop( $paths1 );

        $paths2 = explode ( "/", $mods );
        $file2 = array_pop( $paths2 );

        $first = array_shift ( $paths2 ) ;

        switch ( $first )
        {
            case "." :
                break;

            case ".." :
                array_pop( $paths1 );

                if ( empty( $paths1 ) )
                    array_unshift( $paths1, "" );

                break;

            default:
                $paths1 = array ( $first ) ;
        }

		// echo "Paths1 = \n".print_r( $paths1, true )."<br >\n";

        while ( !empty( $paths2 ) )
        {
            $seg = array_shift ( $paths2 ) ;

            switch ( $seg )
            {
                case "." :
                    break;

                case ".." :
                    array_pop( $paths1 );

                    if ( empty( $paths1 ) )
                        array_unshift( $paths1, "" );

                    break;

                default:
                    $paths1[] = $seg ;
            }
        }

        if ( $file2 != "" )
            array_push( $paths1, $file2 );
        else
            if ( $file1 != "" )
                array_push( $paths1, $file1 );

        return implode( "/",  $paths1 );
    }

    function replace_urls( $href, $from_url, $to_url )
    {

        // // write_log( $from_url." => ".$to_url );

        return rtrim( str_replace(
                            $from_url,
                            $to_url,
                            $href
                        ), "/");

    }


    function copy_css( $src, $dest, $to_path, $from_path, $from_url, $to_url )
    {
        global $VdG_page_generator;

        $file_path = dirname( $src );

		// echo "src = ".$src."\n";
		// echo "dest = ".$dest."\n";

        $file_paths = explode( "/", $file_path );

        //array_pop( $file_paths );

        $file_path = trailingslashit( implode( "/", $file_paths ) );

        write_log( $src );

        $cssFileContent = file_get_contents( $src );

        // write_log( "src = ".$src );

		$cssFileContent = apply_filters( "vandergraaf_page_generator_css_filter", $cssFileContent, $file_path, $to_path, $from_path, $from_url, $to_url );


        // file_put_contents( $dest, $cssFileContent );

        $VdG_page_generator->Storage->writeFile( $dest, $cssFileContent, 0644 );

	}


    function copy( $src, $dest )
    {
        global $VdG_page_generator;

        write_log( "copy ".$src." to ".$dest );

        $VdG_page_generator->Storage->copyFile ( $src, $dest );
    }

}

endif;
