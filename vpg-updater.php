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

define( 'VDG_PG_RELEASE_URL', 'https://s3.eu-west-2.amazonaws.com/vandergraaf-page-generator-releases' );


class VanderGraaf_Page_Generator_Updater
{

    function __construct( )
    {

        // Self updating
        add_filter ('pre_set_site_transient_update_plugins', array ( $this, 'check_for_new_release' ) ) ;

        // Define the alternative response for information checking
        add_filter('plugins_api', array( $this, 'check_release_info'), 10, 3);

    }

    function check_for_new_release ($transient)
    {
        $data = file_get_contents( VDG_PG_RELEASE_URL."/latest-release.number" );
        $update = json_decode( $data );

        if ( version_compare( $transient->checked['vandergraaf-page-generator/vandergraaf-page-generator.php'], $update->version, "<" ) )
        {

            $obj = new stdClass();
            $obj->slug = 'vandergraaf-page-generator.php';
            $obj->new_version = $update->version;
            $obj->url = VDG_PG_RELEASE_URL;
            $obj->package = VDG_PG_RELEASE_URL.'/vandergraaf-pg-'.$update->version.'.zip';
            $transient->response['vandergraaf-page-generator/vandergraaf-page-generator.php'] = $obj;

            //echo "<pre>".print_r( $transient, true )."</pre>";

        }

        return $transient;
    }

    public function check_release_info($false, $action, $arg)
    {

      if ($arg->slug === 'vandergraaf-page-generator.php')
        {

            $data = file_get_contents( VDG_PG_RELEASE_URL."/latest-release.number" );
            $update = json_decode( $data );

            $obj = new stdClass();
            $obj->slug = 'vandergraaf-page-generator.php';
            $obj->plugin_name = 'vandergraaf-page-generator.php';
            $obj->new_version =  $update->version;
            $obj->requires = $update->requires;
            $obj->tested = $update->tested;
            $obj->last_updated = $update->last_updated;
            $obj->sections = array(
                'description' => $update->description,
            //     'another_section' => 'This is another section',
                 'changelog' => implode( "<br />", $update->changelog )
            );
            $obj->download_link = VDG_PG_RELEASE_URL.'/vandergraaf-pg-'.$update->version.'.zip';

            return $obj;
        }

        return false;
    }
}
