<?php

/**
 * Enqueue an admin script, for custom editor styles and other stuff
 *
 * @return void
 */
add_action( 'admin_enqueue_scripts', 'mai_admin_enqueue_scripts' );
function mai_admin_enqueue_scripts() {

	// Use minified files if script debug is not being used
	$suffix = mai_get_suffix();

	// Register for later
	wp_enqueue_style( 'mai-admin', MAI_PRO_ENGINE_PLUGIN_URL . "/assets/css/mai-admin{$suffix}.css", array(), MAI_PRO_ENGINE_VERSION );
	wp_enqueue_script( 'mai-admin', MAI_PRO_ENGINE_PLUGIN_URL . "/assets/js/mai-admin{$suffix}.js", array( 'jquery' ), MAI_PRO_ENGINE_VERSION, true );
}

/**
 * Add editor stylesheet.
 * Cannot use add_editor_style() via a plugin, so we need to use mce_css filter.
 *
 * @return  Comma-separated string of CSS urls
 */
add_filter( 'mce_css', 'mai_add_editor_style' );
function mai_add_editor_style( $mce_css ) {

	// Use minified files if script debug is not being used
	$suffix = mai_get_suffix();

	if ( ! empty( $mce_css ) ) {
		$mce_css .= ',';
	}
	$mce_css .= MAI_PRO_ENGINE_PLUGIN_URL . "assets/css/mai-editor{$suffix}.css";
	return $mce_css;
}

/**
 * Adds a new select bar to the WP editor
 * Insert 'styleselect' into the $buttons array
 * _2 places the new button on the second line
 *
 * @return  array
 */
add_filter( 'mce_buttons_2', 'mai_add_styleselect_dropdown' );
function mai_add_styleselect_dropdown( $buttons ) {
	array_unshift( $buttons, 'styleselect' );
	return $buttons;
}

/**
 * Add a button option to the editor.
 *
 * @param   array  $init_array
 *
 * @return  array
 */
add_filter( 'tiny_mce_before_init', 'mai_add_style_format_options_to_editor' );
function mai_add_style_format_options_to_editor( $init_array ) {
	// Define the style_formats array
	$style_formats = array(
		// Each array child is a format with it's own settings
		array(
			'title' 	=> 'Section Title',
			// 'selector'  => 'h2',
			'block' 	=> 'h2',
			'classes' 	=> 'heading',
		),
		array(
			'title'		=> 'Button',
			'selector'	=> 'a',
			'classes'	=> 'button',
		),
	);
	// Insert the array, JSON ENCODED, into 'style_formats'
	$init_array['style_formats'] = json_encode( $style_formats );
	return $init_array;
}

// Show the editor on the page set for is_home()
add_action( 'edit_form_after_title', 'mai_posts_page_edit_form' );
function mai_posts_page_edit_form() {
    global $post, $post_type, $post_ID;
    if ( $post_ID == get_option( 'page_for_posts' ) && empty( $post->post_content ) ) {
        add_post_type_support( $post_type, 'editor' );
    }
}

// Change login logo
add_action( 'login_head',  'mai_login_logo_css' );
function mai_login_logo_css() {

	$logo_id  = get_theme_mod( 'custom_logo' );

	// Bail if we don't have a custom logo
	if ( ! $logo_id ) {
		return;
	}

	echo '<style  type="text/css">
		.login h1 a {
			width: 100% !important;
			max-width: 100% !important;
			height: auto !important;
			background: none !important;
			text-indent: 0 !important;
			padding: 0 !important;
			margin: 0 !important;
		}
	</style>';

	// Add the filter that adds the inline logo
	add_action( 'login_header', 'mai_do_login_logo_filter' );

}

/**
 * Add login logo filters if we have a custom logo.
 *
 * @return  void
 */
function mai_do_login_logo_filter() {

	// Replace site title with the logo
	add_filter( 'bloginfo', 'mai_do_inline_login_logo', 10, 2 );

	// Hook in after the login form to remove the filter
	add_action( 'login_footer', 'mai_remove_login_logo_filter' );
}

/**
 * Remove the filter that adds login logo as the blog name.
 *
 * @return  void
 */
function mai_remove_login_logo_filter() {
	remove_filter( 'bloginfo', 'mai_do_inline_login_logo', 10, 2 );
}

/**
 * Replace site name with an inline logo.
 * This filter only runs if we have a custom logo, so no need to check if ! $logo_id again.
 *
 * @param   string  $output  The site name.
 * @param   string  $show    Which bloginfo data to filter.
 *
 * @return  string|HTML 	 The inline image HTML
 */
function mai_do_inline_login_logo( $output, $show ) {

	// Bail if not filtering the name
	if ( $show != 'name' ) {
		return $output;
	}

	// Get the logo
	$logo_id  = get_theme_mod( 'custom_logo' );

	return wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full' );
}

// Change login link
add_filter( 'login_headerurl', 'mai_login_link' );
function mai_login_link() {
	return get_site_url();
}
