<?php
/**
 * Plugin Name:     Giphy Block
 * Description:     Example block written with ESNext standard and JSX support – build step required.
 * Version:         0.1.0
 * Author:          The WordPress Contributors
 * License:         GPL-2.0-or-later
 * Text Domain:     giphy-block
 *
 * @package         DonMhico\Giphy_Block
 */

namespace DonMhico\Giphy_Block;

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function block_init() {
	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new \Error(
			'You need to run `npm start` or `npm run build` for the "donmhico/giphy-block" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'donmhico-giphy-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);

	$editor_css = 'editor.css';
	wp_register_style(
		'donmhico-giphy-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'style.css';
	wp_register_style(
		'donmhico-giphy-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'donmhico/giphy-block', array(
		'editor_script' => 'donmhico-giphy-block-editor',
		'editor_style'  => 'donmhico-giphy-block-editor',
		'style'         => 'donmhico-giphy-block',
	) );
}
add_action( 'init', __NAMESPACE__ . '\block_init' );

define( 'DM_GIPHY_BLOCK_REST_NAMESPACE', 'dmgiphyblock/v1' );
define( 'DM_GIPHY_BLOCK_API_KEY', 'dm_giphy_block_api_key' );
/**
 * Register custom WP Rest Endpoints to fetch and save the Giphy API Key.
 */
function rest_endpoint() {
	register_rest_route(
		DM_GIPHY_BLOCK_REST_NAMESPACE,
		'api-key/',
		[
			'methods' => \WP_REST_Server::READABLE,
			'callback' => __NAMESPACE__ . '\rest_get_api_key',
			'permission_callback' => __NAMESPACE__ . '\rest_check_permission'
		]
	);

	register_rest_route(
		DM_GIPHY_BLOCK_REST_NAMESPACE,
		'api-key/',
		[
			'methods' => \WP_REST_Server::EDITABLE,
			'callback' => __NAMESPACE__ . '\rest_update_api_key',
			'permission_callback' => __NAMESPACE__ . '\rest_check_permission'
		]
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\rest_endpoint' );

function rest_get_api_key() {
	$response = new \WP_REST_Response( get_option( DM_GIPHY_BLOCK_API_KEY, '' ) );
	$response->set_status( 200 );

	return $response;
}

function rest_update_api_key( $request ) {
	$save_api_key = update_option( DM_GIPHY_BLOCK_API_KEY, $request->get_body() );

	$response = new \WP_REST_Response( $save_api_key );
	$response->set_status( 201 );

	return $response;
}

/**
 * Make the custom REST endpoint private and accessible on to users that can `edit_posts`.
 *
 * @return bool
 */
function rest_check_permission() {
	return current_user_can( 'edit_posts' );
}
