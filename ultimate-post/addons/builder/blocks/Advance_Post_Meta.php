<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Advance_Post_Meta {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'         => '',
			/*
			============================
				Advanced Post Meta Settings
			============================*/
			// META ITEM ENABLE
			'authorShow'      => true,
			'dateShow'        => true,
			'cmtCountShow'    => true,
			'viewCountShow'   => false,
			'readTimeShow'    => false,
			'catShow'         => false,
			'tagShow'         => false,
			'metaSeparator'   => 'dot',
			'metaItemSort'    => array( 'author', 'date', 'cmtCount', 'viewCount', 'readTime', 'cat', 'tag' ),

			/*
			============================
				Post Author Style
			============================*/
			// Avatar
			'authImgShow'     => false,
			// Author Label
			'authLabelShow'   => true,
			'authLabel'       => 'Author',
			// Auth Icon
			'authIconShow'    => false,
			'authIconStyle'   => 'author1',
			'authAlign'       => false,

			/*
			============================
				Post Publish Time Style
			============================*/
			'dateFormat'      => 'updated',
			'metaDateFormat'  => 'M j, Y',
			'dateColor'       => '#a4a4a4',
			// Prefix
			'enablePrefix'    => true,
			'datePubText'     => 'Publish Update',
			'dateText'        => 'Latest Update',
			// Icon
			'DateIconShow'    => false,
			'dateIconStyle'   => 'date1',
			'dateAlign'       => false,

			/*
			============================
				Comment Style
			============================*/
			// Prefix
			'cmtLabelShow'    => true,
			'cmtLabel'        => 'Comment',
			// Icon
			'cmtIconShow'     => false,
			'cmntIconStyle'   => 'commentCount1',
			'cmntAlign'       => false,
			// Prefix Style
			'viewLabelShow'   => true,
			'viewLabel'       => 'View',
			// Icon
			'viewIconShow'    => false,
			'viewIconStyle'   => 'viewCount1',
			'viewAlign'       => false,

			/*
			============================
				Reading Time Style
			============================*/
			// Prefix
			'readTimePrefix'  => true,
			'readTimeText'    => 'Minute Read',
			'readPrefixAlign' => 'after',
			// Icon
			'readTimeIcon'    => false,
			'readIconStyle'   => 'readingTime2',
			'readAlign'       => false,

			/*
			============================
				Categories Style
			============================*/
			'catLabelShow'    => true,
			'catLabel'        => 'Category',
			'catIconShow'     => false,
			'catIconStyle'    => 'cat2',
			'catAlign'        => false,

			/*
			============================
				Tag Style
			============================*/
			'tagColor'        => '#545454',
			'tagHovColor'     => '#ddd',
			'tagTypo'         => (object) array(
				'openTypography' => 1,
				'decoration'     => 'none',
				'size'           => (object) array(
					'lg'   => 15,
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => 20,
					'unit' => 'px',
				),
			),
			'tagSpace'        => (object) array(
				'lg'   => '7',
				'unit' => 'px',
			),

			'tagLabelShow'    => true,
			'tagLabel'        => 'Tag - ',
			'tagIconShow'     => false,
			'tagIconStyle'    => 'tag2',
			'tagAlign'        => false,

			/*
			============================
				Advance Setting
			============================*/
			'advanceId'       => '',
			'advanceZindex'   => '',
			'hideExtraLarge'  => false,
			'hideDesktop'     => false,
			'hideTablet'      => false,
			'hideMobile'      => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/advance-post-meta',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}


	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$block_name     = 'post_meta';
		$wrapper_before = $wrapper_after = $wrapper_content = $authContent = $updateLabel = $dateLabel = '';

		$post_id      = get_the_ID();
		$contentAlign = ( $attr['catAlign'] || $attr['tagAlign'] || $attr['cmntAlign'] || $attr['viewAlign'] || $attr['readAlign'] || $attr['authAlign'] || $attr['dateAlign'] ) ? 'ultp-contentMeta-align' : 'ultp-contentMeta';

		$attr['className']     = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']         = isset( $attr['align'] ) && $attr['align'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['advanceId']     = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']       = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['metaSeparator'] = sanitize_html_class( $attr['metaSeparator'] );
		$allowed_html_tags     = ultimate_post()->ultp_allowed_html_tags();
		$attr['authLabel']     = wp_kses( $attr['authLabel'], $allowed_html_tags );
		$attr['dateText']      = wp_kses( $attr['dateText'], $allowed_html_tags );
		$attr['datePubText']   = wp_kses( $attr['datePubText'], $allowed_html_tags );
		$attr['cmtLabel']      = wp_kses( $attr['cmtLabel'], $allowed_html_tags );
		$attr['viewLabel']     = wp_kses( $attr['viewLabel'], $allowed_html_tags );
		$attr['readTimeText']  = wp_kses( $attr['readTimeText'], $allowed_html_tags );
		$attr['catLabel']      = wp_kses( $attr['catLabel'], $allowed_html_tags );
		$attr['tagLabel']      = wp_kses( $attr['tagLabel'], $allowed_html_tags );

		// Icons of the meta items; an add-on may supply them.
		$icons = array();
		foreach ( array( 'author', 'comment', 'view', 'readTime', 'cat', 'tag' ) as $item ) {
			$icons[ $item ] = $this->meta_icon( $item, $attr );
		}

		// Author Content
		if ( $attr['authorShow'] ) {
			$author_id        = get_post_field( 'post_author', $post_id );
			$authContent     .= '<span class="ultp-post-auth ultp-meta-separator">';
				$authContent .= '<span class="ultp-auth-heading">';
			if ( false !== $icons['author'] ) {
				$authContent .= wp_kses( $icons['author'], ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $attr['authImgShow'] ) {
				$authContent .= wp_kses( get_avatar( $author_id, 32 ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $attr['authLabelShow'] ) {
				$authContent .= '<span class="ultp-auth-label">' . wp_kses( $attr['authLabel'], $allowed_html_tags ) . '</span>';
			}
				$authContent     .= ' </span>';
				$authContent     .= '<a  href="' . esc_url( get_author_posts_url( $author_id ) ) . '" class="ultp-auth-name">';
					$authContent .= esc_html( get_the_author_meta( 'display_name', $author_id ) );
				$authContent     .= '</a>';
			$authContent         .= '</span>';
		}

		// Date Content
		if ( $attr['enablePrefix'] ) {
			$dateLabel .= '<span class="ultp-date-prefix">' . wp_kses( $attr['dateText'], $allowed_html_tags ) . '</span>';
		}
		if ( $attr['enablePrefix'] ) {
			$updateLabel .= '<span class="ultp-date-prefix">' . wp_kses( $attr['datePubText'], $allowed_html_tags ) . '</span>';
		}
		$dateContent = '';
		if ( $attr['dateShow'] ) {
			$dateContent .= '<span class="ultp-date-meta ultp-meta-separator">';
			if ( $attr['DateIconShow'] ) {
				$dateContent .= '<span class="ultp-date-icon">' . wp_kses( ultimate_post()->get_svg_icon( '' . $attr['dateIconStyle'] . '' ), ultimate_post()->rendered_content_allowed_html() ) . '</span>';
			}
			if ( $attr['dateFormat'] == 'updated' ) {
				// Filter `ultp_builder_advance_meta_date_format`: the date text of a format other than 'M j, Y' (see Functions::get_builder_date()).
				$dateContent .= '<span class="ultp-post-update">' . $dateLabel . '<span class="ultp-post-date__val">' . esc_html( ultimate_post()->get_builder_date( 'ultp_builder_advance_meta_date_format', $attr['metaDateFormat'], true, $post_id, $attr ) ) . '</span></span>';
			}
			if ( $attr['dateFormat'] == 'publish' ) {
				$dateContent .= '<span class="ultp-post-date">' . $updateLabel . ' <span class="ultp-post-date__val">' . esc_html( ultimate_post()->get_builder_date( 'ultp_builder_advance_meta_date_format', $attr['metaDateFormat'], false, $post_id, $attr ) ) . '</span></span>';
			}
			$dateContent .= '</span>';
		}

		// Main Content
		$wrapper_before              .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '' . ( $attr['align'] ? ' align' . esc_attr( $attr['align'] ) : '' ) . '">';
			$wrapper_before          .= '<div class="ultp-block-wrapper">';
				$wrapper_content     .= '<div class="ultp-advance-post-meta ' . esc_attr( $contentAlign ) . ' ultp-post-meta-' . esc_attr( $attr['metaSeparator'] ) . '">';
					$wrapper_content .= '<div>';
		foreach ( $attr['metaItemSort'] as $val ) {
			if ( $val == 'author' && $attr['authorShow'] && $attr['authAlign'] == false ) {
				$wrapper_content .= $authContent;
			}
			if ( $val == 'date' && $attr['dateShow'] && $attr['dateAlign'] == false ) {
				$wrapper_content .= $dateContent;
			}
			if ( $val == 'cmtCount' && $attr['cmtCountShow'] && $attr['cmntAlign'] == false ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'comment', get_post_field( 'comment_count', '' ), $attr['cmtLabelShow'], $attr['cmtLabel'], false !== $icons['comment'], (string) $icons['comment'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $val == 'viewCount' && $attr['viewCountShow'] && $attr['viewAlign'] == false ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'view', get_post_meta( $post_id, '_ultp_post_views_count', true ), $attr['viewLabelShow'], $attr['viewLabel'], false !== $icons['view'], (string) $icons['view'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $val == 'readTime' && $attr['readTimeShow'] && $attr['readAlign'] == false ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'readTime', 12, $attr['readTimePrefix'], $attr['readTimeText'], false !== $icons['readTime'], (string) $icons['readTime'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $val == 'cat' && $attr['catShow'] && $attr['catAlign'] == false ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'cat', get_the_category(), $attr['catLabelShow'], $attr['catLabel'], false !== $icons['cat'], (string) $icons['cat'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $val == 'tag' && $attr['tagShow'] && $attr['tagAlign'] == false ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'tag', get_the_tags(), $attr['tagLabelShow'], $attr['tagLabel'], false !== $icons['tag'], (string) $icons['tag'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
		}
					$wrapper_content .= '</div>';
					$wrapper_content .= '<div>';
		foreach ( $attr['metaItemSort'] as $content ) {
			if ( $content == 'author' && $attr['authorShow'] && $attr['authAlign'] ) {
				$wrapper_content .= $authContent;
			}
			if ( $content == 'date' && $attr['dateShow'] && $attr['dateAlign'] ) {
				$wrapper_content .= $dateContent;
			}
			if ( $content == 'cmtCount' && $attr['cmtCountShow'] && $attr['cmntAlign'] ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'comment', get_post_field( 'comment_count', '' ), $attr['cmtLabelShow'], $attr['cmtLabel'], false !== $icons['comment'], (string) $icons['comment'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $content == 'viewCount' && $attr['viewCountShow'] && $attr['viewAlign'] ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'view', get_post_meta( get_the_ID(), '_ultp_post_views_count', true ), $attr['viewLabelShow'], $attr['viewLabel'], false !== $icons['view'], (string) $icons['view'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $content == 'readTime' && $attr['readTimeShow'] && $attr['readAlign'] ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'readTime', 12, $attr['readTimePrefix'], $attr['readTimeText'], false !== $icons['readTime'], (string) $icons['readTime'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $content == 'cat' && $attr['catShow'] && $attr['catAlign'] ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'cat', get_the_category(), $attr['catLabelShow'], $attr['catLabel'], false !== $icons['cat'], (string) $icons['cat'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
			if ( $content == 'tag' && $attr['tagShow'] && $attr['tagAlign'] ) {
				$wrapper_content .= wp_kses( $this->renderPostCount( 'tag', get_the_tags(), $attr['tagLabelShow'], $attr['tagLabel'], false !== $icons['tag'], (string) $icons['tag'], $post_id ), ultimate_post()->rendered_content_allowed_html() );
			}
		}
					$wrapper_content .= '</div>';
				$wrapper_content     .= '</div>';
			$wrapper_after           .= '</div>';
		$wrapper_after               .= '</div>';

		return $wrapper_before . $wrapper_content . $wrapper_after;
	}

	/**
	 * Icon of one meta item: the icon the block's "Enable Icon" and "Select Icon" options choose, which an
	 * add-on may replace.
	 *
	 * Filter `ultp_advance_post_meta_icon`: `$icon` (the icon markup, or false for none), `$attr`,
	 * `$context` with `item` (`author`, `comment`, `view`, `readTime`, `cat` or `tag`). An add-on that
	 * returns a string gets its markup printed instead; one that returns false gets no icon.
	 *
	 * @param string $item Meta item.
	 * @param array  $attr Block attributes.
	 * @return string|false Icon markup, or false when the item has no icon.
	 */
	private function meta_icon( $item, $attr ) {
		$icon = $this->default_icon( $item, $attr );
		$icon = apply_filters( 'ultp_advance_post_meta_icon', $icon, $attr, array( 'item' => $item ) );
		return is_string( $icon ) ? wp_kses( $icon, ultimate_post()->rendered_content_allowed_html() ) : false;
	}

	/**
	 * Icon of one meta item from the block's own options, before any add-on filter.
	 *
	 * @param string $item Meta item.
	 * @param array  $attr Block attributes.
	 * @return string|false Icon markup from the shipped icon set, or false when "Enable Icon" is off.
	 */
	private function default_icon( $item, $attr ) {
		// Meta item => array( "show" attribute, "icon style" attribute ).
		$options = array(
			'author'   => array( 'authIconShow', 'authIconStyle' ),
			'comment'  => array( 'cmtIconShow', 'cmntIconStyle' ),
			'view'     => array( 'viewIconShow', 'viewIconStyle' ),
			'readTime' => array( 'readTimeIcon', 'readIconStyle' ),
			'cat'      => array( 'catIconShow', 'catIconStyle' ),
			'tag'      => array( 'tagIconShow', 'tagIconStyle' ),
		);
		if ( ! isset( $options[ $item ] ) || empty( $attr[ $options[ $item ][0] ] ) ) {
			return false;
		}
		$style = isset( $attr[ $options[ $item ][1] ] ) ? $attr[ $options[ $item ][1] ] : '';
		return (string) ultimate_post()->get_svg_icon( '' . $style . '' );
	}

	public function renderPostCount( $title, $data, $labelEnable, $labelText, $iconEnable, $icon, $post_id = null ) {
		$content  = '';
		$content .= '<span class="ultp-' . esc_attr( $title ) . '-wrap ultp-meta-separator">';
		if ( ( $title == 'tag' || $title == 'cat' ) && $iconEnable ) {
			$content .= '<span>' . wp_kses( $icon, ultimate_post()->rendered_content_allowed_html() ) . '</span>';
		}
		if ( ( $title == 'tag' || $title == 'cat' ) && ( $labelEnable ) ) {
			$content .= '<span class="ultp-' . esc_attr( $title ) . '-label">' . wp_kses( $labelText, ultimate_post()->ultp_allowed_html_tags() ) . '</span>';
		}
		if ( $title == 'tag' || $title == 'cat' ) {
			$content .= '<span class="ultp-post-' . esc_attr( $title ) . '">';
			if ( is_array( $data ) && count( $data ) > 0 ) {
				if ( is_array( $data ) ) {
					foreach ( $data as $dt ) {
						$content .= '<a href="' . esc_url( ultimate_post()->esc_term_link( $dt ) ) . '">' . esc_html( $dt->name ) . '</a>';
					}
				}
			} else {
				$content .= '<a>No Taxonomy Found.</a>';
			}
			$content .= '</span>';
		} elseif ( ( $title != 'tag' && $title != 'cat' && $title != 'readTime' ) ) {
			$content .= '<span class="ultp-' . esc_attr( $title ) . '-count">';
			if ( $iconEnable ) {
				$content .= wp_kses( $icon, ultimate_post()->rendered_content_allowed_html() ); }
				$content .= esc_html( $data ? $data : 0 );
			$content     .= '</span>';
		}
		if ( ( $title != 'tag' && $title != 'cat' && $title != 'readTime' ) && $labelEnable ) {
			$content .= '<span class="ultp-' . esc_attr( $title ) . '-label">' . wp_kses( $labelText, ultimate_post()->ultp_allowed_html_tags() ) . '</span>';
		}
		if ( $title == 'readTime' ) {
			if ( $iconEnable ) {
				$content .= wp_kses( $icon, ultimate_post()->rendered_content_allowed_html() ); }

			$content .= '<div>' . esc_html( ceil( mb_strlen( wp_strip_all_tags( get_the_content( null, false, $post_id ) ) ) / 1200 ) ) . '</div>';
			$content .= $labelEnable ? '<span class="ultp-read-label">' . wp_kses( $labelText, ultimate_post()->ultp_allowed_html_tags() ) . '</span>' : '';
			$content .= '</span>';
		}
		$content .= '</span>';
		return $content;
	}
}
