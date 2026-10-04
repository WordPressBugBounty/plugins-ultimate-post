<?php

namespace ULTP;


defined( 'ABSPATH' ) || exit;

class Saved_Templates {
	public function __construct() {
		$this->templates_post_type_callback();
		add_filter( 'manage_ultp_templates_posts_columns', array( $this, 'templates_table_head' ) );
		add_action( 'manage_ultp_templates_posts_custom_column', array( $this, 'templates_table_content' ), 10, 2 );
	}

	public function templates_table_head( $defaults ) {
		$type_array = array( 'type' => __( 'Shortcode', 'ultimate-post' ) );
		array_splice( $defaults, 2, 0, $type_array );
		return $defaults;
	}

	// Column Content
	public function templates_table_content( $column_name, $post_id ) {
		echo '<code class="ultp-shortcode-copy">[postx_template id="' . esc_attr( $post_id ) . '"]</code>';
	}


	// Templates Post Type Register
	public function templates_post_type_callback() {
		$labels = array(
			'name'               => _x( 'Saved Templates', 'Templates', 'ultimate-post' ),
			'singular_name'      => _x( 'Saved Template', 'Templates', 'ultimate-post' ),
			'menu_name'          => __( 'Saved Templates', 'ultimate-post' ),
			'parent_item_colon'  => __( 'Parent Template', 'ultimate-post' ),
			'all_items'          => __( 'Saved Templates', 'ultimate-post' ),
			'view_item'          => __( 'View Template', 'ultimate-post' ),
			'add_new_item'       => __( 'Add New Template', 'ultimate-post' ),
			'add_new'            => __( 'Add New Template', 'ultimate-post' ),
			'edit_item'          => __( 'Edit Template', 'ultimate-post' ),
			'update_item'        => __( 'Update Template', 'ultimate-post' ),
			'search_items'       => __( 'Search Template', 'ultimate-post' ),
			'not_found'          => __( 'No Template Found', 'ultimate-post' ),
			'not_found_in_trash' => __( 'Not Template found in Trash', 'ultimate-post' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'ultp_templates', $args );
	}
}
