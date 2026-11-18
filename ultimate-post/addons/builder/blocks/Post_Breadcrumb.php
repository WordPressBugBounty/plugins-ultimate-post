<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class Post_Breadcrumb {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}
	public function get_attributes() {

		return array(
			'blockId'             => '',

			/*
			============================
				Breadcrumb Settings
			============================*/
			'bcrumbSeparator'     => true,
			'bcrumbName'          => true,
			'bcrumbRootText'      => 'Home',

			/*
			============================
				Separator Style
			============================*/
			'bcrumbSeparatorIcon' => 'dash',

			/*
			============================
				Advance Settings
			============================*/
			'advanceId'           => '',
			'advanceZindex'       => '',
			'hideExtraLarge'      => false,
			'hideDesktop'         => false,
			'hideTablet'          => false,
			'hideMobile'          => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/post-breadcrumb',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );
		global $post;
		$block_name     = 'post-breadcrumb';
		$wrapper_before = $wrapper_after = $content = '';

        $attr['className'] = isset($attr['className']) && $attr['className'] ? preg_replace('/[^A-Za-z0-9_ -]/', '', $attr['className']) : '';
        $attr['align'] = isset($attr['align']) && $attr['align'] ? preg_replace('/[^A-Za-z0-9_ -]/', '', $attr['align']) : '';
        $attr['advanceId'] = isset($attr['advanceId']) ? sanitize_html_class( $attr['advanceId'] ) : '';
        $attr['blockId'] = isset($attr['blockId']) ? sanitize_html_class( $attr['blockId'] ) : '';
        $attr['bcrumbRootText'] = wp_kses($attr['bcrumbRootText'], ultimate_post()->ultp_allowed_html_tags());
        $separator = '';

        $wrapper_before .= '<div '.( $attr['advanceId'] ? 'id="'.esc_attr( $attr['advanceId'] ).'" ':'' ).' class="wp-block-ultimate-post-'.esc_attr( $block_name ).' ultp-block-'.esc_attr( $attr["blockId"] ).( $attr["className"] ?' '.esc_attr( $attr["className"] ):'' ).''.( $attr["align"] ? ' align' .esc_attr( $attr["align"] ):'' ).'">';
            $wrapper_before .= '<div class="ultp-block-wrapper">';
                $content .= '<ul class="ultp-builder-breadcrumb ultp-breadcrumb-'.esc_attr( sanitize_html_class( $attr['bcrumbSeparatorIcon'] ) ).'">';
                    if ($attr['bcrumbSeparator']) {
                        $separator .= '<li class="ultp-breadcrumb-separator"></li>';
                    }
                    $content .= '<li><a href="'.esc_url(home_url('/')).'">'.( strlen($attr['bcrumbRootText']) > 0 && 'Home' !== $attr['bcrumbRootText'] ? wp_kses( $attr['bcrumbRootText'], ultimate_post()->ultp_allowed_html_tags() ) : esc_html__( 'Home', 'ultimate-post' ) ).'</a></li>';
                    if (is_category() || is_single() || is_tag() || is_author()) {
                        if (is_category()) {
                            $cat = get_queried_object();
                            if (isset($cat) && is_object($cat)) {
                                $parents = [];
                                $parent_id = $cat->parent;
                                while ($parent_id) {
                                    $parent = get_category($parent_id);
                                    if ($parent && $parent->term_id != 0) {
                                        array_unshift($parents, $parent); // Add to the beginning
                                        $parent_id = $parent->parent;
                                    } else {
                                        break;
                                    }
                                }
                                foreach ($parents as $parent_cat) {
                                    $content .= $separator.'<li><a href="'.esc_url( ultimate_post()->esc_term_link( $parent_cat->term_id, 'category' ) ).'">'.esc_html($parent_cat->name).'</a></li>';
                                }
                            }
                            $content .= $separator.'<li>'.esc_html( single_cat_title('', false) ).'</li>';
                        }
                        if (is_tag()) {
                            $content .= $separator.'<li>'.esc_html( single_tag_title('', false) ).'</li>';
                        }
                        if (is_author()) {
                            $content .= $separator.'<li>'.esc_html( get_the_author_meta('display_name', false) ).'</li>';
                        }
                        if (is_single()) {
                            $cat = get_the_category();
                            if (isset($cat[0])) {
                                $content .= $separator.'<li><a href="'.esc_url( ultimate_post()->esc_term_link( $cat[0]->term_id ) ).'">'.esc_html( $cat[0]->name ).'</a></li>';
                            }
                            if ($attr['bcrumbName']) {
                                $content .= $separator.'<li>'.wp_kses_post( get_the_title() ).'</li>';
                            }
                        }
                    } elseif (is_page()) {
                        if ($post->post_parent) {
                            $ancestors = array_reverse( get_post_ancestors( $post ) ); // Topmost first, includes the direct parent
                            foreach ($ancestors as $ancestor_id) {
                                $content .= $separator.'<li><a href="'.esc_url( get_permalink($ancestor_id) ).'">'.wp_kses_post( get_the_title($ancestor_id) ).'</a></li>';
                            }
                        }
                        if ($attr['bcrumbName']) {
                            $content .= $separator.'<li>'.wp_kses_post( get_the_title() ).'</li>';
                        }
                    } elseif (is_search()) {
                        $content .= $separator.'<li>'.esc_html( get_search_query() ).'</li>';
                    }
                $content .= '</ul>';
            $wrapper_after .= '</div>';
        $wrapper_after .= '</div>';

		return $wrapper_before . $content . $wrapper_after;
	}
}
