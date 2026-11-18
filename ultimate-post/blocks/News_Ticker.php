<?php
namespace ULTP\blocks;

defined( 'ABSPATH' ) || exit;

class News_Ticker {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'blockId'                => '',
			'previewImg'             => '',
			// Label Style
			'tickLabelColor'         => '#fff',
			'tickLabelBg'            => '#1974d2',
			'tickLabelTypo'          => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '16',
					'unit' => 'px',
				),
				'spacing'        => (object) array(
					'lg'   => '0',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '27',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
				'family'         => '',
				'weight'         => '500',
			),
			'tickLabelPadding'       => '15',
			'tickLabelSpace'         => (object) array( 'lg' => '160' ),
			'tickShapeStyle'         => 'normal',
			// Ticker body  Style
			'tickerContentHeight'    => '45',
			'tickBodyColor'          => '#444',
			'tickBodyHovColor'       => '#7d7d7d',
			'tickBodyListColor'      => '#037fff',
			'tickerBodyBg'           => '#eeeeee',
			'tickBodyTypo'           => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '16',
					'unit' => 'px',
				),
				'spacing'        => (object) array(
					'lg'   => '0',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '16',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
				'family'         => '',
				'weight'         => '500',
			),
			'tickBodyBorderColor'    => '',
			'tickBodyBorder'         => (object) array(
				'openBorder'   => 0,
				'disableColor' => true,
				'width'        => (object) array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
				),
				'type'         => 'solid',
			),
			'tickBodyRadius'         => (object) array(
				'lg'   => '',
				'unit' => 'px',
			),
			'tickTxtStyle'           => 'normal',
			'tickImgWidth'           => '30',
			'tickImgSpace'           => '10',
			'tickImgRadius'          => (object) array(
				'lg' => (object) array(
					'top'    => '30',
					'bottom' => '30',
					'left'   => '30',
					'right'  => '30',
					'unit'   => 'px',
				),
			),
			'tickBodySpace'          => (object) array( 'lg' => '21' ),
			// Body Time Badge Style
			'timeBadgeType'          => 'days_ago',
			'timeBadgeDateFormat'    => 'M j, Y',
			'tickTimeBadge'          => 'Time Badge',
			'timeBadgeColor'         => '#fff',
			'timeBadgeBg'            => (object) array(
				'openColor' => 1,
				'type'      => 'color',
				'color'     => '#444',
			),
			'timeBadgeTypo'          => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '12',
					'unit' => 'px',
				),
				'spacing'        => (object) array(
					'lg'   => '0',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '16',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
				'family'         => '',
				'weight'         => '500',
			),
			'timeBadgeRadius'        => (object) array(
				'lg' => (object) array(
					'top'    => '100',
					'bottom' => '100',
					'left'   => '100',
					'right'  => '100',
					'unit'   => 'px',
				),
			),
			'timeBadgePadding'       => (object) array(
				'lg' => (object) array(
					'top'    => 3,
					'bottom' => 3,
					'left'   => 6,
					'right'  => 6,
					'unit'   => 'px',
				),
			),
			// Icon Navigator
			'TickNavStyle'           => 'nav1',
			'TickNavIconStyle'       => 'Angle2',
			'TickNavColor'           => '#fff',
			'TickNavBg'              => (object) array(
				'openColor' => 1,
				'type'      => 'color',
				'color'     => '#037fff',
			),
			'TickNavHovColor'        => '#d0d0d0f4',
			'TickNavHovBg'           => (object) array(
				'openColor' => 1,
				'type'      => 'color',
				'color'     => '#53a0ff',
			),
			// Pause Style
			'TickNavPause'           => 'Pause Icon Style',
			'PauseColor'             => '#fff',
			'PauseHovColor'          => '#d0d0d0f4',
			'PauseBg'                => (object) array(
				'openColor' => 1,
				'type'      => 'color',
				'color'     => '#2163ff',
			),
			'PauseHovBg'             => (object) array(
				'openColor' => 1,
				'type'      => 'color',
				'color'     => '#53a0ff',
			),
			// Query Setting
			'queryQuick'             => '',
			'queryNumPosts'          => (object) array( 'lg' => 4 ),
			'queryNumber'            => array( // for compatibility since v.2.5.4
				'type'    => 'string',
				'default' => 4,
			),
			'queryType'              => 'post',
			'queryTax'               => 'category',
			'queryTaxValue'          => '[]',
			'queryRelation'          => 'OR',
			'queryOrderBy'           => 'date',
			'metaKey'                => 'custom_meta_key',
			'queryOrder'             => 'desc',
			'queryExclude'           => '',
			'queryAuthor'            => '[]',
			'queryOffset'            => '0',
			'querySticky'            => true,
			// General Setting
			'tickerType'             => 'vertical',
			'tickerPosition'         => 'up',
			'tickerHeading'          => true,
			'tickTimeShow'           => true,
			'tickImageShow'          => false,
			'navControlToggle'       => true,
			'controlToggle'          => true,
			'pauseOnHover'           => true,
			'tickerDirectionVer'     => 'up',
			'tickerDirectionHorizon' => 'left',
			'tickerSpeed'            => '4000',
			'marqueSpeed'            => '10',
			'tickerSpeedTypewriter'  => '50',
			'tickerAnimation'        => 'slide',
			'typeAnimation'          => 'fadein',
			'openInTab'              => false,
			// Heading Setting/Style
			'headingText'            => 'News Ticker',
			// Advanced
			'advanceId'              => '',
			'advanceZindex'          => '',
			'hideExtraLarge'         => false,
			'hideTablet'             => false,
			'hideMobile'             => false,
		);
	}

	public function register() {
		register_block_type(
			'ultimate-post/news-ticker',
			array(
				'editor_script'   => 'ultp-blocks-editor-script',
				'editor_style'    => 'ultp-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! $noAjax ) {
			$paged         = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
			$attr['paged'] = $paged ? $paged : 1;
		}

		$block_name    = 'news-ticker';
		$wraper_before = $wraper_after = $post_loop = '';

		$attr['queryNumber'] = ultimate_post()->get_post_number( 4, $attr['queryNumber'], $attr['queryNumPosts'] );
		$recent_posts        = new \WP_Query( ultimate_post()->get_query( $attr ) );

		$arrowLeft  = '';
		$arrowRight = '';
		if ( $attr['TickNavIconStyle'] != 'icon2' ) {
			$arrowLeft  .= wp_kses( ultimate_post()->get_svg_icon( 'left' . $attr['TickNavIconStyle'] ), ultimate_post()->rendered_content_allowed_html() );
			$arrowRight .= wp_kses( ultimate_post()->get_svg_icon( 'right' . $attr['TickNavIconStyle'] ), ultimate_post()->rendered_content_allowed_html() );
		}

		$attr['className']              = isset( $attr['className'] ) && $attr['className'] ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['advanceId']              = isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '';
		$attr['blockId']                = isset( $attr['blockId'] ) ? sanitize_html_class( $attr['blockId'] ) : '';
		$attr['TickNavStyle']           = sanitize_html_class( $attr['TickNavStyle'] );
		$attr['TickNavIconStyle']       = sanitize_html_class( $attr['TickNavIconStyle'] );
		$attr['tickerType']             = sanitize_html_class( $attr['tickerType'] );
		$attr['headingText']            = wp_kses( $attr['headingText'], ultimate_post()->ultp_allowed_html_tags() );
		$attr['tickerAnimation']        = sanitize_html_class( $attr['tickerAnimation'] );
		$attr['tickerDirectionVer']     = sanitize_html_class( $attr['tickerDirectionVer'] );
		$attr['tickerDirectionHorizon'] = sanitize_html_class( $attr['tickerDirectionHorizon'] );
		$attr['tickerSpeed']            = sanitize_html_class( $attr['tickerSpeed'] );
		$attr['tickTxtStyle']           = sanitize_html_class( $attr['tickTxtStyle'] );
		$attr['pauseOnHover']           = isset( $attr['pauseOnHover'] ) ? $attr['pauseOnHover'] == true : true;

		// The ticker type is vertical or horizontal unless ultp_news_ticker_type gives another.
		$attr['tickerType'] = sanitize_html_class( apply_filters( 'ultp_news_ticker_type', in_array( $attr['tickerType'], array( 'vertical', 'horizontal' ), true ) ? $attr['tickerType'] : 'vertical', $attr ) );
		// How the selected type animates; the answer is filtered through ultp_news_ticker_animation_options.
		$type_options = apply_filters(
			'ultp_news_ticker_animation_options',
			array(
				'animation'   => $attr['tickerAnimation'], // Suffix of the `ultp-animation-` class.
				'speed'       => $attr['tickerSpeed'],     // `data-speed`, in ms.
				'title_style' => '',                       // Inline style of the title text.
			),
			$attr
		);
		$speed       = isset( $type_options['speed'] ) ? $type_options['speed'] : $attr['tickerSpeed'];
		$animation   = isset( $type_options['animation'] ) ? $type_options['animation'] : $attr['tickerAnimation'];
		$title_style = isset( $type_options['title_style'] ) ? $type_options['title_style'] : '';

		// Inline style of each item. The default delays the item's animation when the animation is fadeout;
		// ultp_news_ticker_item_style can change it.
		$item_style_default = 'fadeout' === $attr['tickerAnimation'] && is_numeric( $attr['tickerSpeed'] ) ? 'animation-delay:' . ( $attr['tickerSpeed'] - 1000 ) . 'ms' : '';
		$item_style         = apply_filters( 'ultp_news_ticker_item_style', $item_style_default, $attr, $speed );

		// Extra class of the wrapper, from the ultp_news_ticker_wrapper_class filter.
		$wrapper_class = apply_filters( 'ultp_news_ticker_wrapper_class', '', $attr );
		$wrapper_class = implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', (string) $wrapper_class ) ) ) );
		$wrapper_class = '' !== $wrapper_class ? ' ' . $wrapper_class : '';

		if ( $recent_posts->have_posts() ) {
			$wraper_before         .= '<div ' . ( $attr['advanceId'] ? 'id="' . esc_attr( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-ultimate-post-' . esc_attr( $block_name ) . ' ultp-block-' . esc_attr( $attr['blockId'] ) . '' . ( $attr['className'] ? ' ' . esc_attr( $attr['className'] ) : '' ) . '">';
				$wraper_before     .= '<div class="ultp-block-wrapper' . esc_attr( $wrapper_class ) . '">';
					$wraper_before .= '<div class="ultp-block-items-wrap">';

						$post_loop .= '<div class="ultp-news-ticker-' . esc_attr( $attr['TickNavStyle'] ) . ' ultp-nav-' . esc_attr( $attr['TickNavIconStyle'] ) . '  ultp-newsTicker-wrap ultp-newstick-' . esc_attr( $attr['tickerType'] ) . ' ">';
			if ( $attr['tickerHeading'] && $attr['headingText'] ) {
				$post_loop .= '<div class="ultp-news-ticker-label">' . wp_kses( $attr['headingText'], ultimate_post()->ultp_allowed_html_tags() ) . '</div>';
			}

							$post_loop     .= '<div class="ultp-news-ticker-box ultp-animation-' . esc_attr( $animation ) . ' ultp-sliderDir-' . ( $attr['tickerType'] == 'vertical' ? esc_attr( $attr['tickerDirectionVer'] ) : esc_attr( $attr['tickerDirectionHorizon'] ) ) . '">';
								$post_loop .= '<ul class="ultp-news-ticker" data-type="' . esc_attr( $attr['tickerType'] ) . '" data-hover="' . esc_attr( $attr['pauseOnHover'] ) . '" data-direction="' . ( $attr['tickerType'] == 'vertical' ? esc_attr( $attr['tickerDirectionVer'] ) : esc_attr( $attr['tickerDirectionHorizon'] ) ) . '" data-speed="' . esc_attr( $speed ) . '">';
			while ( $recent_posts->have_posts() ) :
				$recent_posts->the_post();
				$post_id   = get_the_ID();
				$title     = get_the_title( $post_id );
				$titlelink = get_permalink( $post_id );
				// Action hook for each post the block shows.
				do_action( 'ultp_block_loop_post', $post_id, $attr );
				$typeStyle = $title_style;
				$post_loop         .= '<li style="' . esc_attr( $item_style ) . '">';
					$post_loop     .= '<div class="ultp-list-' . esc_attr( $attr['tickTxtStyle'] ) . '">';
						$post_loop .= '<a ' . ( $attr['openInTab'] ? 'target="_blank"' : '' ) . ' href="' . esc_url( $titlelink ) . '">';
				if ( $attr['tickImageShow'] && has_post_thumbnail() ) {
							$post_loop .= wp_kses( ultimate_post()->get_image( get_post_thumbnail_id( $post_id ), 'thumbnail', '', $title, '', '' ), ultimate_post()->rendered_content_allowed_html() );
				}
							$post_loop .= '<span style="' . esc_attr( $typeStyle ) . '" class="title-text" data-text="' . esc_attr( $title ) . '">' . wp_kses_post( $title ) . '</span>';
							$post_loop .= '</a>';
				// The time badge when tickTimeShow is on. The ultp_news_ticker_time_badge_html filter can replace it.
				$badge_default = '';
				if ( $attr['tickTimeShow'] ) {
					if ( $attr['timeBadgeType'] == 'date' ) {
							// Free offers the `M j, Y` format only. Any other saved format goes through ultp_news_ticker_time_badge_date,
							// whose default is the `M j, Y` text.
							$dateData = get_the_date( 'M j, Y' );
							if ( 'M j, Y' !== $attr['timeBadgeDateFormat'] ) {
								$date_text = apply_filters( 'ultp_news_ticker_time_badge_date', $dateData, $attr['timeBadgeDateFormat'], $post_id, $attr );
								$dateData  = is_string( $date_text ) ? $date_text : $dateData;
							}
					} else {
							$dateData = human_time_diff( get_the_time( 'U' ), current_time( 'U' ) ) . ' ago';
					}
					$badge_default = '<span class="ultp-ticker-timebadge">' . esc_html( $dateData ) . '</span>';
				}
				$post_loop .= ultimate_post()->esc_filtered_markup( apply_filters( 'ultp_news_ticker_time_badge_html', $badge_default, $attr, $post_id ), $badge_default );
														$post_loop .= '</div>';
														$post_loop .= '</li>';
								endwhile;
								$post_loop .= '</ul>';
							$post_loop     .= '</div>';

			if ( $attr['navControlToggle'] && $attr['TickNavStyle'] ) {
				$post_loop         .= '<div class="ultp-news-ticker-controls ultp-news-ticker-vertical-controls">';
						$post_loop .= '<button aria-label="' . esc_attr__( 'Show Previous Post', 'ultimate-post' ) . '" class="ultp-news-ticker-arrow ultp-news-ticker-prev prev">' . $arrowLeft . '</button>';
				if ( $attr['controlToggle'] && ( $attr['TickNavStyle'] == 'nav1' || $attr['TickNavStyle'] == 'nav3' || $attr['TickNavStyle'] == 'nav4' ) ) {
							$post_loop .= '<button aria-label="' . esc_attr__( 'Pause Current Post', 'ultimate-post' ) . '" id="ultp-pause-btn" class="ultp-news-ticker-pause pause"></button>';
				}
						$post_loop         .= '<button aria-label="' . esc_attr__( 'Show Next Post', 'ultimate-post' ) . '" class="ultp-news-ticker-arrow ultp-news-ticker-next next">' . $arrowRight . '</button>';
								$post_loop .= '</div>';
			}
						$post_loop .= '</div>';
					$wraper_after  .= '</div>';
				$wraper_after      .= '</div>';
			$wraper_after          .= '</div>';

			wp_reset_postdata();
		}

		return $noAjax ? $post_loop : $wraper_before . $post_loop . $wraper_after;
	}
}
