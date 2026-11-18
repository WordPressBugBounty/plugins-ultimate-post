<?php
/**
 * Autoload PostX classes using an explicit map of the existing file layout.
 *
 * Add new plugin classes to CLASS_MAP. Keys are lowercase because PHP class
 * names are case-insensitive. No filesystem discovery runs during requests.
 */
namespace ULTP;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	private const CLASS_MAP = array(
		'ultp\\blockstylesource' => 'classes/BlockStyleSource.php',
		'ultp\\blockstyleinput' => 'classes/BlockStyleInput.php',
		'ultp\\blockstylevalues' => 'classes/BlockStyleValues.php',
		'ultp\\blockstyles' => 'classes/BlockStyles.php',
		'ultp\\blockstyleschema' => 'classes/BlockStyleSchema.php',
		'ultp\\globalstyles' => 'classes/GlobalStyles.php',
		'ultp\\blocks' => 'classes/Blocks.php',
		'ultp\\blocks\\advance_post_meta' => 'addons/builder/blocks/Advance_Post_Meta.php',
		'ultp\\blocks\\advanced_filter' => 'blocks/Advanced_Filter.php',
		'ultp\\blocks\\advanced_list' => 'blocks/Advanced_List.php',
		'ultp\\blocks\\advanced_search' => 'blocks/Advanced_Search.php',
		'ultp\\blocks\\archive_title' => 'addons/builder/blocks/Archive_Title.php',
		'ultp\\blocks\\author_box' => 'addons/builder/blocks/Author_Box.php',
		'ultp\\blocks\\button' => 'blocks/Button.php',
		'ultp\\blocks\\dark_light' => 'blocks/Dark_Light.php',
		'ultp\\blocks\\heading' => 'blocks/Heading.php',
		'ultp\\blocks\\image' => 'blocks/Image.php',
		'ultp\\blocks\\news_ticker' => 'blocks/News_Ticker.php',
		'ultp\\blocks\\next_previous' => 'addons/builder/blocks/Next_Previous.php',
		'ultp\\blocks\\post_author_meta' => 'addons/builder/blocks/Post_Author_Meta.php',
		'ultp\\blocks\\post_breadcrumb' => 'addons/builder/blocks/Post_Breadcrumb.php',
		'ultp\\blocks\\post_category' => 'addons/builder/blocks/Post_Category.php',
		'ultp\\blocks\\post_comment_count' => 'addons/builder/blocks/Post_Comment_Count.php',
		'ultp\\blocks\\post_comments' => 'addons/builder/blocks/Post_Comments.php',
		'ultp\\blocks\\post_content' => 'addons/builder/blocks/Post_Content.php',
		'ultp\\blocks\\post_date_meta' => 'addons/builder/blocks/Post_Date_Meta.php',
		'ultp\\blocks\\post_excerpt' => 'addons/builder/blocks/Post_Excerpt.php',
		'ultp\\blocks\\post_featured_image' => 'addons/builder/blocks/Post_Featured_Image.php',
		'ultp\\blocks\\post_grid_1' => 'blocks/Post_Grid_1.php',
		'ultp\\blocks\\post_grid_2' => 'blocks/Post_Grid_2.php',
		'ultp\\blocks\\post_grid_3' => 'blocks/Post_Grid_3.php',
		'ultp\\blocks\\post_grid_4' => 'blocks/Post_Grid_4.php',
		'ultp\\blocks\\post_grid_5' => 'blocks/Post_Grid_5.php',
		'ultp\\blocks\\post_grid_6' => 'blocks/Post_Grid_6.php',
		'ultp\\blocks\\post_grid_7' => 'blocks/Post_Grid_7.php',
		'ultp\\blocks\\post_list_1' => 'blocks/Post_List_1.php',
		'ultp\\blocks\\post_list_2' => 'blocks/Post_List_2.php',
		'ultp\\blocks\\post_list_3' => 'blocks/Post_List_3.php',
		'ultp\\blocks\\post_list_4' => 'blocks/Post_List_4.php',
		'ultp\\blocks\\post_module_1' => 'blocks/Post_Module_1.php',
		'ultp\\blocks\\post_module_2' => 'blocks/Post_Module_2.php',
		'ultp\\blocks\\post_reading_time' => 'addons/builder/blocks/Post_Reading_Time.php',
		'ultp\\blocks\\post_slider_1' => 'blocks/Post_Slider_1.php',
		'ultp\\blocks\\post_slider_2' => 'blocks/Post_Slider_2.php',
		'ultp\\blocks\\post_social_share' => 'addons/builder/blocks/Post_Social_Share.php',
		'ultp\\blocks\\post_tag' => 'addons/builder/blocks/Post_Tag.php',
		'ultp\\blocks\\post_title' => 'addons/builder/blocks/Post_Title.php',
		'ultp\\blocks\\post_view_count' => 'addons/builder/blocks/Post_View_Count.php',
		'ultp\\blocks\\taxonomy' => 'blocks/Taxonomy.php',
		'ultp\\blocks\\youtube_gallery' => 'blocks/Youtube_Gallery.php',
		'ultp\\builder' => 'addons/builder/Builder.php',
		'ultp\\caches' => 'classes/Caches.php',
		'ultp\\compatibility' => 'classes/Compatibility.php',
		'ultp\\css_sanitizer' => 'classes/CSS_Sanitizer.php',
		'ultp\\custom_font' => 'addons/custom_font/Custom_Font.php',
		'ultp\\dashboard' => 'classes/Dashboard.php',
		'ultp\\dccontroller' => 'addons/dynamic_content/includes/DCController.php',
		'ultp\\dcservice' => 'addons/dynamic_content/includes/DCService.php',
		'ultp\\functions' => 'classes/Functions.php',
		'ultp\\importer' => 'classes/Importer.php',
		'ultp\\legacy_keys' => 'classes/Legacy_Keys.php',
		'ultp\\includes\\helper\\xpo' => 'includes/helper/class-xpo.php',
		'ultp\\includes\\notice\\abstract_campaign_notice' => 'includes/notice/class-abstract-campaign-notice.php',
		'ultp\\includes\\notice\\banner_notice' => 'includes/notice/types/class-banner-notice.php',
		'ultp\\includes\\notice\\campaigns' => 'includes/notice/class-campaigns.php',
		'ultp\\includes\\notice\\content_notice' => 'includes/notice/types/class-content-notice.php',
		'ultp\\includes\\notice\\image_notice' => 'includes/notice/types/class-image-notice.php',
		'ultp\\includes\\notice\\notice' => 'includes/notice/class-notice.php',
		'ultp\\includes\\notice\\notice_assets' => 'includes/notice/class-notice-assets.php',
		'ultp\\includes\\notice\\notice_type' => 'includes/notice/interface-notice-type.php',
		'ultp\\includes\\notice\\promo_links' => 'includes/notice/class-promo-links.php',
		'ultp\\options' => 'classes/Options.php',
		'ultp\\request_validation'                         => 'classes/Request_Validation.php',
		'ultp\\requestapi' => 'addons/builder/RequestAPI.php',
		'ultp\\extended_compat' => 'classes/Extended_Compat.php',
		'ultp\\rest_api' => 'classes/REST_API.php',
		'ultp\\saved_templates' => 'addons/templates/Saved_Templates.php',
		'ultp\\shortcode' => 'addons/templates/Shortcode.php',
		'ultp\\styles' => 'classes/Styles.php',
		'ultp\\svg_font_sanitizer' => 'addons/custom_font/Svg_Font_Sanitizer.php',
		'ultp\\templates' => 'classes/Templates.php',
		'ultp\\ultp_initialization' => 'classes/Initialization.php',
	);

	public static function register(): void {
		$callback  = array( self::class, 'load' );
		$autoloads = spl_autoload_functions();
		// PHP 7.x returns false instead of an empty array when no autoloader is registered.
		if ( ! is_array( $autoloads ) || ! in_array( $callback, $autoloads, true ) ) {
			spl_autoload_register( $callback );
		}
	}

	public static function load( string $class ): void {
		$file = self::CLASS_MAP[ strtolower( $class ) ] ?? null;
		if ( null !== $file ) {
			require_once ULTP_PATH . $file;
		}
	}
}
