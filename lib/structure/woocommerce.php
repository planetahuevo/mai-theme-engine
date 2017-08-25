<?php
/**
 * Mai Pro Engine.
 *
 * @author   Mike Hemberger
 *
 * @version  1.0.0
 */

/**
 * WooCommerce product gallery support.
 *
 * Remove these by adding any of the following in functions.php:
 *
 * remove_theme_support( 'wc-product-gallery-zoom' );
 * remove_theme_support( 'wc-product-gallery-lightbox' );
 * remove_theme_support( 'wc-product-gallery-slider' );
 */
add_theme_support( 'wc-product-gallery-zoom' );
add_theme_support( 'wc-product-gallery-lightbox' );
add_theme_support( 'wc-product-gallery-slider' );

// Remove taxonomy archive description since Mai has this functionality already
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

// Replace Woocommerce Default pagination with Genesis Framework Pagination
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

// Maybe remove woocommerce page title
add_filter( 'woocommerce_show_page_title', 'mai_woocommerce_show_page_title' );
function mai_woocommerce_show_page_title( $return ) {
	if ( mai_is_banner_area_enabled() ) {
		if ( is_shop() ) {
			return false;
		}
		if ( is_product() ) {
			return false;
		}
	}
	return false;
}

add_filter( 'mai_cpt_settings', 'mai_woocommerce_product_settings', 10, 2 );
function mai_woocommerce_product_settings( $args, $post_type ) {
	// Bail if CPT is not WooCommerce 'product'.
	if ( ! ( class_exists( 'WooCommerce') && ( 'product' === $post_type ) ) ) {
		return $args;
	}
	// Woo defaults.
	return array(
		'columns'                   => 3,
		'content_archive'           => 'unset',
		'content_archive_limit'     => 'unset',
		'more_link'                 => 'unset',
		'more_link_text'            => 'unset',
		'content_archive_thumbnail' => 1,
		'image_location'            => 'unset',
		'image_size'                => 'unset',
		'image_alignment'           => 'unset',
		'remove_meta'               => 'unset',
		'layout_product'            => 'md-content',         // Single
		'layout'                    => 'full-width-content', // Archive
		'posts_per_page'            => 12,
		'posts_nav'                 => 'prev-next',
	);
}

/**
 * Load WooCommerce templates in the plugin,
 * while still allowing the theme to override.
 *
 * @return  string  The template file location
 */
add_filter( 'wc_get_template', 'mai_wc_get_template', 10, 4 );
function mai_wc_get_template( $template, $template_name, $args, $template_path ) {

	if ( ! $template_path ) {
		$template_path = WC()->template_path();
	}

	// Look for the file in the theme - this is priority
	$_template = locate_template( array( $template_path . $template_name, $template_name ) );

	if ( $_template ) {
		// Use theme template
		$template = $_template;
	} else {
		// Use our plugin template
		$plugin_path = MAI_PRO_ENGINE_PLUGIN_DIR . 'templates/woocommerce/';
		if ( file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}
	}
	return $template;
}

/**
 * Load WooCommerce templates in the plugin,
 * while still allowing the theme to override.
 *
 * @return  string  The template file location
 */
add_filter( 'wc_get_template_part', 'mai_wc_get_template_part', 10, 3 );
function mai_wc_get_template_part( $template, $slug, $name ) {

	$template_path = WC()->template_path();
	$template_name = "{$slug}-{$name}.php";

	// Look within passed path within the theme - this is priority
	$_template = locate_template( array( $template_path . $template_name, $template_name ) );

	if ( $_template ) {
		// Use theme template
		$template = $_template;
	} else {
		// Use our plugin template
		$plugin_path = MAI_PRO_ENGINE_PLUGIN_DIR . 'templates/woocommerce/';
		if ( file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}
	}
	return $template;
}

/**
 * Remove metaboxes on Woo shop admin page.
 * Most settings are now in Customizer.
 *
 * @return void
 */
add_action( 'add_meta_boxes', 'mai_remove_woo_shop_meta_boxes', 99, 2 );
function mai_remove_woo_shop_meta_boxes( $post_type, $post ){

	// Bail if Woo isn't active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Bail if not a page.
	if ( 'page' !== $post_type ) {
		return;
	}

	// Bail if not the Woo shop page.
	if ( $post->ID !== get_option( 'woocommerce_shop_page_id' ) ) {
		return;
	}

	global $wp_meta_boxes;

	// Create an array of meta boxes exceptions, ones that should not be removed (remove if you don't want/need)
	$exceptions = array(
		'slugdiv',
		'submitdiv',
		'pageparentdiv',
		'authordiv',
		'postexcerpt',
	);

	// Start looping.
	foreach( $wp_meta_boxes as $page => $page_boxes ) {

		// Skip if none.
		if ( empty( $page_boxes ) ) {
			continue;
		}

		// Loop through each page.
		foreach( $page_boxes as $context => $box_context ) {

			// Skip if none.
			if ( empty( $box_context ) ) {
				continue;
			}

			// Loop through each context.
			foreach( $box_context as $box_type ) {

				// Skip if none.
				if ( empty( $box_type ) ) {
					continue;
				}

				// Loop through each type.
				foreach( $box_type as $id => $box ) {

					// Skip if keeping.
					if ( in_array( $id, $exceptions ) ) {
						continue;
					}

					// Remove.
					remove_meta_box( $id, $page, $context );

				}

			}

		}

	}

	// Add metabox shop notice.
	add_meta_box( 'mai_woo_shop_notice', __( 'Mai WooCommerce Shop', 'mai-pro-engine' ), 'mai_woo_shop_notice', 'page', 'normal' );

}

/**
 * Outputs the content of the meta box
 *
 * @link  https://www.slushman.com/how-to-link-to-the-customizer/
 */
function mai_woo_shop_notice( $post ) {
	$section_link = mai_get_customizer_post_type_settings_link( 'product' );
	printf( '<a class="button" href="%s">%s</a>', esc_url( $section_link ), __( 'Edit Mai Product Settings', 'mai-pro-engine' ) );
}
