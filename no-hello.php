<?php
/*
Plugin Name: No Hello!
Plugin URI: http://mondaybynoon.com/
Description: Get rid of every mention of 'hello' in titles on your site
Version: 1.0
Author: Jonathan Christopher
Author URI: http://mondaybynoon.com.com/

Copyright 2014 Jonathan Christopher

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Remove 'hello' from titles
 *
 * @param $title
 * @param $id
 *
 * @return mixed|string The modified title
 */
function no_hello_remove_hello( $title, $id ) {
	$prevented = get_post_meta( $id, '_no_hello_prevented', true );
	if( ! $prevented ) {
		$setting = esc_attr( get_option( 'no_hello_setting' ) );
		$title = str_ireplace( 'hello', $setting, $title );
		$title = ucwords( $title );
	}
    return $title;
}
add_filter( 'the_title', 'no_hello_remove_hello', 10, 2 );


/**
 * Enqueue our custom stylesheet
 *
 * @uses wp_enqueue_style() to enqueue our custom style sheet
 */
function no_hello_styles() {
	wp_enqueue_style( 'no-hello', plugins_url( 'assets/css/style.css' , __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'no_hello_styles' );


/**
 * Add our Settings menu entry
 *
 * @uses add_options_page() to add our custom Settings menu entry
 */
function no_hello_admin_menu() {
	add_options_page( 'No Hello', 'No Hello', 'manage_options', 'no-hello', 'no_hello_options_page' );
}
add_action( 'admin_menu', 'no_hello_admin_menu' );


/**
 * Output the markup for our Options page
 *
 * @uses settings_fields() to output our settings fields
 * @uses do_settings_sections() to output our settings sections
 * @uses submit_button() to output our form submit button
 */
function no_hello_options_page() { ?>
	<div class="wrap">
		<h2><?php _e( 'No Hello Options', 'no-hello' ); ?></h2>
		<form action="options.php" method="POST">
			<?php settings_fields( 'no-hello-settings-group' ); ?>
			<?php do_settings_sections( 'no-hello' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}


/**
 * Register our setting with WordPress' Settings API
 *
 * @uses register_setting() to register our plugin setting
 * @uses add_settings_section() to define our settings section
 * @uses add_settings_field() to add a field to our settings section
 */
function no_hello_register_settings() {

	// register our setting
	register_setting( 'no-hello-settings-group',                    // $option_group
		'no_hello_setting',                                         // $option_name
		'no_hello_settings_sanitize' );                             // $sanitize_callback

	// add our settings section
	add_settings_section( 'no-hello-setting-replacement-section',   // $id
		'Replacement Options',                                      // $title
		'no_hello_setting_replacement_section_callback',            // $callback
		'no-hello' );                                               // $page

	// add a field to our settings section
	add_settings_field( 'no-hello-replacement',                     // $id
		'Replacement Text',                                         // $title
		'no_hello_replacement_field_callback',                      // $callback
		'no-hello',                                                 // $page
		'no-hello-setting-replacement-section' );                   // $section
}
add_action( 'admin_init', 'no_hello_register_settings' );


/**
 * This is a callback to output content above our settings section field(s)
 *
 * @see no_hello_register_settings()
 */
function no_hello_setting_replacement_section_callback() {
	?>
	Use the following to customize your implementation of No Hello
	<?php
}


/**
 * This is a callback to output the markup for our setting field
 *
 * @see no_hello_register_settings()
 */
function no_hello_replacement_field_callback() {
	$setting = esc_attr( get_option( 'no_hello_setting' ) );
	if( false == $setting && ! empty( $setting ) ) { // why not empty()? because maybe the user wants nothing...
		// the setting was never set
		$setting = 'Hey there';
	}
	echo "<input type='text' name='no_hello_setting' value='$setting' />";  // input name must be the same as $option_name
}


/**
 * Sanitize our settings before they're saved to the database
 *
 * @param $input
 * @uses sanitize_text_field() to sanitize our text field
 *
 * @return mixed
 */
function no_hello_settings_sanitize( $input ) {
	if( ! is_string( $input ) ) {
		$input = (string) $input;
	}

	$input = sanitize_text_field( $input );

	return $input;
}


/**
 * Register our custom meta box on Post edit screens
 *
 * @uses add_meta_box() to register our meta box with WordPress
 */
function no_hello_meta_box() {
	add_meta_box( 'no-hello-enabled',       // $id
		__( 'No Hello', 'no-hello' ),                         // $title
		'no_hello_enabled_callback',        // $callback
		'post',                             // $screen
		'normal',                           // $context
		'default');                         // $priority
}
add_action( 'add_meta_boxes', 'no_hello_meta_box' );


/**
 * Output the HTML for our meta box
 *
 * @uses wp_nonce_field() to generate a nonce
 * @uses get_post_meta() to retrieve the existing value
 * @uses checked() to automatically output the checked HTML attribute when necessary
 * @see no_hello_meta_box()
 */
function no_hello_enabled_callback() {
	global $post;
	wp_nonce_field( 'no_hello', 'no_hello_nonce' );
	$prevented = get_post_meta( $post->ID, '_no_hello_prevented', true );
	?>
	<p>
		<input type="checkbox" value="1" name="_no_hello_prevented" id="_no_hello_prevented" <?php checked( $prevented ); ?> />
		<label for="_no_hello_prevented">Prevent No Hello from substituting text</label>
	</p>
<?php
}


/**
 * Save our meta box form field data when posts are saved
 *
 * @param $post_id
 * @param $post
 * @uses wp_verify_nonce() to verify the submitted nonce
 * @uses wp_is_post_revision() to check for post revisions being saved
 * @uses current_user_can() to ensure the current user can actually manage post meta
 * @uses update_post_meta() to save our meta data record
 * @uses delete_post_meta() to delete a legacy meta data record
 *
 * @return mixed
 */
function no_hello_save_post( $post_id, $post ) {
	// validate the request itself using the nonce we set up
	if( ! isset( $_POST['no_hello_nonce'] ) || ! wp_verify_nonce( $_POST['no_hello_nonce'], 'no_hello' ) ) {
		return $post->ID;
	}

	// bail out if running an autosave, ajax, cron, or revision
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post->ID;
	}

	if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return $post->ID;
	}

	if( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return $post->ID;
	}

	if( wp_is_post_revision( $post_id ) ) {
		return $post->ID;
	}

	// make sure the user is authorized
	if( ! current_user_can( 'edit_post', $post->ID ) ) {
		return $post->ID;
	}

	// this request is validated, proceed to save
	$prevented = isset( $_POST['_no_hello_prevented'] ) ? absint( $_POST['_no_hello_prevented'] ) : false;
	if( $prevented ) {
		// save the flag for this post
		update_post_meta( $post->ID, '_no_hello_prevented', true );
	} else {
		// clean up the database record
		delete_post_meta( $post->ID, '_no_hello_prevented' );
	}

	return $post->ID;
}

add_action( 'save_post', 'no_hello_save_post', 1, 2 );
