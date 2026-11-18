<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Author_Box {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function register() {
		register_block_type(
			'ultimate-post/author-box',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}
	public function get_attributes() {
		return array(
			'blockId'         => '',
			'layout'          => 'layout1',

			/*
			============================
				Author Box Settings
			============================*/
			'imgShow'         => true,
			'writtenByShow'   => true,
			'authorBioShow'   => true,
			'metaShow'        => true,
			'allPostLinkShow' => true,
			'authorBoxAlign'  => 'center',

			/*
			============================
				Author Image Settings
			============================*/
			'imgSize'         => (object) array( 'lg' => '100' ),
			'imgRatio'        => '100',

			/*
			============================
				Written by Settings
			============================*/
			'writtenByText'   => 'Written by',

			/*
			============================
				Author Name Settings
			============================*/
			'authorNameTag'   => 'h4',

			/*
			============================
				Meta Setting/Style Settings
			============================*/
			'metaPosition'    => 'bottom',

			/*
			============================
				View all Post Button Settings
			============================*/
			'viewAllPostText' => 'View All Posts',

			/*
			============================
				Advanced Settings
			============================*/
			'advanceId'       => '',
			'advanceZindex'   => '',
			'hideExtraLarge'  => false,
			'hideDesktop'     => false,
			'hideTablet'      => false,
			'hideMobile'      => false,
		);
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$block_name = 'author_box';
		$author_bio = $wrapper_before = $wrapper_after = $content = '';

		$page_post_id = get_the_ID();

		if ( $page_post_id ) {

			$attr['className']     = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']         = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$attr['advanceId']     = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
			$attr['blockId']       = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
			$attr['authorNameTag'] = in_array( $attr['authorNameTag'], ultimate_post()->ultp_allowed_block_tags() ) ? $attr['authorNameTag'] : 'h4';
			$attr['layout']        = sanitize_html_class( $attr['layout'] );
			// Layout class of the card. The default can be changed through ultp_author_box_layout_class.
			$layout_class          = sanitize_html_class( apply_filters( 'ultp_author_box_layout_class', 'layout1', $attr ) );
			$attr['writtenByText'] = wp_kses( $attr['writtenByText'], ultimate_post()->ultp_allowed_html_tags() );

			$_post       = get_post( $page_post_id );
			$post_author = get_userdata( $_post->post_author );

			// Author Image
			$author_image  = '<div class="ultp-post-author-image-section">';
			// get_userdata() returns false when the post has no valid author (author 0 or a deleted user).
			$author_image .= wp_kses( get_avatar( $post_author ? $post_author->ID : null, $attr['imgRatio'] ), ultimate_post()->rendered_content_allowed_html() );
			$author_image .= '</div>';

			// The photo sits before the details by default. The two image slots let a replacement move it after them.
			$image_ctx       = array( 'image' => $author_image );
			$image_html      = $attr['imgShow'] ? ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_author_box_image_html', $author_image, $attr, $image_ctx ), $author_image ) : '';
			$image_last_html = $attr['imgShow'] ? ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_author_box_image_last_html', '', $attr, $image_ctx ), '' ) : '';

			// Author Meta
			$author_meta  = '<div class="ultp-post-author-meta">';
			$author_meta .= '<span class="ultp-total-post">' . esc_html( count_user_posts( $_post->post_author, $post_type = 'post' ) ) . ' Posts</span>';
			$author_meta .= '<span class="ultp-total-comment">' . esc_html( get_comments_number( $page_post_id ) ) . ' Comments</span>';
			$author_meta .= '</div>';

			// Author Description
			if ( $post_author && $post_author->description ) {
				$author_bio .= '<div class="ultp-post-author-bio">';
				$author_bio .= '<span class="ultp-post-author-bio-meta">' . wp_kses_post( $post_author->description ) . '</span>';
				$author_bio .= '</div>';
			}

			// Author Url
			if ( get_author_posts_url( $_post->post_author ) ) {
				$all_post_link  = '<div class="ultp-author-post-link">';
				$all_post_link .= '<a class="ultp-author-post-link-text" href="' . esc_url( get_author_posts_url( $_post->post_author ) ) . '">' . wp_kses( $attr['viewAllPostText'], ultimate_post()->ultp_allowed_html_tags() ) . '</a>';
				$all_post_link .= '</div>';
			}

			$wrapper_before                  .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
				$wrapper_before              .= '<div class="ultp-block-wrapper">';
					$content                 .= '<div class="ultp-author-box ultp-author-box-' . esc_attr( $layout_class ) . '-content">';
						$content             .= $image_html;
							$content         .= '<div class="ultp-post-author-details">';
								$content     .= '<div class="ultp-post-author-title">';
									$content .= $attr['writtenByShow'] ? '<span class="ultp-post-author-written-by">' . wp_kses( $attr['writtenByText'], ultimate_post()->ultp_allowed_html_tags() ) . '</span>' : '';
									$content .= '<' . tag_escape( $attr['authorNameTag'] ) . ' class="ultp-post-author-name"><a href="' . esc_url( get_author_posts_url( $_post->post_author ) ) . '">' . esc_html( $post_author ? $post_author->display_name : '' ) . '</a></' . tag_escape( $attr['authorNameTag'] ) . '>';
								$content     .= '</div>';

								$content .= ( $attr['metaShow'] && $attr['metaPosition'] == 'top' ? $author_meta : '' );

								$content .= ( $attr['authorBioShow'] && $author_bio ? $author_bio : '' );
								$content .= ( $attr['metaShow'] && $attr['metaPosition'] == 'bottom' ? $author_meta : '' );
								$content .= ( $attr['allPostLinkShow'] ? $all_post_link : '' );
							$content     .= '</div>';
							$content     .= $image_last_html;
						$content         .= '</div>';
				$wrapper_after           .= '</div>';
			$wrapper_after               .= '</div>';
		}

		return $wrapper_before . $content . $wrapper_after;
	}
}
