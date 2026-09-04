<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Unyson+ blocks.
 *
 * ## The bridge, in one paragraph
 *
 * Every block here is a DYNAMIC block: it stores option values as a single
 * `upOptions` object attribute and renders on the server by delegating to the
 * matching Unyson+ shortcode. Nothing about the front end changes — the same
 * PHP produces the same HTML and enqueues the same assets as the page builder
 * does. A block is a second *authoring* surface, never a second *rendering*
 * path; that is what stops the element library forking in two.
 *
 * ## Why the inspector is built from the option schema
 *
 * The sidebar controls are not hand-written per block. Each block declares which
 * option paths it exposes, PHP hands that schema to the editor, and the React
 * control layer (`fw.controls`, see UnysonPlus\Admin\Controls\Registry) renders
 * whatever it recognises. The schema stays the single source of truth, exactly
 * as it is for the PHP renderer.
 */
class FW_Extension_Blocks extends FW_Extension {

	/**
	 * Registered block definitions, keyed by block directory name.
	 *
	 * @var array|null
	 */
	private $blocks = null;

	/**
	 * @internal
	 */
	public function _init() {
		add_action( 'init', array( $this, '_action_register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, '_action_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, '_action_block_assets' ) );
		add_filter( 'block_categories_all', array( $this, '_filter_block_category' ), 10, 1 );
	}

	/**
	 * Shortcode tag => block name, for code that has to translate between the two
	 * authoring surfaces — currently the page builder's block-markup exporter.
	 *
	 * Public because the mapping is the ONLY thing an exporter needs, and reading it
	 * beats every caller re-deriving `unysonplus/<dir>` from a tag (the two are not
	 * always a simple dash/underscore swap, and a block can be dropped without its
	 * shortcode going away). A tag absent from this map has no block — the caller is
	 * expected to fall back rather than guess a name.
	 *
	 * @return array<string,string>
	 */
	public function get_shortcode_block_map() {
		$map = array();

		foreach ( $this->get_blocks() as $dir => $definition ) {
			if ( ! is_array( $definition ) || empty( $definition['shortcode'] ) ) {
				continue;
			}
			$map[ (string) $definition['shortcode'] ] = 'unysonplus/' . $dir;
		}

		return $map;
	}

	/**
	 * Block definitions.
	 *
	 * `shortcode` is the tag the block delegates its render to. `options` maps an
	 * `fw_akg()` path inside the shortcode's saved atts to the option schema entry
	 * that should edit it — the same array shape an options.php file declares.
	 *
	 * @return array
	 */
	private function get_blocks() {
		if ( null !== $this->blocks ) {
			return $this->blocks;
		}

		$this->blocks = array(
			'wc-products' => array(
				'shortcode' => 'wc_products',
				'extension' => 'woocommerce',
				'options'   => $this->wc_products_options(),
				'styles'    => array(
					'fw-shortcode-wc_products' => '/shortcodes/wc_products/static/css/styles.css',
				),
			),
			'wc-product' => array(
				'shortcode' => 'wc_product',
				'extension' => 'woocommerce',
				'options'   => $this->wc_product_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-product-page' => array(
				'shortcode' => 'wc_product_page',
				'extension' => 'woocommerce',
				'options'   => $this->wc_product_page_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-product-categories' => array(
				'shortcode' => 'wc_product_categories',
				'extension' => 'woocommerce',
				'options'   => $this->wc_product_categories_options(),
				'styles'    => array(
					'fw-shortcode-wc_product_categories' => '/shortcodes/wc_product_categories/static/css/styles.css',
				),
			),
			'wc-product-filters' => array(
				'shortcode' => 'wc_product_filters',
				'extension' => 'woocommerce',
				'options'   => $this->wc_product_filters_options(),
				'styles'    => array(
					'fw-shortcode-wc_product_filters' => '/shortcodes/wc_product_filters/static/css/styles.css',
				),
			),
			'wc-product-search' => array(
				'shortcode' => 'wc_product_search',
				'extension' => 'woocommerce',
				'options'   => $this->wc_product_search_options(),
				'styles'    => array(
					'fw-shortcode-wc_product_search' => '/shortcodes/wc_product_search/static/css/styles.css',
				),
			),
			'wc-add-to-cart' => array(
				'shortcode' => 'wc_add_to_cart',
				'extension' => 'woocommerce',
				'options'   => $this->wc_add_to_cart_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-cart-link' => array(
				'shortcode' => 'wc_cart_link',
				'extension' => 'woocommerce',
				'options'   => $this->wc_cart_link_options(),
				'styles'    => array(
					'fw-shortcode-wc_cart_link' => '/shortcodes/wc_cart_link/static/css/styles.css',
				),
			),
			'wc-mini-cart' => array(
				'shortcode' => 'wc_mini_cart',
				'extension' => 'woocommerce',
				'options'   => $this->wc_mini_cart_options(),
				'styles'    => array(
					'fw-shortcode-wc_mini_cart' => '/shortcodes/wc_mini_cart/static/css/styles.css',
				),
			),
			'wc-account' => array(
				'shortcode' => 'wc_account',
				'extension' => 'woocommerce',
				'options'   => $this->wc_account_options(),
				'styles'    => array(
					'fw-shortcode-wc_account' => '/shortcodes/wc_account/static/css/styles.css',
				),
			),
			'wc-free-shipping' => array(
				'shortcode' => 'wc_free_shipping',
				'extension' => 'woocommerce',
				'options'   => $this->wc_free_shipping_options(),
				'no_options_note' => __( 'This element has no settings. It reads the free-shipping threshold from your WooCommerce shipping zones, and its wording and appearance come from the theme.', 'fw' ),
				'styles'    => array(
					'fw-shortcode-wc_free_shipping' => '/shortcodes/wc_free_shipping/static/css/styles.css',
				),
			),
			'wc-cart' => array(
				'shortcode' => 'wc_cart',
				'extension' => 'woocommerce',
				'options'   => $this->wc_cart_options(),
				'no_options_note' => __( 'This element renders WooCommerce\'s own template. Its appearance comes from WooCommerce settings and your theme, not from this block.', 'fw' ),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-checkout' => array(
				'shortcode' => 'wc_checkout',
				'extension' => 'woocommerce',
				'options'   => $this->wc_checkout_options(),
				'no_options_note' => __( 'This element renders WooCommerce\'s own template. Its appearance comes from WooCommerce settings and your theme, not from this block.', 'fw' ),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-my-account' => array(
				'shortcode' => 'wc_my_account',
				'extension' => 'woocommerce',
				'options'   => $this->wc_my_account_options(),
				'no_options_note' => __( 'This element renders WooCommerce\'s own template. Its appearance comes from WooCommerce settings and your theme, not from this block.', 'fw' ),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'wc-order-tracking' => array(
				'shortcode' => 'wc_order_tracking',
				'extension' => 'woocommerce',
				'options'   => $this->wc_order_tracking_options(),
				'no_options_note' => __( 'This element renders WooCommerce\'s own template. Its appearance comes from WooCommerce settings and your theme, not from this block.', 'fw' ),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'portfolio' => array(
				'shortcode' => 'portfolio',
				'extension' => 'portfolio',
				'options'   => $this->portfolio_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'project-details' => array(
				'shortcode' => 'project_details',
				'extension' => 'portfolio',
				'options'   => $this->project_details_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'project-gallery' => array(
				'shortcode' => 'project_gallery',
				'extension' => 'portfolio',
				'options'   => $this->project_gallery_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'project-nav' => array(
				'shortcode' => 'project_nav',
				'extension' => 'portfolio',
				'options'   => $this->project_nav_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'project-results' => array(
				'shortcode' => 'project_results',
				'extension' => 'portfolio',
				'options'   => $this->project_results_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'project-testimonial' => array(
				'shortcode' => 'project_testimonial',
				'extension' => 'portfolio',
				'options'   => $this->project_testimonial_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'related-projects' => array(
				'shortcode' => 'related_projects',
				'extension' => 'portfolio',
				'options'   => $this->related_projects_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'table' => array(
				'shortcode' => 'table',
				'options'   => $this->table_options(),
				'styles'    => array(
					'fw-shortcode-table' => '/shortcodes/table/static/css/styles.css',
				),
			),
			'contact-form' => array(
				'shortcode' => 'contact_form',
				'extension' => 'forms',
				'options'   => $this->contact_form_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'section' => array(
				'shortcode' => 'section',
				'options'   => $this->section_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'column' => array(
				'shortcode' => 'column',
				// The page builder gives a column its width from the row's item model;
				// a standalone block has no row, so it defaults to full width.
				'atts_defaults' => array( 'width' => '1_1' ),
				'options'   => $this->column_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'container' => array(
				'shortcode' => 'container',
				'options'   => $this->container_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'flexbox' => array(
				'shortcode' => 'flexbox',
				'options'   => $this->flexbox_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'bleed-section' => array(
				'shortcode' => 'bleed_section',
				'options'   => $this->bleed_section_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'masonry-section' => array(
				'shortcode' => 'masonry_section',
				'options'   => $this->masonry_section_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'flip-box' => array(
				'shortcode' => 'flip_box',
				'options'   => $this->flip_box_options(),
				'styles'    => array(
					'fw-shortcode-flip-box' => '/shortcodes/flip-box/static/css/styles.css',
				),
			),
			'gallery-3d' => array(
				'shortcode' => 'gallery_3d',
				'extension' => 'animation-engine',
				'options'   => $this->gallery_3d_options(),
				'styles'    => array(
					'fw-shortcode-gallery-3d' => '/shortcodes/gallery-3d/static/css/gallery-3d.css',
				),
			),
			'post-title' => array(
				'shortcode' => 'post_title',
				'options'   => $this->post_title_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-content' => array(
				'shortcode' => 'post_content',
				'options'   => $this->post_content_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-date' => array(
				'shortcode' => 'post_date',
				'options'   => $this->post_date_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-excerpt' => array(
				'shortcode' => 'post_excerpt',
				'options'   => $this->post_excerpt_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-meta' => array(
				'shortcode' => 'post_meta',
				'options'   => $this->post_meta_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-terms' => array(
				'shortcode' => 'post_terms',
				'options'   => $this->post_terms_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'post-author' => array(
				'shortcode' => 'post_author',
				'options'   => $this->post_author_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'site-logo' => array(
				'shortcode' => 'site_logo',
				'options'   => $this->site_logo_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'nav-menu' => array(
				'shortcode' => 'nav_menu',
				'options'   => $this->nav_menu_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'menu-toggle' => array(
				'shortcode' => 'menu_toggle',
				'options'   => $this->menu_toggle_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'widget-area' => array(
				'shortcode' => 'widget_area',
				'options'   => $this->widget_area_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'scroll-to-top' => array(
				'shortcode' => 'scroll_to_top',
				'options'   => $this->scroll_to_top_options(),
				'styles'    => array(
					'fw-shortcode-scroll-to-top' => '/shortcodes/scroll-to-top/static/css/styles.css',
				),
			),
			'call-to-action' => array(
				'shortcode' => 'call_to_action',
				'options'   => $this->call_to_action_options(),
				'styles'    => array(
					'fw-shortcode-call-to-action' => '/shortcodes/call-to-action/static/css/styles.css',
				),
			),
			'image-content' => array(
				'shortcode' => 'image_content',
				'options'   => $this->image_content_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe.
			),
			'countdown' => array(
				'shortcode' => 'countdown',
				'options'   => $this->countdown_options(),
				'styles'    => array(
					'fw-shortcode-countdown' => '/shortcodes/countdown/static/css/styles.css',
				),
			),
			'code-block' => array(
				'shortcode' => 'code_block',
				'options'   => $this->code_block_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe.
			),
			'posts' => array(
				'shortcode' => 'posts',
				'options'   => $this->posts_options(),
				'styles'    => array(
					'fw-shortcode-posts' => '/shortcodes/posts/static/css/styles.css',
				),
			),
			'toc' => array(
				'shortcode' => 'toc',
				'options'   => $this->toc_options(),
				'styles'    => array(
					'fw-shortcode-toc' => '/shortcodes/toc/static/css/styles.css',
				),
			),
			'button' => array(
				'shortcode' => 'button',
				'options'   => $this->button_options(),
				'styles'    => array(
					'fw-shortcode-button' => '/shortcodes/button/static/css/styles.css',
					// The element ships TWO stylesheets. hover-fx.css defines the
					// `.btnfx-*` classes the rendered button carries, so without it a
					// button with a hover effect looks correct at rest and does nothing
					// on hover — in the canvas only, which is the confusing half.
					'fw-shortcode-button-hover-fx' => '/shortcodes/button/static/css/hover-fx.css',
				),
			),
			'pricing-table' => array(
				'shortcode' => 'pricing_table',
				'options'   => $this->pricing_table_options(),
				'styles'    => array(
					'fw-shortcode-pricing-table' => '/shortcodes/pricing-table/static/css/styles.css',
				),
			),
			'image-sequence' => array(
				'shortcode' => 'image_sequence',
				'extension' => 'animation-engine',
				'options'   => $this->image_sequence_options(),
				'styles'    => array(
					'fw-shortcode-image-sequence' => '/shortcodes/image-sequence/static/css/image-sequence.css',
				),
			),
			'svg-morph' => array(
				'shortcode' => 'svg_morph',
				'extension' => 'animation-engine',
				'options'   => $this->svg_morph_options(),
				'styles'    => array(
					'fw-shortcode-svg-morph' => '/shortcodes/svg-morph/static/css/svg-morph.css',
				),
			),
			'webgl-object' => array(
				'shortcode' => 'webgl_object',
				'extension' => 'animation-engine',
				'options'   => $this->webgl_object_options(),
				'styles'    => array(
					'fw-shortcode-webgl-object' => '/shortcodes/webgl-object/static/css/webgl-object.css',
				),
			),
			'global-section' => array(
				'shortcode' => 'global_section',
				'extension' => 'snippets',
				'options'   => $this->global_section_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'snippet' => array(
				'shortcode' => 'snippet',
				'extension' => 'snippets',
				'options'   => $this->snippet_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'scroll-indicator' => array(
				'shortcode' => 'scroll_indicator',
				'options'   => $this->scroll_indicator_options(),
				'styles'    => array(
					'fw-shortcode-scroll-indicator' => '/shortcodes/scroll-indicator/static/css/styles.css',
				),
			),
			'site-search' => array(
				'shortcode' => 'site_search',
				'options'   => $this->site_search_options(),
				// No 'styles': this element ships no stylesheet of its own, so there is
				// nothing to push into the canvas iframe. Naming a file that does not
				// exist would enqueue a 404 on every editor load.
			),
			'lottie' => array(
				'shortcode' => 'lottie',
				'options'   => $this->lottie_options(),
				'styles'    => array(
					'fw-shortcode-lottie' => '/shortcodes/lottie/static/css/styles.css',
				),
			),
			'svg-draw' => array(
				'shortcode' => 'svg_draw',
				'extension' => 'animation-engine',
				'options'   => $this->svg_draw_options(),
				'styles'    => array(
					'fw-shortcode-svg-draw' => '/shortcodes/svg-draw/static/css/svg-draw.css',
				),
			),
			'model-viewer' => array(
				'shortcode' => 'model_viewer',
				'extension' => 'animation-engine',
				'options'   => $this->model_viewer_options(),
				'styles'    => array(
					'fw-shortcode-model-viewer' => '/shortcodes/model-viewer/static/css/model-viewer.css',
				),
			),
			'avatar' => array(
				'shortcode' => 'avatar',
				'options'   => $this->avatar_options(),
				'styles'    => array(
					'fw-shortcode-avatar' => '/shortcodes/avatar/static/css/styles.css',
				),
			),
			'media-image' => array(
				'shortcode' => 'media_image',
				'options'   => $this->media_image_options(),
				// No 'styles': this element ships no stylesheet of its own — it is drawn
				// by the theme's rules and the Image Style presets. Naming a file that
				// does not exist would enqueue a 404 on every editor load.
			),
			'media-video' => array(
				'shortcode' => 'media_video',
				'options'   => $this->media_video_options(),
				'styles'    => array(
					'fw-shortcode-media-video' => '/shortcodes/media-video/static/css/media-video.css',
				),
			),
			'text-block' => array(
				'shortcode' => 'text_block',
				'options'   => $this->text_block_options(),
				'styles'    => array(
					'fw-shortcode-text-block' => '/shortcodes/text-block/static/css/styles.css',
				),
			),
			'featured-image' => array(
				'shortcode' => 'featured_image',
				'options'   => $this->featured_image_options(),
				// No 'styles': this element ships no stylesheet of its own — it is drawn
				// by the theme's rules and the Image Style presets. Naming a file that
				// does not exist would enqueue a 404 on every editor load.
			),
			'audio-player' => array(
				'shortcode' => 'audio_player',
				'options'   => $this->audio_player_options(),
				'styles'    => array(
					'fw-shortcode-audio-player' => '/shortcodes/audio-player/static/css/styles.css',
				),
			),
			'author-box' => array(
				'shortcode' => 'author_box',
				'options'   => $this->author_box_options(),
				'styles'    => array(
					'fw-shortcode-author-box' => '/shortcodes/author-box/static/css/styles.css',
				),
			),
			'calendar' => array(
				'shortcode' => 'calendar',
				'options'   => $this->calendar_options(),
				'styles'    => array(
					'fw-shortcode-calendar' => '/shortcodes/calendar/static/css/styles.css',
				),
			),
			'image-hotspots' => array(
				'shortcode' => 'image_hotspots',
				'options'   => $this->image_hotspots_options(),
				'styles'    => array(
					'fw-shortcode-image-hotspots' => '/shortcodes/image-hotspots/static/css/styles.css',
				),
			),
			'map' => array(
				'shortcode' => 'map',
				'options'   => $this->map_options(),
				'styles'    => array(
					'fw-shortcode-map' => '/shortcodes/map/static/css/styles.css',
				),
			),
			'image-box' => array(
				'shortcode' => 'image_box',
				'options'   => $this->image_box_options(),
				'styles'    => array(
					'fw-shortcode-image-box' => '/shortcodes/image-box/static/css/styles.css',
				),
			),
			'gallery' => array(
				'shortcode' => 'gallery',
				'options'   => $this->gallery_options(),
				'styles'    => array(
					'fw-shortcode-gallery' => '/shortcodes/gallery/static/css/styles.css',
				),
			),
			'team-member' => array(
				'shortcode' => 'team_member',
				'options'   => $this->team_member_options(),
				'styles'    => array(
					'fw-shortcode-team-member' => '/shortcodes/team-member/static/css/styles.css',
				),
			),
			'progress' => array(
				'shortcode' => 'progress',
				'options'   => $this->progress_options(),
				'styles'    => array(
					'fw-shortcode-progress' => '/shortcodes/progress/static/css/styles.css',
				),
			),
			'business-info' => array(
				'shortcode' => 'business_info',
				'options'   => $this->business_info_options(),
				'styles'    => array(
					'fw-shortcode-business-info' => '/shortcodes/business-info/static/css/styles.css',
				),
			),
			'carousel' => array(
				'shortcode' => 'carousel',
				'options'   => $this->carousel_options(),
				'styles'    => array(
					'fw-shortcode-carousel' => '/shortcodes/carousel/static/css/styles.css',
				),
			),
			'feature-list' => array(
				'shortcode' => 'feature_list',
				'options'   => $this->feature_list_options(),
				'styles'    => array(
					'fw-shortcode-feature-list' => '/shortcodes/feature-list/static/css/styles.css',
				),
			),
			'comparison-table' => array(
				'shortcode' => 'comparison_table',
				'options'   => $this->comparison_table_options(),
				'styles'    => array(
					'fw-shortcode-comparison-table' => '/shortcodes/comparison-table/static/css/styles.css',
				),
			),
			'icon' => array(
				'shortcode' => 'icon',
				'options'   => $this->icon_options(),
				'styles'    => array(
					'fw-shortcode-icon' => '/shortcodes/icon/static/css/styles.css',
				),
			),
			'accordion' => array(
				'shortcode' => 'accordion',
				'options'   => $this->accordion_options(),
				'styles'    => array(
					'fw-shortcode-accordion' => '/shortcodes/accordion/static/css/styles.css',
				),
			),
			'testimonials' => array(
				'shortcode' => 'testimonials',
				'options'   => $this->testimonials_options(),
				'styles'    => array(
					'fw-shortcode-testimonials' => '/shortcodes/testimonials/static/css/styles.css',
				),
			),
			'social-icons' => array(
				'shortcode' => 'social_icons',
				'options'   => $this->social_icons_options(),
				// No 'styles': this element ships no stylesheet of its own. It is drawn
				// by the icon font and the theme's own rules, so there is nothing to
				// push into the canvas iframe — and naming a file that does not exist
				// would enqueue a 404 on every editor load.
			),
			'divider' => array(
				'shortcode' => 'divider',
				'options'   => $this->divider_options(),
				'styles'    => array(
					'fw-shortcode-divider' => '/shortcodes/divider/static/css/styles.css',
				),
			),
			'tabs' => array(
				'shortcode' => 'tabs',
				'options'   => $this->tabs_options(),
				'styles'    => array(
					'fw-shortcode-tabs' => '/shortcodes/tabs/static/css/styles.css',
				),
			),
			'steps' => array(
				'shortcode' => 'steps',
				'options'   => $this->steps_options(),
				'styles'    => array(
					'fw-shortcode-steps' => '/shortcodes/steps/static/css/styles.css',
				),
			),
			'timeline' => array(
				'shortcode' => 'timeline',
				'options'   => $this->timeline_options(),
				'styles'    => array(
					'fw-shortcode-timeline' => '/shortcodes/timeline/static/css/styles.css',
				),
			),
			'logo-grid' => array(
				'shortcode' => 'logo_grid',
				'options'   => $this->logo_grid_options(),
				'styles'    => array(
					'fw-shortcode-logo-grid' => '/shortcodes/logo-grid/static/css/styles.css',
				),
			),
			'notification' => array(
				'shortcode' => 'notification',
				'options'   => $this->notification_options(),
				'styles'    => array(
					'fw-shortcode-notification' => '/shortcodes/notification/static/css/styles.css',
				),
			),
			'highlight-text' => array(
				'shortcode' => 'highlight_text',
				'options'   => $this->highlight_text_options(),
				'styles'    => array(
					'fw-shortcode-highlight-text' => '/shortcodes/highlight-text/static/css/styles.css',
				),
			),
			'tooltip' => array(
				'shortcode' => 'tooltip',
				'options'   => $this->tooltip_options(),
				'styles'    => array(
					'fw-shortcode-tooltip' => '/shortcodes/tooltip/static/css/styles.css',
				),
			),
			'social-share' => array(
				'shortcode' => 'social_share',
				'options'   => $this->social_share_options(),
				'styles'    => array(
					'fw-shortcode-social-share' => '/shortcodes/social-share/static/css/styles.css',
				),
			),
			'modal-popup' => array(
				'shortcode' => 'modal_popup',
				'options'   => $this->modal_popup_options(),
				'styles'    => array(
					'fw-shortcode-modal-popup' => '/shortcodes/modal-popup/static/css/styles.css',
				),
			),
			'animated-heading' => array(
				'shortcode' => 'animated_heading',
				'options'   => $this->animated_heading_options(),
				'styles'    => array(
					'fw-shortcode-animated-heading' => '/shortcodes/animated-heading/static/css/styles.css',
				),
			),
			'newsletter' => array(
				'shortcode' => 'newsletter',
				'options'   => $this->newsletter_options(),
				'styles'    => array(
					'fw-shortcode-newsletter' => '/shortcodes/newsletter/static/css/styles.css',
				),
			),
			'blockquote' => array(
				'shortcode' => 'blockquote',
				'options'   => $this->blockquote_options(),
				'styles'    => array(
					'fw-shortcode-blockquote' => '/shortcodes/blockquote/static/css/styles.css',
				),
			),
			'badge' => array(
				'shortcode' => 'badge',
				'options'   => $this->badge_options(),
				'styles'    => array(
					'fw-shortcode-badge' => '/shortcodes/badge/static/css/styles.css',
				),
			),
			'tag-list' => array(
				'shortcode' => 'tag_list',
				'options'   => $this->tag_list_options(),
				'styles'    => array(
					'fw-shortcode-tag-list' => '/shortcodes/tag-list/static/css/styles.css',
				),
			),
			'text-expander' => array(
				'shortcode' => 'text_expander',
				'options'   => $this->text_expander_options(),
				'styles'    => array(
					'fw-shortcode-text-expander' => '/shortcodes/text-expander/static/css/styles.css',
				),
			),
			'special-heading' => array(
				'shortcode' => 'special_heading',
				'options'   => $this->special_heading_options(),
				'styles'    => array(
					'fw-shortcode-special-heading' => '/shortcodes/special-heading/static/css/styles.css',
				),
			),
			'icon-box' => array(
				'shortcode' => 'icon_box',
				'options'   => $this->icon_box_options(),
				'styles'    => array(
					'fw-shortcode-icon-box' => '/shortcodes/icon-box/static/css/styles.css',
				),
			),
			'video-popup' => array(
				'shortcode' => 'video_popup',
				'options'   => $this->video_popup_options(),
				'styles'    => array(
					'fw-shortcode-video-popup' => '/shortcodes/video-popup/static/css/styles.css',
				),
			),
			'star-rating' => array(
				'shortcode' => 'star_rating',
				'options'   => $this->star_rating_options(),
				'styles'    => array(
					'fw-shortcode-star-rating' => '/shortcodes/star-rating/static/css/styles.css',
				),
			),
			'counter' => array(
				'shortcode' => 'counter',
				'options'   => $this->counter_options(),
				'styles'    => array(
					'fw-shortcode-counter' => '/shortcodes/counter/static/css/styles.css',
				),
			),
			'before-after' => array(
				'shortcode' => 'before_after',
				'options'   => $this->before_after_options(),
				// Stylesheets (paths inside the shortcodes extension) that must
				// reach the editor's canvas IFRAME — see register_block_styles().
				'styles'    => array(
					'fw-shortcode-before-after' => '/shortcodes/before-after/static/css/styles.css',
				),
			),
		);

		/**
		 * Filters the registered block definitions keyed by directory name.
		 *
		 * Filter the block definitions.
		 *
		 * @param array $blocks Block definitions keyed by directory name.
		 */
		$this->blocks = apply_filters( 'fw_ext_blocks_definitions', $this->blocks );

		return $this->blocks;
	}

	/**
	 * The option paths the Products block exposes in its inspector.
	 *
	 * The first block from the WOOCOMMERCE extension, which ships inactive AND
	 * requires the WooCommerce plugin. The availability guard covers both: the
	 * element only registers when the extension is active, and the extension only
	 * registers its elements when WooCommerce is present — so the block appears
	 * exactly when it can work.
	 *
	 * `card_preview` is omitted, as on Testimonials: an html-full sample of a card
	 * the canvas already renders for real.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_products_options() {
		return $this->pick_shortcode_options( 'wc_products', array(
			'source', 'category', 'tags', 'attribute',
			'attribute_terms', 'product_ids', 'posts_per_page', 'orderby',
			'order', 'layout', 'columns', 'gap',
			'alignment', 'pagination', 'carousel_arrows', 'card_rows',
			'box_style', 'image_ratio', 'image_size', 'rating_symbol',
			'rating_fill_color', 'rating_empty_color', 'rating_size', 'show_ribbon',
			'show_sale_badge', 'badge_style', 'show_featured_badge', 'show_new_badge',
			'new_days', 'add_to_cart_text',
		) );
	}

	/**
	 * The option paths the Single Product block exposes in its inspector.
	 *
	 * A card for one chosen product. For the full product page — gallery, tabs,
	 * variations — use Product Page instead.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_product_options() {
		return $this->pick_shortcode_options( 'wc_product', array(
			'product', 'card_rows', 'box_style', 'image_ratio',
			'image_size', 'rating_symbol', 'rating_fill_color', 'rating_empty_color',
			'rating_size', 'show_ribbon', 'show_sale_badge', 'badge_style',
			'show_featured_badge', 'show_new_badge', 'new_days', 'add_to_cart_text',
		) );
	}

	/**
	 * The option paths the Product Page block exposes in its inspector.
	 *
	 * One option, because the element renders WooCommerce's whole single-product
	 * template. Everything about how it looks comes from WooCommerce and the theme.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_product_page_options() {
		return $this->pick_shortcode_options( 'wc_product_page', array(
			'product',
		) );
	}

	/**
	 * The option paths the Product Categories block exposes in its inspector.
	 *
	 * `hide_empty` is worth a deliberate answer on a new shop: with it off, a
	 * freshly created category with no products yet still shows, which is either a
	 * useful placeholder or a dead end depending on how far along the shop is.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_product_categories_options() {
		return $this->pick_shortcode_options( 'wc_product_categories', array(
			'number', 'orderby', 'order', 'parent',
			'ids', 'hide_empty', 'columns', 'gap',
			'alignment', 'card_rows', 'box_style', 'image_ratio',
			'image_size', 'button_text',
		) );
	}

	/**
	 * The option paths the Product Filters block exposes in its inspector.
	 *
	 * Pairs with a Products block on the same page — it filters the shop query, so
	 * it needs a grid to act on.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_product_filters_options() {
		return $this->pick_shortcode_options( 'wc_product_filters', array(
			'filters', 'panel_title', 'collapsible', 'box_style',
			'divider',
		) );
	}

	/**
	 * The option paths the Product Search block exposes in its inspector.
	 *
	 * Searches products specifically, unlike the Site Search element, which
	 * searches everything.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_product_search_options() {
		return $this->pick_shortcode_options( 'wc_product_search', array(
			'placeholder', 'button_text', 'button_icon', 'layout',
			'field_shape', 'size', 'button_style', 'width',
			'alignment',
		) );
	}

	/**
	 * The option paths the Add to Cart block exposes in its inspector.
	 *
	 * For a landing page selling one product, this is the element to reach for
	 * rather than the whole Product Page.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_add_to_cart_options() {
		return $this->pick_shortcode_options( 'wc_add_to_cart', array(
			'product', 'quantity', 'label', 'show_price',
			'price_position', 'style', 'size', 'shape',
			'width', 'alignment', 'hover_animation',
		) );
	}

	/**
	 * The option paths the Cart Link block exposes in its inspector.
	 *
	 * `hide_when_empty` is the setting that decides whether a header shows an empty
	 * cart at all. Hiding it is tidier; showing it tells a returning visitor their
	 * cart really is empty rather than missing.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_cart_link_options() {
		return $this->pick_shortcode_options( 'wc_cart_link', array(
			'icon', 'label', 'show_count', 'show_total',
			'hide_when_empty',
		) );
	}

	/**
	 * The option paths the Mini Cart block exposes in its inspector.
	 *
	 * Five of these options describe the EMPTY state, which is the state most
	 * visitors see first and the one least often designed. They are exposed
	 * together so it is obvious that it can be designed at all.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_mini_cart_options() {
		return $this->pick_shortcode_options( 'wc_mini_cart', array(
			'icon', 'panel_style', 'drawer_backdrop', 'drawer_backdrop_blur',
			'trigger', 'show_count', 'panel_title', 'subtotal_label',
			'checkout_text', 'footnote', 'empty_icon', 'empty_heading',
			'empty_text', 'empty_button_label', 'empty_button_url',
		) );
	}

	/**
	 * The option paths the Account Link block exposes in its inspector.
	 *
	 * What it links to depends on whether the visitor is logged in, so the canvas
	 * shows the state of whoever is editing.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_account_options() {
		return $this->pick_shortcode_options( 'wc_account', array(
			'show_label', 'trigger',
		) );
	}

	/**
	 * The option paths the Free Shipping Bar block exposes in its inspector.
	 *
	 * No options: the threshold comes from the shipping zone, and the progress from
	 * the current cart. Nothing here is the block's to decide.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_free_shipping_options() {
		return $this->pick_shortcode_options( 'wc_free_shipping', array(

		) );
	}

	/**
	 * The option paths the Cart block exposes in its inspector.
	 *
	 * A PAGE-LEVEL element. WooCommerce expects its cart at a specific page, set
	 * under WooCommerce → Settings → Advanced; placing this block elsewhere renders
	 * a cart there but does not make that page the shop's cart.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_cart_options() {
		return $this->pick_shortcode_options( 'wc_cart', array(

		) );
	}

	/**
	 * The option paths the Checkout block exposes in its inspector.
	 *
	 * A PAGE-LEVEL element, and the one where placement matters most: WooCommerce
	 * routes payment gateways and order handling through the page configured as the
	 * checkout. Putting this block on a different page produces a checkout form that
	 * looks right and is not the one the shop is configured to use.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_checkout_options() {
		return $this->pick_shortcode_options( 'wc_checkout', array(

		) );
	}

	/**
	 * The option paths the My Account block exposes in its inspector.
	 *
	 * A PAGE-LEVEL element. To a logged-out visitor it renders the login form; the
	 * canvas shows whichever state the editing user is in, which is always logged
	 * in.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_my_account_options() {
		return $this->pick_shortcode_options( 'wc_my_account', array(

		) );
	}

	/**
	 * The option paths the Order Tracking block exposes in its inspector.
	 *
	 * Renders WooCommerce's tracking form. Unlike the cart and checkout it is not
	 * tied to a configured page, so it can go wherever it makes sense.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function wc_order_tracking_options() {
		return $this->pick_shortcode_options( 'wc_order_tracking', array(

		) );
	}

	/**
	 * The option paths the Portfolio Grid block exposes in its inspector.
	 *
	 * The first block from the PORTFOLIO extension, which ships inactive. Like the
	 * Animation Engine blocks, it simply does not register when that extension is
	 * off — the availability guard in _action_register_blocks() covers every
	 * extension, not just the ones it was written for.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function portfolio_options() {
		return $this->pick_shortcode_options( 'portfolio', array(
			'categories', 'count', 'featured_only', 'orderby',
			'order', 'pagination', 'link_to', 'layout',
			'columns', 'ratio', 'hover', 'gap',
			'image_size', 'show_filters', 'show_summary', 'show_category',
			'text_color', 'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Project Details block exposes in its inspector.
	 *
	 * `project_id` is exposed on every single-project element in this set. Left
	 * empty it reads the current project, which is what a template wants; set, it
	 * pins one project, which is what a landing page wants. Both are legitimate, so
	 * neither is hidden.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function project_details_options() {
		return $this->pick_shortcode_options( 'project_details', array(
			'project_id', 'heading', 'heading_tag',
		) );
	}

	/**
	 * The option paths the Project Gallery block exposes in its inspector.
	 *
	 * The three `columns_*` options are exposed together for the same reason the
	 * carousel's per-breakpoint counts are: they are one responsive decision in
	 * three parts, and only the desktop one is visible while editing.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function project_gallery_options() {
		return $this->pick_shortcode_options( 'project_gallery', array(
			'project_id', 'columns', 'columns_tablet', 'columns_mobile',
			'gap', 'ratio', 'image_size', 'lightbox',
			'captions', 'no_results_text', 'text_color', 'bg_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Project Navigation block exposes in its inspector.
	 *
	 * One option, and it is the whole decision: whether "next" means the next
	 * project overall or the next in the same category. On a portfolio spanning
	 * unrelated kinds of work, the second is almost always what a visitor expects.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function project_nav_options() {
		return $this->pick_shortcode_options( 'project_nav', array(
			'same_category',
		) );
	}

	/**
	 * The option paths the Project Results block exposes in its inspector.
	 *
	 * Content comes from the project itself, so the block places it rather than
	 * defining it — the only choice is which project.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function project_results_options() {
		return $this->pick_shortcode_options( 'project_results', array(
			'project_id',
		) );
	}

	/**
	 * The option paths the Project Testimonial block exposes in its inspector.
	 *
	 * Content comes from the project. For a standalone set of quotes not tied to
	 * one, use the Testimonials block instead.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function project_testimonial_options() {
		return $this->pick_shortcode_options( 'project_testimonial', array(
			'project_id',
		) );
	}

	/**
	 * The option paths the Related Projects block exposes in its inspector.
	 *
	 * Relatedness is derived from the current project's taxonomy terms, so this
	 * element only means anything where a current project exists — a project
	 * template, or a single project page.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function related_projects_options() {
		return $this->pick_shortcode_options( 'related_projects', array(
			'count', 'heading', 'heading_tag',
		) );
	}

	/**
	 * The option paths the Table block exposes in its inspector.
	 *
	 * The `table` control edits cells as a list of rows rather than as a grid - a
	 * sidebar column is not a grid. Column widths, alignment and the header/footer
	 * row counts are here; MERGING cells stays in the page builder, and merges made
	 * there survive editing here untouched.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function table_options() {
		return $this->pick_shortcode_options( 'table', array(
			'table', 'table_preset', 'frame_preset', 'style_striped',
			'style_hover', 'style_bordered', 'style_condensed', 'sticky_header',
			'caption', 'caption_position', 'enable_sort', 'enable_search',
			'enable_pagination', 'pagination_length', 'enable_length_change', 'enable_info',
			'text_color', 'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Contact Form block exposes in its inspector.
	 *
	 * The last block, and the one that needed the most from the bridge:
	 *
	 * - `form` is a `form-builder`, whose item types and per-type option schemas
	 *   live in PHP classes rather than in the option array. enrich_option()
	 *   attaches them so the control has something to build a field editor from.
	 * - `mailer` is exposed but READ-ONLY: it stores site-wide mail settings in a
	 *   wp-option, so an editable field here would write them somewhere nothing
	 *   reads, and mail would silently keep using the site configuration.
	 *
	 * `id` is omitted - a `unique` option the element generates for itself.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function contact_form_options() {
		return $this->pick_shortcode_options( 'contact_form', array(
			'form', 'email_to', 'subject_message', 'submit_button_text',
			'success_message', 'failure_message', 'mailer', 'form_max_width',
			'form_align', 'field_bg', 'field_text', 'field_border',
			'field_focus', 'label_color', 'field_radius', 'field_border_width',
			'field_padding', 'button_style', 'button_size', 'button_shape',
			'button_full', 'button_align',
		) );
	}

	/**
	 * The option paths the Section block exposes in its inspector.
	 *
	 * The first CONTAINER block: it holds inner blocks rather than previewing
	 * itself with ServerSideRender, and its children reach the element as $content
	 * exactly as the page builder's children do.
	 *
	 * `background` is the first `background-pro` option in any block - five stacked
	 * layers (colour, gradient, image, video, overlay) in one value.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function section_options() {
		return $this->pick_shortcode_options( 'section', array(
			'variant', 'is_fullwidth', 'container_width', 'min_height',
			'column_halign', 'column_valign', 'reverse_columns', 'background',
			'background_pattern', 'bg_effect', 'divider_top', 'divider_bottom',
			'text_align', 'padding_top', 'padding_bottom', 'gap',
			'gap_x', 'gap_y',
		) );
	}

	/**
	 * The option paths the Column block exposes in its inspector.
	 *
	 * `col_width` and several siblings are `responsive` options: one value per
	 * breakpoint, edited through device tabs. Whether a blank device inherits the
	 * smaller one depends on the inner type declaring a blank choice - these do.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function column_options() {
		return $this->pick_shortcode_options( 'column', array(
			'content_h', 'content_direction', 'content_v', 'full_height',
			'content_gap', 'align_self', 'col_width', 'max_width',
			'col_offset', 'content_order', 'mobile_order', 'text_align',
			'bg_color', 'border_preset',
		) );
	}

	/**
	 * The option paths the Container block exposes in its inspector.
	 *
	 * Section without the dividers and effects. Reach for this when the band needs
	 * a width and a background and nothing else.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function container_options() {
		return $this->pick_shortcode_options( 'container', array(
			'is_fullwidth', 'min_height', 'column_halign', 'column_valign',
			'reverse_columns', 'background', 'background_pattern', 'padding_top',
			'padding_bottom', 'gap', 'gap_x', 'gap_y',
		) );
	}

	/**
	 * The option paths the Flexbox block exposes in its inspector.
	 *
	 * The container whose settings the canvas can show least - the children are
	 * laid out by the editor, not by the element's flex CSS. Everything here is
	 * real on the front end and invisible in the outline.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function flexbox_options() {
		return $this->pick_shortcode_options( 'flexbox', array(
			'direction', 'gap', 'justify_content', 'align_items',
			'wrap', 'reverse', 'align_content', 'width',
			'flex_grow', 'align_self', 'order', 'background',
			'border_preset', 'min_height',
		) );
	}

	/**
	 * The option paths the Bleed Section block exposes in its inspector.
	 *
	 * `bleed_image_alt` is exposed beside the image for the same reason Image Box's
	 * is: alt text written when the image is chosen is alt text that gets written.
	 *
	 * `bleed_mobile_stacking` decides which of the image and the content comes
	 * first once they stack - invisible at desktop width, and wrong by default for
	 * a section whose text introduces its image.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function bleed_section_options() {
		return $this->pick_shortcode_options( 'bleed_section', array(
			'bleed_image', 'bleed_image_alt', 'bleed_image_side', 'bleed_image_ratio',
			'bleed_image_position', 'bleed_image_lazy', 'bleed_mobile_stacking', 'bleed_min_height',
			'is_fullwidth', 'background', 'bleed_overlay_color', 'bleed_overlay_opacity',
			'bleed_vertical_align', 'bleed_content_padding',
		) );
	}

	/**
	 * The option paths the Masonry Section block exposes in its inspector.
	 *
	 * `masonry_info` is omitted: it is an `html` explanatory panel for the page
	 * builder's options modal, not a setting.
	 *
	 * Masonry positions are computed from the rendered heights of real content, so
	 * the canvas - which stacks the children normally - shows a different
	 * arrangement from the front end by nature, not by omission.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function masonry_section_options() {
		return $this->pick_shortcode_options( 'masonry_section', array(
			'gap', 'is_fullwidth', 'background', 'padding_top',
			'padding_bottom',
		) );
	}

	/**
	 * The option paths the Flip Box block exposes in its inspector.
	 *
	 * The first block to expose a `popover` option (`design_settings`). Its React
	 * control renders the inner options inline, and stores them the way PHP does:
	 * UNWRAPPED when the popover declares a single inner option, as a hash keyed by
	 * inner id when it declares several.
	 *
	 * `height` is exposed and matters more than it looks: both faces occupy the same
	 * box, so the card is as tall as its longer face. Leave it unset and a short
	 * front sits in a card sized by the back.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function flip_box_options() {
		return $this->pick_shortcode_options( 'flip_box', array(
			'front_icon', 'front_title', 'front_title_tag', 'front_text',
			'front_button_label', 'back_icon', 'back_title', 'back_title_tag',
			'back_text', 'button_label', 'button_url', 'button_target',
			'design_settings', 'flip_direction', 'trigger', 'parallax',
			'flip_speed', 'flip_easing', 'height', 'rounded',
			'box_style', 'icon_badge_preset', 'front_bg', 'front_image',
			'front_color', 'back_bg', 'back_image', 'back_color',
			'button_style', 'button_size', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the 3D Gallery block exposes in its inspector.
	 *
	 * `design_preview` is exposed in the list PHP hands over but renders NOTHING in
	 * a block sidebar - it is a value-less page-builder visual aid whose JS reads its
	 * siblings out of the options modal's DOM. The block canvas previews the real
	 * element instead. See the null control for the reasoning.
	 *
	 * `as_background` is worth knowing before switching on: it takes the scene out
	 * of the flow and behind the section's content, so the block's own height stops
	 * describing what you see.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function gallery_3d_options() {
		return $this->pick_shortcode_options( 'gallery_3d', array(
			'source', 'design_settings', 'as_background', 'box_style',
			'shadow', 'captions', 'caption_source', 'click',
		) );
	}

	/**
	 * The option paths the Post Title block exposes in its inspector.
	 *
	 * Core ships a Post Title block, and in a post it does the same job. This one
	 * exists because it renders through the theme's own type scale and colour
	 * tokens - `font_size_preset` is the theme's preset list, not an arbitrary size -
	 * so it matches the rest of an UnysonPlus page without hand-tuning.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_title_options() {
		return $this->pick_shortcode_options( 'post_title', array(
			'heading_tag', 'link_to_post', 'text_align', 'text_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Content block exposes in its inspector.
	 *
	 * `note` is omitted: it is an `html-full` explanatory panel meant for the page
	 * builder's options modal, not a setting.
	 *
	 * Placing this INSIDE a post renders that post's content within itself, which
	 * WordPress guards against but which is never what anyone means. It belongs in a
	 * Theme Builder template.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_content_options() {
		return $this->pick_shortcode_options( 'post_content', array(
			'text_align', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Date block exposes in its inspector.
	 *
	 * `date_type` chooses published or modified. Worth a deliberate answer: a
	 * "last updated" date on an article that has only ever been published once shows
	 * the same value as the publish date, and a published date on a page updated
	 * yearly misleads readers about how current it is.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_date_options() {
		return $this->pick_shortcode_options( 'post_date', array(
			'date_type', 'date_format', 'link_to_post', 'text_align',
			'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Excerpt block exposes in its inspector.
	 *
	 * Length and trimming are WordPress's own (`excerpt_length` / `excerpt_more`),
	 * not options here - which is why there are only three. An element that
	 * re-implemented them would disagree with every other excerpt on the site.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_excerpt_options() {
		return $this->pick_shortcode_options( 'post_excerpt', array(
			'text_align', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Meta Field block exposes in its inspector.
	 *
	 * `before_text` / `after_text` wrap the value, and they are the reason this
	 * element is usable at all: a bare meta value like `4` means nothing, while
	 * "Serves 4" does. They render only when the field itself has a value, so an
	 * empty field prints nothing rather than a stranded label.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_meta_options() {
		return $this->pick_shortcode_options( 'post_meta', array(
			'meta_key', 'before_text', 'after_text', 'text_align',
			'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Terms block exposes in its inspector.
	 *
	 * `taxonomy` accepts any registered taxonomy, not just category and tag - which
	 * is what makes this worth having over core's Post Terms block for custom post
	 * types.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_terms_options() {
		return $this->pick_shortcode_options( 'post_terms', array(
			'taxonomy', 'term_prefix', 'term_separator', 'link_terms',
			'text_align', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Post Author block exposes in its inspector.
	 *
	 * A byline, not a bio - see the Author Box block for the card with a
	 * description and social links.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function post_author_options() {
		return $this->pick_shortcode_options( 'post_author', array(
			'author_prefix', 'link_to_author', 'show_avatar', 'avatar_size',
			'text_align', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Site Logo block exposes in its inspector.
	 *
	 * `source` picks between the Theme Settings logo and a custom image. Using the
	 * Theme Settings one means a rebrand changes a single setting rather than every
	 * page that shows a logo.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function site_logo_options() {
		return $this->pick_shortcode_options( 'site_logo', array(
			'source', 'custom_image', 'link_home', 'max_height',
			'alignment',
		) );
	}

	/**
	 * The option paths the Navigation Menu block exposes in its inspector.
	 *
	 * `depth` caps how many levels render. A menu with deep nesting placed
	 * unrestricted into a narrow column produces submenus that open off-screen -
	 * which is invisible until someone hovers.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function nav_menu_options() {
		return $this->pick_shortcode_options( 'nav_menu', array(
			'menu_source', 'orientation', 'submenu_style', 'depth',
			'alignment',
		) );
	}

	/**
	 * The option paths the Menu Toggle block exposes in its inspector.
	 *
	 * `target` names the menu this button opens. A toggle pointing at nothing is
	 * inert with no visible symptom, which is why the field is exposed rather than
	 * hidden behind a default.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function menu_toggle_options() {
		return $this->pick_shortcode_options( 'menu_toggle', array(
			'target', 'label', 'icon_style',
		) );
	}

	/**
	 * The option paths the Widget Area block exposes in its inspector.
	 *
	 * Renders whatever widgets that sidebar currently holds, so its content is
	 * managed under Appearance rather than here.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function widget_area_options() {
		return $this->pick_shortcode_options( 'widget_area', array(
			'sidebar', 'text_color', 'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Scroll to Top block exposes in its inspector.
	 *
	 * Page chrome rather than content: it pins itself to the viewport, so it belongs
	 * ONCE per page - usually in a Theme Builder template rather than in a post. Two
	 * of these on one page produce two buttons in the same corner.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function scroll_to_top_options() {
		return $this->pick_shortcode_options( 'scroll_to_top', array(
			'show_button', 'show_progress', 'icon', 'position',
			'shape', 'show_after', 'progress_position', 'progress_height',
			'accent_color', 'icon_color', 'button_size',
		) );
	}

	/**
	 * The option paths the Call to Action block exposes in its inspector.
	 *
	 * `column_split` is the first `column-split` option in any block: it divides the
	 * text from the button. Its React control offers only the ALLOWED fractions, in
	 * lowest terms, because PHP snaps anything else to the nearest one - so a tile
	 * for `6/12` would be silently rewritten to `1/2` on save.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function call_to_action_options() {
		return $this->pick_shortcode_options( 'call_to_action', array(
			'title', 'message', 'button_label', 'button_link',
			'button_target', 'column_split', 'box_style', 'bg_color',
			'title_color', 'message_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Image + Content block exposes in its inspector.
	 *
	 * `mobile_order` is exposed deliberately. When an image-left row stacks, the
	 * image lands above the text by default - which for a row whose text introduces
	 * the image is the wrong order, and is invisible at desktop width.
	 *
	 * `content_padding` is omitted: it is a `spacing` option, and the block already
	 * declares supports.spacing for the block's own padding.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function image_content_options() {
		return $this->pick_shortcode_options( 'image_content', array(
			'image', 'content', 'image_link', 'image_link_target',
			'layout', 'column_ratio', 'vertical_align', 'content_align',
			'gap', 'mobile_order', 'breakpoint', 'stack_image_width',
			'stack_image_align', 'image_fit', 'image_ratio', 'image_radius',
			'image_shadow', 'image_style', 'content_max_width', 'content_color',
			'content_bg',
		) );
	}

	/**
	 * The option paths the Countdown block exposes in its inspector.
	 *
	 * `target` is the first `datetime-picker` option in any block, and the one that
	 * made the control worth writing carefully: PHP validates the FORMATTING, not
	 * just the date, so an ISO string would be discarded for the option default and
	 * the countdown would quietly count to nothing.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function countdown_options() {
		return $this->pick_shortcode_options( 'countdown', array(
			'target', 'show_days', 'show_hours', 'show_minutes',
			'show_seconds', 'label_days', 'label_hours', 'label_minutes',
			'label_seconds', 'on_complete', 'complete_text', 'alignment',
			'number_font', 'number_color', 'label_font', 'label_color',
			'box_preset',
		) );
	}

	/**
	 * The option paths the Code Block block exposes in its inspector.
	 *
	 * `render_as_code` is the switch that decides whether the value is DISPLAYED as
	 * code or EXECUTED as markup in the page. It is exposed because hiding it would
	 * not make it safer - but it is worth knowing which way it is set before pasting
	 * anything into the field.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function code_block_options() {
		return $this->pick_shortcode_options( 'code_block', array(
			'code', 'render_as_code', 'beautify', 'code_language',
			'text_color', 'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Posts block exposes in its inspector.
	 *
	 * The largest sidebar in the set, because the element is a query builder as well
	 * as a layout. Left in the page builder:
	 *
	 * - `meta_key`, `cat_taxonomy`, `scope_selector`-style raw keys that only make
	 *   sense next to the taxonomy they name;
	 * - `cache_output` / `cache_hours`, because a cached block previewing a stale
	 *   query in the editor is a confusing first encounter with caching;
	 * - `fallback_image_url` and `card_preview`, the latter being a page-builder-only
	 *   sample of a card the canvas already renders for real.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function posts_options() {
		return $this->pick_shortcode_options( 'posts', array(
			'use_current_query', 'post_type', 'taxonomy_filter', 'taxonomy_relation',
			'include_ids', 'exclude_ids', 'author_ids', 'date_range',
			'posts_per_page', 'offset', 'orderby', 'order',
			'exclude_current', 'sticky_handling', 'design', 'card',
			'card_rows', 'box_style', 'image_style', 'image_size',
			'image_ratio', 'card_padding', 'text_align', 'mobile_layout_override',
			'title_tag', 'cat_position', 'cat_max', 'meta_items',
			'meta_layout', 'date_format', 'excerpt_source', 'excerpt_length',
			'excerpt_suffix', 'readmore', 'readmore_text', 'pagination',
			'live_filters', 'filters_position', 'no_results_text', 'text_color',
			'bg_color', 'title_color', 'excerpt_color', 'meta_color',
			'accent_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Table of Contents block exposes in its inspector.
	 *
	 * `levels` is the first `checkboxes` option in any block - which heading levels
	 * to collect. Its React control omits unchecked keys entirely rather than sending
	 * false, because the PHP validator stores anything that is not the empty string
	 * as true.
	 *
	 * `skip_text` is left in the page builder: it is a newline-separated exclusion
	 * list, and pruning a contents list is deliberate work better done where the
	 * whole page is in view.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function toc_options() {
		return $this->pick_shortcode_options( 'toc', array(
			'title', 'levels', 'hierarchical', 'min_headings',
			'numeration', 'numeration_suffix', 'collapsible', 'collapsed_default',
			'label_show', 'label_hide', 'scope', 'scope_selector',
			'smooth_scroll', 'scroll_offset', 'scrollspy', 'nofollow',
			'noindex', 'width', 'custom_width', 'float',
			'sticky', 'sticky_offset', 'title_size', 'items_size',
			'bg_color', 'border_color', 'title_color',
		) );
	}

	/**
	 * The option paths the Button block exposes in its inspector.
	 *
	 * Small sidebar, most-placed element. It is the reason `button-style-picker` and
	 * `button-hover-animation` were worth building: both render REAL buttons in the
	 * sidebar, because the generated preset CSS is enqueued in wp-admin and the
	 * hover-effect stylesheet is linked by its control.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function button_options() {
		return $this->pick_shortcode_options( 'button', array(
			'label', 'link', 'target', 'icon',
			'icon_position', 'style', 'size', 'shape',
			'width', 'alignment', 'state', 'hover_animation',
		) );
	}

	/**
	 * The option paths the Pricing Table block exposes in its inspector.
	 *
	 * `billing_default` decides which prices a visitor sees FIRST, and it is the
	 * setting most likely to be left wrong: a yearly-default table advertises the
	 * lower monthly-equivalent figure, which is either the honest framing or the
	 * misleading one depending on what the plans actually charge.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function pricing_table_options() {
		return $this->pick_shortcode_options( 'pricing_table', array(
			'plans', 'billing_toggle', 'billing_default', 'billing_monthly_label',
			'billing_yearly_label', 'billing_note', 'design', 'columns',
			'gap', 'featured_style', 'button_preset', 'align',
			'product_schema', 'box_style', 'icon_badge_preset', 'accent_color',
			'bg_color', 'card_bg', 'title_color', 'price_color',
			'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Image Sequence block exposes in its inspector.
	 *
	 * `frames_source` is a multi-picker: a numbered URL pattern and a media-library
	 * selection need genuinely different fields.
	 *
	 * `pin_length` is exposed even though it is the option most likely to be set
	 * wrongly - it decides how much page scroll the sequence consumes, and a value
	 * that feels right on a desktop can make a phone feel stuck. It belongs in the
	 * sidebar precisely because it must be tuned against the real page.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function image_sequence_options() {
		return $this->pick_shortcode_options( 'image_sequence', array(
			'frames_source', 'mode', 'pin_length', 'direction',
			'fit', 'height', 'bg',
		) );
	}

	/**
	 * The option paths the SVG Morph block exposes in its inspector.
	 *
	 * `shapes_list` is a repeater, and ORDER is the animation: the shapes morph in
	 * the order they are listed, so reordering the list rewrites the sequence.
	 *
	 * Shapes need compatible path data to morph cleanly; mismatched point counts
	 * produce a lurch rather than a morph. That is a property of the paths, not a
	 * setting, which is why there is no option for it.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function svg_morph_options() {
		return $this->pick_shortcode_options( 'svg_morph', array(
			'shapes_list', 'loopback', 'render_mode', 'trigger',
			'easing', 'fill_color', 'stroke_color', 'stroke_width',
			'max_width', 'align',
		) );
	}

	/**
	 * The option paths the WebGL Object block exposes in its inspector.
	 *
	 * `quality` and `dpr_cap` are exposed rather than treated as expert settings.
	 * This element runs a continuous render loop on the visitor's GPU, and on a
	 * phone that is the difference between a striking hero and a hot battery - the
	 * two options that govern the cost belong where the effect is chosen.
	 *
	 * `poster` matters more than it looks: it is what shows before WebGL
	 * initialises, on devices that refuse it, and in the editor canvas.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function webgl_object_options() {
		return $this->pick_shortcode_options( 'webgl_object', array(
			'style_preset', 'placement', 'scale', 'color_a',
			'color_b', 'background', 'bg_color', 'auto_rotate',
			'noise_amount', 'noise_speed', 'scroll_link', 'pointer_follow',
			'pointer_strength', 'parallax', 'quality', 'dpr_cap',
			'poster',
		) );
	}

	/**
	 * The option paths the Global Section block exposes in its inspector.
	 *
	 * One option, and it is the whole element: which saved section to place. The
	 * content lives in the snippet, so editing it there updates every page that
	 * places it - which is the difference between this and copying a block.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function global_section_options() {
		return $this->pick_shortcode_options( 'global_section', array(
			'snippet_id',
		) );
	}

	/**
	 * The option paths the Snippet block exposes in its inspector.
	 *
	 * A snippet can contain arbitrary saved code, so what this block renders is
	 * whatever that snippet renders - in the editor as well as on the front end.
	 * Snippets are authored by administrators, which is the boundary that makes that
	 * acceptable; it is not a place to paste code from elsewhere.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function snippet_options() {
		return $this->pick_shortcode_options( 'snippet', array(
			'id',
		) );
	}

	/**
	 * The option paths the Scroll Indicator block exposes in its inspector.
	 *
	 * `target` is a selector or anchor further down the SAME page. It is exposed
	 * because an indicator pointing at nothing still animates invitingly and then
	 * does nothing when clicked - a failure with no visible symptom until someone
	 * tries it.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function scroll_indicator_options() {
		return $this->pick_shortcode_options( 'scroll_indicator', array(
			'text', 'icon', 'target', 'layout',
			'animation', 'text_color', 'icon_color', 'icon_size',
		) );
	}

	/**
	 * The option paths the Site Search block exposes in its inspector.
	 *
	 * Two options, because a search field has two decisions: how it looks and what
	 * it says before anyone types. The results page is WordPress's own.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function site_search_options() {
		return $this->pick_shortcode_options( 'site_search', array(
			'style', 'placeholder',
		) );
	}

	/**
	 * The option paths the Lottie block exposes in its inspector.
	 *
	 * `source` picks between a URL and an uploaded file, and BOTH fields are exposed
	 * rather than hidden by that choice - an animation pointed at the field the
	 * source is not reading is a blank frame with no explanation.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function lottie_options() {
		return $this->pick_shortcode_options( 'lottie', array(
			'source', 'lottie_url', 'lottie_file', 'trigger',
			'loop', 'reverse_hover', 'speed', 'direction',
			'max_width', 'alignment',
		) );
	}

	/**
	 * The option paths the SVG Draw block exposes in its inspector.
	 *
	 * Part of the Animation Engine, which ships INACTIVE. The block simply does not
	 * register when that extension is off - see _action_register_blocks() - rather
	 * than appearing in the inserter with an empty sidebar.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function svg_draw_options() {
		return $this->pick_shortcode_options( 'svg_draw', array(
			'svg', 'trigger', 'duration', 'stagger',
			'direction', 'loop', 'stroke_width', 'stroke_color',
			'fill_after', 'fill_color', 'max_width', 'align',
		) );
	}

	/**
	 * The option paths the 3D Model block exposes in its inspector.
	 *
	 * The element carries 37 content-tab options; this exposes the eighteen that
	 * decide what the model IS and how it behaves. Left in the page builder:
	 *
	 * - the camera LIMITS (`min_fov`/`max_fov`, `min_orbit`/`max_orbit`, `disable_pan`)
	 *   - a badly chosen pair can leave a model that cannot be rotated to its front,
	 *   and the failure is only visible by trying every angle;
	 * - the fine tone-mapping and skybox controls, which are rendering-pipeline
	 *   settings rather than content;
	 * - `variants_show` / `variant_default`, which depend on what the model file
	 *   itself defines - a name typed without the file open is a guess.
	 *
	 * `alt` is exposed. A 3D viewer is opaque to a screen reader without it.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function model_viewer_options() {
		return $this->pick_shortcode_options( 'model_viewer', array(
			'model_url', 'model_file', 'alt', 'poster',
			'height', 'camera_controls', 'disable_zoom', 'auto_rotate',
			'rotation_speed', 'auto_rotate_delay', 'environment', 'exposure',
			'shadow_intensity', 'animation_autoplay', 'animation_name', 'ar',
			'ar_placement', 'hotspots',
		) );
	}

	/**
	 * The option paths the Avatar block exposes in its inspector.
	 *
	 * `mode_settings` is a multi-picker choosing what the avatar IS - an uploaded
	 * image, a WordPress user's, initials, or an icon - and revealing the fields
	 * each needs. The colour options below it apply only to some of those modes,
	 * which is why they are grouped after rather than inside it.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function avatar_options() {
		return $this->pick_shortcode_options( 'avatar', array(
			'mode_settings', 'design', 'shape', 'size',
			'show_status', 'show_label', 'initials_color_mode', 'ring_color',
			'initials_bg', 'initials_color', 'label_color', 'counter_bg',
			'counter_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Image block exposes in its inspector.
	 *
	 * Core has an Image block, and for a plain image it is the better choice. This
	 * one exists for `image_style` - the theme's Image Style presets - and for
	 * `fetchpriority`, which is the setting that makes a hero image load first.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function media_image_options() {
		return $this->pick_shortcode_options( 'media_image', array(
			'image', 'width', 'height', 'fetchpriority',
			'link', 'target', 'image_style', 'bg_color',
		) );
	}

	/**
	 * The option paths the Video block exposes in its inspector.
	 *
	 * `source_type` is a multi-picker: self-hosted files and embedded providers need
	 * genuinely different fields, and only the chosen branch is stored.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function media_video_options() {
		return $this->pick_shortcode_options( 'media_video', array(
			'source_type', 'width', 'ratio', 'bg_color',
		) );
	}

	/**
	 * The option paths the Text Block block exposes in its inspector.
	 *
	 * The one block where the shared-renderer trade-off is felt directly: the text
	 * is edited in the SIDEBAR, not in the canvas, because the canvas preview is
	 * server-rendered markup rather than an editable surface.
	 *
	 * That is a real cost, and the honest answer is that core's Paragraph block is
	 * better for ordinary prose. This element earns its place when the typographic
	 * options are the point.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function text_block_options() {
		return $this->pick_shortcode_options( 'text_block', array(
			'text', 'text_align', 'max_width', 'columns',
			'balance', 'line_height', 'para_spacing', 'lead',
			'link_underline', 'dropcap', 'text_color', 'link_color',
			'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Featured Image block exposes in its inspector.
	 *
	 * A dynamic element: it renders the CURRENT post's featured image, so it is most
	 * at home in a Theme Builder template. In a post it works too, and previews that
	 * post's own image - which on a new draft is nothing yet.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function featured_image_options() {
		return $this->pick_shortcode_options( 'featured_image', array(
			'image_size', 'link_to', 'text_align', 'image_style',
		) );
	}

	/**
	 * The option paths the Audio Player block exposes in its inspector.
	 *
	 * `autoplay` is exposed but is worth a thought before using: browsers block
	 * unmuted autoplay, and audio that starts by itself is the reason they do. It is
	 * here because the option exists and hiding it would not stop anyone.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function audio_player_options() {
		return $this->pick_shortcode_options( 'audio_player', array(
			'tracks', 'design', 'autoplay', 'loop',
			'show_volume', 'show_download', 'rounded', 'accent_color',
			'bg_color', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Author Box block exposes in its inspector.
	 *
	 * `source` decides whether the card reads from a WordPress user or from the
	 * fields typed below it. Both sets are exposed, because a card that pulled from a
	 * user would otherwise show fields that silently do nothing - and the reverse.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function author_box_options() {
		return $this->pick_shortcode_options( 'author_box', array(
			'source', 'user_id', 'name', 'role',
			'bio', 'avatar', 'socials', 'design',
			'avatar_shape', 'avatar_size', 'show_posts', 'accent_color',
			'card_bg', 'name_color', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Calendar block exposes in its inspector.
	 *
	 * The rendered month is the CURRENT month, computed at render time - so this
	 * element, like Business Info, produces different output on different days
	 * without anyone editing it.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function calendar_options() {
		return $this->pick_shortcode_options( 'calendar', array(
			'events', 'design', 'start_week', 'show_list',
			'list_limit', 'accent_color', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Image Hotspots block exposes in its inspector.
	 *
	 * Pin POSITIONS are per-hotspot coordinates inside the repeater, not something
	 * dragged on the preview - dragging in the canvas moves the block. Placing pins
	 * by eye is one of the things the page builder is still better at.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function image_hotspots_options() {
		return $this->pick_shortcode_options( 'image_hotspots', array(
			'image', 'hotspots', 'design', 'trigger',
			'pin_size', 'rounded', 'pin_color', 'pop_bg',
			'pop_color', 'accent_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Map block exposes in its inspector.
	 *
	 * Both of this element's main options are multi-pickers: `data_provider` (where
	 * the markers come from) and `map_engine` (which service renders them, and the
	 * key it needs). Exposing the engine means the API key is visible where the map
	 * is configured, rather than being a separate expedition when the map is blank.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function map_options() {
		return $this->pick_shortcode_options( 'map', array(
			'data_provider', 'map_engine', 'map_height', 'disable_scrolling',
			'bg_color',
		) );
	}

	/**
	 * The option paths the Image Box block exposes in its inspector.
	 *
	 * `image_alt` is exposed alongside the image rather than left in the page
	 * builder. Alt text written at the moment the image is chosen is alt text that
	 * gets written; a field one surface away is a field that stays empty.
	 *
	 * `sc_design_panel` is omitted - it is an `html-full` sample rendered inside the
	 * page builder's options panel, and the block already previews the real element
	 * in the canvas.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function image_box_options() {
		return $this->pick_shortcode_options( 'image_box', array(
			'image', 'image_alt', 'subtitle', 'title',
			'title_tag', 'text', 'icon', 'button_style',
			'button_label', 'design_settings', 'image_ratio', 'content_align',
			'image_size', 'hover_effect', 'transition_speed', 'link_behavior',
			'link_url', 'link_target', 'box_style', 'image_style',
			'icon_badge_preset', 'bg_color', 'title_color', 'subtitle_color',
			'content_color', 'icon_color', 'accent_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Gallery block exposes in its inspector.
	 *
	 * Three multi-pickers in one sidebar - `source`, `design_settings` and `click` -
	 * which is what this element is: where the images come from, how they are laid
	 * out, and what happens when one is clicked. Each reveals a different set of
	 * fields, and only the chosen branch is stored.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function gallery_options() {
		return $this->pick_shortcode_options( 'gallery', array(
			'source', 'design_settings', 'container_type', 'click',
			'captions', 'caption_source', 'hover_zoom', 'box_style',
			'image_style', 'text_color', 'bg_color', 'caption_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Team Member block exposes in its inspector.
	 *
	 * `card_rows` decides which rows appear on the card and in what order, so it is
	 * a repeater rather than a set of switches - the ORDER is part of the answer.
	 *
	 * `card_preview` is omitted for the same reason it is on Testimonials: the block
	 * already previews the real card.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function team_member_options() {
		return $this->pick_shortcode_options( 'team_member', array(
			'image', 'name', 'job', 'desc',
			'card_rows', 'box_style', 'image_style', 'text_color',
			'bg_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Progress block exposes in its inspector.
	 *
	 * `layout` is a multi-picker whose branches are genuinely different shapes - a
	 * horizontal bar and a circular meter do not share settings - which is why the
	 * fields beneath it change so much when it is switched.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function progress_options() {
		return $this->pick_shortcode_options( 'progress', array(
			'layout', 'bars', 'height', 'value_position',
			'rounded', 'striped', 'show_value', 'animate',
			'count_up', 'gap', 'fill_color', 'fill_color_2',
			'track_color', 'label_color',
		) );
	}

	/**
	 * The option paths the Business Info block exposes in its inspector.
	 *
	 * `show_status` computes Open now / Closed from the CURRENT time against the
	 * hours repeater. It is the one option in the library whose rendered output
	 * changes without anyone editing anything, which is worth knowing before
	 * wondering why two screenshots of the same page disagree.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function business_info_options() {
		return $this->pick_shortcode_options( 'business_info', array(
			'biz_name', 'hours', 'show_status', 'time_format',
			'address', 'phone', 'email', 'website',
			'map_link', 'design', 'highlight_today', 'accent_color',
			'card_bg', 'text_color',
		) );
	}

	/**
	 * The option paths the Carousel block exposes in its inspector.
	 *
	 * The three `per_page_*` options are exposed together for the same reason the
	 * marquee's speed and direction are: they are one responsive decision in three
	 * parts, and a carousel that shows four slides on a phone because only the
	 * desktop value was set is the failure they exist to prevent.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function carousel_options() {
		return $this->pick_shortcode_options( 'carousel', array(
			'slides', 'per_page', 'per_page_tablet', 'per_page_mobile',
			'gap', 'height', 'arrows', 'pagination',
			'autoplay', 'interval', 'speed', 'pause_hover',
			'loop', 'drag', 'effect', 'overlay',
			'overlay_opacity', 'heading_color', 'text_color',
		) );
	}

	/**
	 * The option paths the Feature List block exposes in its inspector.
	 *
	 * The first block to expose a `unit-input` (`marker_size`), which stores a
	 * { value, unit } pair where the number half is a STRING - so an unset size
	 * stays distinguishable from a zero one.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function feature_list_options() {
		return $this->pick_shortcode_options( 'feature_list', array(
			'items', 'design', 'orientation', 'icon_position',
			'icon_style', 'columns', 'dividers', 'zebra',
			'spacing_size', 'box_style', 'icon_badge_preset', 'marker_color',
			'marker_size', 'text_color', 'sub_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Comparison Table block exposes in its inspector.
	 *
	 * The only block with TWO repeaters, and the order they appear in is the order
	 * you have to fill them: `rows` are defined per column, so a row added before
	 * its columns exist has nowhere to put its values.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function comparison_table_options() {
		return $this->pick_shortcode_options( 'comparison_table', array(
			'columns', 'rows', 'style', 'highlight_featured',
			'sticky_header', 'center_cells', 'product_schema', 'accent_color',
			'header_bg', 'header_text', 'text_color', 'border_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Icon block exposes in its inspector.
	 *
	 * `aria_label` is exposed rather than left in the page builder. An icon with no
	 * text label is invisible to a screen reader, and the field that fixes that
	 * should not be one surface further away than the field that creates the
	 * problem.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function icon_options() {
		return $this->pick_shortcode_options( 'icon', array(
			'icon', 'title', 'aria_label', 'icon_size',
			'icon_badge_preset', 'icon_color', 'title_color', 'bg_color',
		) );
	}

	/**
	 * The option paths the Accordion block exposes in its inspector.
	 *
	 * The first block to expose BOTH structural option types: `tabs` is a repeater
	 * and `numbering` is a multi-picker. Between them they are what most of the
	 * remaining element library is made of.
	 *
	 * `icon_closed_image` / `icon_open_image` are left in the page builder. They
	 * only apply to one `icon_style`, and a pair of custom icon uploads that do
	 * nothing until a third setting is changed is a poor thing to meet in a
	 * sidebar.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function accordion_options() {
		return $this->pick_shortcode_options( 'accordion', array(
			'tabs', 'title_tag', 'icon_style', 'icon_position',
			'icon_closed_text', 'icon_open_text', 'numbering', 'numbering_start',
			'item_spacing', 'title_alignment', 'initially_open', 'collapsible',
			'multiple_open', 'hash_linking', 'show_expand_collapse_all', 'faq_schema',
			'accordion_style', 'corner_radius', 'elevation', 'active_accent',
			'title_hover', 'tab_title_color', 'title_bg_color', 'tab_content_color',
			'content_bg_color', 'icon_closed_color', 'icon_open_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Testimonials block exposes in its inspector.
	 *
	 * `card_preview` is omitted: it is an `html-full` option that draws a live
	 * sample of the card inside the page builder's options panel. The block already
	 * has a preview of the real element in the canvas, so a second, smaller
	 * approximation of it in the sidebar would only be one more thing that can
	 * disagree.
	 *
	 * `reviews_schema` IS exposed, unlike Star Rating's - here the content really
	 * is reviews, which is the condition that makes the markup honest.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function testimonials_options() {
		return $this->pick_shortcode_options( 'testimonials', array(
			'testimonials', 'design_settings', 'card_rows', 'box_style',
			'rating_symbol', 'rating_fill_color', 'rating_empty_color', 'rating_size',
			'container_type', 'text_align', 'avatar_shape', 'avatar_size',
			'reviews_schema', 'text_color', 'bg_color', 'quote_color',
			'author_name_color', 'author_job_color', 'site_link_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Social Icons block exposes in its inspector.
	 *
	 * Almost the whole element is one option: `source` is a multi-picker that
	 * chooses between the profiles configured in Theme Settings and a custom list,
	 * and reveals a different set of fields for each. It is the clearest example of
	 * why the multi-picker control was worth building before more blocks.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function social_icons_options() {
		return $this->pick_shortcode_options( 'social_icons', array(
			'source', 'size', 'icon_badge_preset',
		) );
	}

	/**
	 * The option paths the Divider block exposes in its inspector.
	 *
	 * `margin_top` / `margin_bottom` are exposed even though the block declares
	 * `supports.spacing`, and that is a deliberate exception to the rule the other
	 * blocks follow. For a divider, the space around it IS the element - it is what
	 * a divider is for - so leaving it to a panel elsewhere in the sidebar would
	 * hide the setting people came to change.
	 *
	 * The element's `spacing` option is still omitted; core's Dimensions panel owns
	 * the block's outer margin, and these two set the rule's own offsets.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function divider_options() {
		return $this->pick_shortcode_options( 'divider', array(
			'style', 'width', 'margin_top', 'margin_bottom',
			'line_color', 'icon_color', 'divider_text_color', 'bg_color',
		) );
	}

	/**
	 * The option paths the Tabs block exposes in its inspector.
	 *
	 * The first block whose main content is a REPEATER. `tabs` is an
	 * addable-popup, so the panels are edited as an expandable list in the sidebar
	 * rather than in a modal - see the addable-popup React control for why the two
	 * renderers differ in presentation while storing the identical value.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function tabs_options() {
		return $this->pick_shortcode_options( 'tabs', array(
			'tabs', 'design', 'tab_width', 'alignment',
			'orientation', 'layout', 'media_side', 'activate_on',
			'activation', 'mobile', 'autoplay', 'autoplay_interval',
			'fade', 'deep_link', 'remember', 'text_color',
			'bg_color', 'tab_title_color', 'tab_content_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Steps block exposes in its inspector.
	 *
	 * Step numbers are derived from list position, so reordering the repeater
	 * renumbers the element. Nothing stores a number that could disagree with
	 * where the step actually sits.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function steps_options() {
		return $this->pick_shortcode_options( 'steps', array(
			'steps', 'design', 'marker', 'marker_shape',
			'connector', 'title_tag', 'accent_color', 'icon_badge_preset',
			'marker_text_color', 'title_color', 'text_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Timeline block exposes in its inspector.
	 *
	 * `howto_schema` emits HowTo structured data and IS exposed here, unlike Star
	 * Rating's review schema. The distinction is who gets hurt by a wrong answer:
	 * review markup on something that is not a review invites a search penalty,
	 * while HowTo markup on a timeline that is not instructions is simply ignored.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function timeline_options() {
		return $this->pick_shortcode_options( 'timeline', array(
			'items', 'design', 'marker', 'card_style',
			'howto_schema', 'accent_color', 'icon_badge_preset', 'line_color',
			'card_bg', 'date_color', 'title_color', 'text_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Logo Grid block exposes in its inspector.
	 *
	 * `speed` and `direction` only apply with `autoplay` on - they configure the
	 * marquee, and a static grid has neither. Both are exposed anyway, because the
	 * three settings are one decision and splitting them across two surfaces is how
	 * a half-configured marquee happens.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function logo_grid_options() {
		return $this->pick_shortcode_options( 'logo_grid', array(
			'logos', 'design', 'columns', 'gap',
			'logo_height', 'grayscale', 'show_labels', 'autoplay',
			'speed', 'direction', 'text_color', 'box_bg',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Notification block exposes in its inspector.
	 *
	 * `spacing` is omitted, as it is for every block here: the block declares
	 * `supports.spacing`, so margin and padding belong to core's own controls.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function notification_options() {
		return $this->pick_shortcode_options( 'notification', array(
			'message', 'label_text', 'type', 'border_style',
			'icon', 'layout', 'dismissible', 'auto_dismiss',
			'display_mode', 'persist_dismiss', 'text_color', 'bg_color',
			'label_color', 'message_color', 'icon_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Highlight Text block exposes in its inspector.
	 *
	 * `spacing` is omitted, as it is for every block here: the block declares
	 * `supports.spacing`, so margin and padding belong to core's own controls.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function highlight_text_options() {
		return $this->pick_shortcode_options( 'highlight_text', array(
			'prefix', 'text', 'suffix', 'tag',
			'fx', 'align', 'text_color', 'accent_color',
			'accent2_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Tooltip block exposes in its inspector.
	 *
	 * `spacing` is omitted, as it is for every block here: the block declares
	 * `supports.spacing`, so margin and padding belong to core's own controls.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function tooltip_options() {
		return $this->pick_shortcode_options( 'tooltip', array(
			'trigger_type', 'trigger_text', 'trigger_icon', 'tip_title',
			'tip_content', 'design', 'position', 'event',
			'arrow', 'max_width', 'tip_bg', 'tip_color',
			'accent_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Social Share block exposes in its inspector.
	 *
	 * `spacing` is omitted, as it is for every block here: the block declares
	 * `supports.spacing`, so margin and padding belong to core's own controls.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function social_share_options() {
		return $this->pick_shortcode_options( 'social_share', array(
			'networks', 'share_source', 'custom_url', 'share_text',
			'design', 'shape', 'size', 'show_label',
			'layout', 'align', 'custom_color', 'icon_color',
			'font_size_preset',
		) );
	}

	/**
	 * The option paths the Modal Popup block exposes in its inspector.
	 *
	 * `open_on_load` and `open_delay` are exposed together and never separately:
	 * a delay with nothing to trigger does nothing, and auto-open with no delay
	 * fires the instant the page paints. Splitting the pair across two surfaces
	 * would make a half-configured popup the easy mistake.
	 *
	 * `spacing` is omitted — the block declares `supports.spacing`, so core's own
	 * controls own margin and padding here.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function modal_popup_options() {
		return $this->pick_shortcode_options( 'modal_popup', array(
			'trigger_type', 'trigger_label', 'trigger_icon', 'trigger_image',
			'modal_title', 'modal_content',
			'design', 'size', 'open_animation',
			'open_on_load', 'open_delay', 'close_overlay',
			'accent_color', 'overlay_color', 'modal_bg', 'modal_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Animated Heading block exposes in its inspector.
	 *
	 * `spacing` is left out on purpose: the block already declares
	 * `supports.spacing`, so margin and padding are edited by core's own controls
	 * at the top of the sidebar. Exposing the element's spacing option too would
	 * put two controls for one thing in one sidebar, writing to different places.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function animated_heading_options() {
		return $this->pick_shortcode_options( 'animated_heading', array(
			'before_text', 'words', 'after_text', 'tag',
			'anim', 'speed', 'highlight', 'loop', 'pause_hover', 'randomize',
			'caret_show', 'caret_style', 'caret_color',
			'align', 'text_color', 'accent_color', 'font_size_preset',
		) );
	}

	/**
	 * The option paths the Newsletter block exposes in its inspector.
	 *
	 * The only block whose element posts to a PUBLIC endpoint, so two of its
	 * options are worth noting rather than treating as ordinary fields:
	 *
	 * - `consent_text` is exposed deliberately. An email capture form without a
	 *   consent line is a compliance problem in several jurisdictions, and hiding
	 *   the field would make the safe thing the harder thing.
	 * - `list_id` is exposed because an integration keyed to the wrong list fails
	 *   silently — subscribers go somewhere nobody reads.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function newsletter_options() {
		return $this->pick_shortcode_options( 'newsletter', array(
			'title', 'description',
			'show_name', 'name_placeholder', 'email_placeholder', 'button_label',
			'consent_text', 'success_message', 'error_message', 'list_id',
			'design', 'align', 'rounded',
			'accent_color', 'field_bg', 'bg_color', 'text_color',
		) );
	}

	/**
	 * The option paths the Blockquote block exposes in its inspector.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function blockquote_options() {
		return $this->pick_shortcode_options( 'blockquote', array(
			'quote', 'author', 'role', 'source_url',
			'design', 'align', 'show_mark', 'box_style', 'max_width',
			'quote_color', 'author_color', 'accent_color', 'bg_color',
		) );
	}

	/**
	 * The option paths the Badge block exposes in its inspector.
	 *
	 * The element carries 29 content-tab options; this exposes the dozen that
	 * decide what the badge IS. Three groups are deliberately left in the page
	 * builder:
	 *
	 * - the `rel_*` switches and `link_target`, which are SEO/link plumbing rather
	 *   than design, and are easy to set wrongly without seeing the whole page;
	 * - the `schema_*` fields, which emit structured data — the same reasoning as
	 *   Star Rating's review schema: worth a deliberate decision, not a sidebar toggle;
	 * - `dismissible` / `dismiss_id`, because a dismissal that persists per visitor
	 *   needs an id chosen with care, and a half-set pair silently does nothing.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function badge_options() {
		return $this->pick_shortcode_options( 'badge', array(
			'tag_text', 'message', 'link',
			'leading', 'leading_icon', 'trailing_icon',
			'style', 'shape', 'size', 'align', 'tag_style', 'hover',
			'pill_color', 'text_color', 'tag_color',
			'aria_label',
		) );
	}

	/**
	 * The option paths the Tag List block exposes in its inspector.
	 *
	 * Every content-tab option is exposed — the element is small enough that
	 * curating it would only hide things for no gain.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function tag_list_options() {
		return $this->pick_shortcode_options( 'tag_list', array(
			'items', 'design', 'shape', 'size', 'align', 'gap', 'marker', 'hover', 'tag_color',
		) );
	}

	/**
	 * The option paths the Text Expander block exposes in its inspector.
	 *
	 * `native_details` is deliberately omitted. It swaps the whole element to a
	 * native <details> / <summary> pair, which changes the markup, the styling
	 * hooks and the keyboard behaviour all at once — a structural choice worth
	 * making in the page builder where the result is visible next to everything
	 * else, not a switch to flip in a narrow sidebar.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function text_expander_options() {
		return $this->pick_shortcode_options( 'text_expander', array(
			'visible_content', 'hidden_content',
			'btn_show', 'btn_hide', 'toggle_icon',
			'show_btn_position', 'hide_btn_position',
			'count_mode', 'show_ellipsis', 'merge_boundary',
			'click_anywhere', 'initially_open',
			'visible_color', 'hidden_color', 'btn_color',
		) );
	}

	/**
	 * The option paths the Special Heading block exposes in its inspector.
	 *
	 * The element carries 28 content-tab options — far more than a sidebar column
	 * should show. What is exposed here is the heading itself (overline, title,
	 * subtitle), the few structural choices that change its shape, and the colours.
	 *
	 * The per-part alignment pickers (`overline_align`, `title_align`,
	 * `subtitle_align`) are deliberately left out: `alignment` already sets all
	 * three, and offering four alignment controls in a narrow column invites the
	 * confusion of setting one and wondering why another disagrees. The per-part
	 * overrides remain in the page builder for the layouts that need them.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function special_heading_options() {
		return $this->pick_shortcode_options( 'special_heading', array(
			'overline', 'title', 'subtitle', 'heading',
			'alignment', 'display_size', 'subtitle_size', 'element_spacing',
			'overline_uppercase', 'overline_marker',
			'icon', 'overline_icon',
			'bg_color', 'overline_color', 'title_color', 'subtitle_color',
			'block_max_width',
		) );
	}

	/**
	 * The option paths the Icon Box block exposes in its inspector.
	 *
	 * The most-used marketing pattern in the set, and the first block whose content
	 * includes rich text — `content` is a `wp-editor`, which edits its markup
	 * directly rather than as a WYSIWYG (see that option type's docs for why).
	 *
	 * `custom_icon` is deliberately omitted: it is a `hidden` field the icon picker
	 * maintains as a side effect, not something a user sets.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function icon_box_options() {
		return $this->pick_shortcode_options( 'icon_box', array(
			'icon', 'title', 'title_tag', 'content',
			'style', 'icon_align', 'icon_size',
			'box_style', 'bg_color', 'icon_color', 'title_color', 'content_color',
			'box_link', 'link_target',
		) );
	}

	/**
	 * The option paths the Video Popup block exposes in its inspector.
	 *
	 * `poster` and `video_url` come first because they are the two the element
	 * cannot render without — the shortcode itself says as much when both are
	 * empty, and that message is what the block preview shows until they are set.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function video_popup_options() {
		return $this->pick_shortcode_options( 'video_popup', array(
			'poster', 'video_url', 'play_label', 'caption',
			'design', 'ratio', 'play_size', 'rounded',
			'overlay', 'hover_zoom',
			'accent_color', 'icon_color', 'overlay_color', 'label_color',
		) );
	}

	/**
	 * The option paths the Star Rating block exposes in its inspector.
	 *
	 * This element is a good fit for a block sidebar because every option in its
	 * content tabs already has a React control — including the three colour fields,
	 * which use the shared compact predefined-colours picker.
	 *
	 * Schema entries are read from the shortcode itself rather than restated here,
	 * so the choices (designs, sizes, presets) cannot drift from the ones the page
	 * builder shows. Only the SELECTION of which options to expose lives in this file.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function star_rating_options() {
		$expose = array(
			'rating', 'max', 'label', 'show_value', 'count_text',
			'design', 'size', 'align',
			'fill_color', 'empty_color', 'text_color',
		);

		return $this->pick_shortcode_options( 'star_rating', $expose );
	}

	/**
	 * Pull a curated set of leaf options out of a shortcode's own schema.
	 *
	 * Blocks expose a flat subset, and restating each entry by hand — as the
	 * Before/After and Counter maps do — means every choice list is duplicated and
	 * free to drift from the page builder's. Reading them from the shortcode keeps
	 * one source of truth: add a design to the shortcode and the block offers it.
	 *
	 * Leaves are matched by id anywhere in the schema, because `group` / `tab`
	 * containers do not namespace their children — the id IS the fw_akg path.
	 * Requested ids that no longer exist are skipped rather than emitted as broken
	 * entries, so renaming an option degrades to "missing from the sidebar" instead
	 * of a control rendering against nothing.
	 *
	 * @param string $tag    Shortcode tag.
	 * @param array  $expose Option ids to expose, in the order they should appear.
	 * @return array Map of option id => schema entry.
	 */
	private function pick_shortcode_options( $tag, array $expose ) {
		$shortcodes = fw_ext( 'shortcodes' );

		if ( ! $shortcodes ) {
			return array();
		}

		$shortcode = $shortcodes->get_shortcode( $tag );

		if ( ! $shortcode ) {
			return array();
		}

		$found = array();

		$walk = function ( $options ) use ( &$walk, &$found ) {
			foreach ( (array) $options as $id => $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}

				if ( isset( $option['options'] ) && is_array( $option['options'] ) ) {
					$walk( $option['options'] );
					continue;
				}

				if ( ! empty( $option['type'] ) && ! isset( $found[ $id ] ) ) {
					$found[ $id ] = $option;
				}
			}
		};

		$walk( $shortcode->get_options() );

		$out = array();

		foreach ( $expose as $id ) {
			if ( isset( $found[ $id ] ) ) {
				$out[ $id ] = $this->enrich_option( $found[ $id ] );
			}
		}

		return $out;
	}

	/**
	 * Add anything a React control needs that the raw schema does not carry.
	 *
	 * Most option types describe themselves completely: a select declares its
	 * choices, a slider its range. `form-builder` does not — its editable content
	 * is a list of ITEMS whose available types and per-type option schemas live in
	 * PHP classes, not in the option array.
	 *
	 * So the item types are resolved here and attached as `item_types`, keyed by
	 * type id, each carrying its title and its flattened option schema. Without
	 * this a form-builder control would have nothing to build a field editor from.
	 *
	 * @param array $option An option schema entry.
	 * @return array The entry, possibly enriched.
	 */
	private function enrich_option( array $option ) {
		if ( empty( $option['type'] ) || 'form-builder' !== $option['type'] ) {
			return $option;
		}

		$builder = fw()->backend->option_type( 'form-builder' );

		if ( ! $builder ) {
			return $option;
		}

		// get_item_types() is protected on FW_Option_Type_Builder; the item classes'
		// get_options() was widened to public for exactly this, so only the one
		// accessor needs opening.
		try {
			$method = new ReflectionMethod( $builder, 'get_item_types' );
			$method->setAccessible( true );
			$types = (array) $method->invoke( $builder );
		} catch ( Exception $e ) {
			return $option;
		}

		$option['item_types'] = array();

		foreach ( $types as $id => $item ) {
			if ( ! method_exists( $item, 'get_options' ) ) {
				continue;
			}

			$flat = fw_extract_only_options( $item->get_options() );

			// A visual-only item (a heading, the honeypot) has no submitted value;
			// it is still placeable, so it is offered with whatever options it has.
			$option['item_types'][ $id ] = array(
				'title'   => method_exists( $item, 'get_item_localization' ) && isset( $item->get_item_localization()['l10n']['item_title'] )
					? $item->get_item_localization()['l10n']['item_title']
					: ucfirst( str_replace( '-', ' ', $id ) ),
				'options' => $flat,
			);
		}

		return $option;
	}

	/**
	 * The option paths the Counter block exposes in its inspector.
	 *
	 * A curated FLAT map of `fw_akg` path => option schema entry, not the whole
	 * shortcode `options.php`. Two reasons that matters:
	 *
	 * - The shortcode's own options are nested in `tab` / `group` containers,
	 *   which are structural and have no React renderer. Picking leaves avoids
	 *   the question entirely. (Groups do not namespace values, so a leaf's path
	 *   is simply its id.)
	 * - A block inspector is a narrow column. Exposing all ~25 counter options
	 *   would be worse than exposing the dozen that decide what the element is.
	 *
	 * Anything omitted still round-trips untouched inside `upOptions`, because the
	 * block never rewrites the attribute wholesale — so a counter styled in the
	 * page builder and then opened as a block keeps every value this does not show.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function counter_options() {
		return array(
			'number' => array(
				'type'  => 'text',
				'label' => __( 'Number', 'fw' ),
				'value' => '100',
				'desc'  => __( 'The value to count up to — e.g. 45280, 96, 4.2.', 'fw' ),
			),
			'start' => array(
				'type'  => 'text',
				'label' => __( 'Start From', 'fw' ),
				'value' => '0',
			),
			'prefix' => array(
				'type'  => 'text',
				'label' => __( 'Prefix', 'fw' ),
				'value' => '',
				'desc'  => __( 'Shown before the number, e.g. $.', 'fw' ),
			),
			'suffix' => array(
				'type'  => 'text',
				'label' => __( 'Suffix', 'fw' ),
				'value' => '',
				'desc'  => __( 'Shown after the number, e.g. + or %.', 'fw' ),
			),
			'decimals' => array(
				'type'    => 'select',
				'label'   => __( 'Decimal Places', 'fw' ),
				'value'   => '0',
				'choices' => array( '0' => '0', '1' => '1', '2' => '2', '3' => '3' ),
			),
			'separator' => array(
				'type'    => 'select',
				'label'   => __( 'Thousands Separator', 'fw' ),
				'value'   => 'yes',
				'choices' => array( 'yes' => __( 'Yes', 'fw' ), 'no' => __( 'No', 'fw' ) ),
			),
			'duration' => array(
				'type'  => 'text',
				'label' => __( 'Duration (ms)', 'fw' ),
				'value' => '2000',
			),
			'easing' => array(
				'type'    => 'select',
				'label'   => __( 'Easing', 'fw' ),
				'value'   => 'ease-out',
				'choices' => array(
					'ease-out' => __( 'Ease Out (fast to slow)', 'fw' ),
					'linear'   => __( 'Linear', 'fw' ),
				),
			),
			'alignment' => array(
				'type'    => 'select',
				'label'   => __( 'Alignment', 'fw' ),
				'value'   => '',
				'choices' => array(
					''       => __( 'Inherit', 'fw' ),
					'left'   => __( 'Left', 'fw' ),
					'center' => __( 'Center', 'fw' ),
					'right'  => __( 'Right', 'fw' ),
				),
			),
			// Exercises the typography + compact-colour controls, which is most of
			// what makes a counter look like the surrounding design.
			'number_font'  => array(
				'type'  => 'typography',
				'label' => __( 'Number Font', 'fw' ),
			),
			'number_color' => function_exists( 'sc_color_field_compact' )
				? sc_color_field_compact( array( 'label' => __( 'Number Color', 'fw' ), 'kind' => 'text' ) )
				: array( 'type' => 'color-picker', 'label' => __( 'Number Color', 'fw' ), 'value' => '' ),
		);
	}

	/**
	 * The option paths the Before / After block exposes in its inspector.
	 *
	 * A deliberate subset: the options that matter for a block-editor user. Anything
	 * absent still round-trips untouched inside `upOptions`, because the block never
	 * rewrites the attribute wholesale — so a layout built in the page builder and
	 * then opened as a block keeps every value this inspector does not show.
	 *
	 * @return array Map of fw_akg path => option schema entry.
	 */
	private function before_after_options() {
		return array(
			'before_image' => array(
				'type'  => 'upload',
				'label' => __( 'Before Image', 'fw' ),
				'desc'  => __( 'The base image.', 'fw' ),
			),
			'after_image' => array(
				'type'  => 'upload',
				'label' => __( 'After Image', 'fw' ),
				'desc'  => __( 'The revealed image. Use the same dimensions as the Before image.', 'fw' ),
			),
			'type/comparison/design' => array(
				'type'    => 'select',
				'label'   => __( 'Design', 'fw' ),
				'value'   => 'classic',
				'choices' => $this->design_choices(),
				'desc'    => __( 'The handle / label look.', 'fw' ),
			),
			'type/comparison/orientation' => array(
				'type'    => 'select',
				'label'   => __( 'Orientation', 'fw' ),
				'value'   => 'horizontal',
				'choices' => array(
					'horizontal' => __( 'Horizontal', 'fw' ),
					'vertical'   => __( 'Vertical', 'fw' ),
				),
			),
			'type/comparison/interaction' => array(
				'type'    => 'select',
				'label'   => __( 'Interaction', 'fw' ),
				'value'   => 'drag',
				'choices' => array(
					'drag'   => __( 'Drag the handle', 'fw' ),
					'hover'  => __( 'Follow the cursor', 'fw' ),
					'toggle' => __( 'Click to toggle', 'fw' ),
				),
			),
			'type/comparison/handle_size' => array(
				'type'    => 'select',
				'label'   => __( 'Handle Size', 'fw' ),
				'value'   => 'md',
				'choices' => array(
					'sm' => __( 'Small', 'fw' ),
					'md' => __( 'Medium', 'fw' ),
					'lg' => __( 'Large', 'fw' ),
				),
			),
			'type/comparison/show_labels' => array(
				'type'         => 'switch',
				'label'        => __( 'Show Labels', 'fw' ),
				'value'        => 'yes',
				'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
				'left-choice'  => array( 'value' => 'no', 'label' => __( 'No', 'fw' ) ),
			),
			'type/comparison/auto_intro' => array(
				'type'         => 'switch',
				'label'        => __( 'Auto Intro Sweep', 'fw' ),
				'value'        => 'yes',
				'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
				'left-choice'  => array( 'value' => 'no', 'label' => __( 'No', 'fw' ) ),
				'desc'         => __( 'Hint that the slider is interactive when it scrolls into view.', 'fw' ),
			),
		);
	}

	/**
	 * Design keys from the shortcode's own registry, so the catalog keeps one
	 * source of truth rather than a copy that silently goes stale.
	 *
	 * @return array value => label
	 */
	private function design_choices() {
		$choices  = array();
		$registry = fw_ext( 'shortcodes' )
			? fw_ext( 'shortcodes' )->locate_path( '/shortcodes/before-after/views/parts/registry.php' )
			: false;

		if ( $registry && file_exists( $registry ) ) {
			$designs = require $registry;

			if ( is_array( $designs ) ) {
				foreach ( $designs as $key => $meta ) {
					$choices[ $key ] = isset( $meta['label'] ) ? $meta['label'] : ucfirst( $key );
				}
			}
		}

		return $choices ? $choices : array( 'classic' => __( 'Classic', 'fw' ) );
	}

	/**
	 * Register every block with WordPress.
	 *
	 * @internal
	 */
	public function _action_register_blocks() {
		$shortcodes = fw_ext( 'shortcodes' );

		foreach ( $this->get_blocks() as $dir => $definition ) {
			$path = $this->get_path( '/blocks/' . $dir );

			if ( ! file_exists( $path . '/block.json' ) ) {
				continue;
			}

			/*
			 * Skip a block whose element is not available — the shortcodes extension
			 * is off, or the element belongs to an extension the site has not
			 * activated (the Animation Engine ships inactive, for one).
			 *
			 * Without this the block still registers, because pick_shortcode_options()
			 * degrades to an empty array rather than failing. The result is worse than
			 * a missing block: an entry in the inserter with an empty sidebar that
			 * renders nothing, and no way for the user to tell whether they have
			 * misconfigured it or hit a bug.
			 */
			if ( ! $shortcodes || ! $shortcodes->get_shortcode( $definition['shortcode'] ) ) {
				continue;
			}

			$handle = 'fw-block-' . $dir;

			wp_register_script(
				$handle,
				$this->get_uri( '/blocks/' . $dir . '/build/index' . $this->asset_suffix() ),
				array(
					'wp-blocks',
					'wp-element',
					'wp-block-editor',
					'wp-components',
					'wp-server-side-render',
					'wp-i18n',
					UnysonPlus\Admin\Controls\Registry::HANDLE,
				),
				$this->manifest->get_version(),
				true
			);

			// The option schema this block's inspector is built from. The editor
			// never hardcodes controls — it renders whatever this describes.
			wp_add_inline_script(
				$handle,
				'window.fwBlocks = window.fwBlocks || {}; window.fwBlocks['
					. wp_json_encode( 'unysonplus/' . $dir ) . '] = '
					. wp_json_encode( array(
						'options' => $definition['options'],
						// Only set for elements with NO options at all — the note the
						// inspector shows in place of an empty panel.
						'no_options_note' => isset( $definition['no_options_note'] )
							? $definition['no_options_note']
							: null,
					) ) . ';',
				'before'
			);

			register_block_type(
				$path,
				array(
					'render_callback' => $this->make_render_callback( $definition['shortcode'] ),
				)
			);
		}
	}

	/**
	 * Block CSS for the editor's canvas IFRAME.
	 *
	 * ## The asset split this method exists to implement
	 *
	 * Since WordPress 6.3 the editor canvas is a real <iframe> for apiVersion 2/3
	 * blocks, which splits a block's assets in two directions:
	 *
	 * - **CSS must go INSIDE the iframe.** `enqueue_block_editor_assets` loads into
	 *   the OUTER document, so styles enqueued there never reach the preview: the
	 *   ServerSideRender markup renders unstyled and absolutely-positioned layers
	 *   collapse into normal flow (the before/after images stack instead of
	 *   overlaying). `enqueue_block_assets` is the hook WordPress replays into the
	 *   iframe, so the stylesheets go here.
	 * - **JS stays OUTSIDE, in the editor window** (see _action_editor_assets),
	 *   because the block's own script runs there and calls the shortcode runtimes
	 *   through `window.fwShortcodeInit`, passing the iframe's document as scope.
	 *
	 * `wp_enqueue_block_style()` looks like the tidy API for this and is NOT used
	 * deliberately: when `wp_should_load_block_assets_on_demand()` is true — the
	 * default here — it returns after registering only a `render_block` filter and
	 * never adds its editor hook at all, so the canvas stays unstyled.
	 *
	 * Guarded to admin: on the front end the shortcode's own static.php already
	 * loads these per-instance, and `enqueue_block_assets` fires on EVERY front-end
	 * page — enqueueing there would ship the CSS site-wide.
	 *
	 * @internal
	 */
	public function _action_block_assets() {
		if ( ! is_admin() ) {
			return;
		}

		$shortcodes = fw_ext( 'shortcodes' );

		if ( ! $shortcodes ) {
			return;
		}

		$version = $shortcodes->manifest->get_version();

		foreach ( $this->get_blocks() as $definition ) {
			if ( empty( $definition['styles'] ) ) {
				continue;
			}

			/*
			 * Which extension owns the stylesheet. Most elements live in `shortcodes`,
			 * but the Animation Engine ships its own (svg-draw, webgl-object, …) and
			 * their paths resolve against THAT extension's URI. Resolving every path
			 * against `shortcodes` would produce a URL that 404s — one that looks
			 * plausible enough in the markup to waste a while.
			 */
			$owner = isset( $definition['extension'] )
				? fw_ext( $definition['extension'] )
				: $shortcodes;

			if ( ! $owner ) {
				continue;
			}

			foreach ( $definition['styles'] as $handle => $rel ) {
				$src = fw_min_uri( $owner->get_declared_URI( $rel ) );

				/**
				 * Enqueue under an ALIAS handle with no dependencies.
				 *
				 * The obvious call — wp_enqueue_style( $handle, $src, array() ) — does
				 * not work here, twice over. wp_enqueue_style() ignores the src and
				 * deps you pass when the handle is already registered, and it usually
				 * is: the shortcode's static.php registered it, complete with its real
				 * dependencies. And a style whose dependency is NOT registered is
				 * silently DROPPED.
				 *
				 * Those dependencies are front-end handles (`font-awesome`,
				 * `fw-ext-builder-frontend-grid`, …) registered during the framework's
				 * front-end pass, which does not run for the block editor. So the
				 * element's own CSS vanished from the canvas — Icon Box and Special
				 * Heading each hit this with a DIFFERENT missing dependency, which is
				 * why special-casing one of them was not a fix.
				 *
				 * An alias carries no deps and therefore cannot be dropped. The real
				 * dependencies are still enqueued below when they happen to be
				 * registered, so utilities and icon fonts come along where available.
				 */
				wp_enqueue_style( $handle . '-fw-block', $src, array(), $version );

				$deps = isset( $GLOBALS['wp_styles']->registered[ $handle ] )
					? (array) $GLOBALS['wp_styles']->registered[ $handle ]->deps
					: array();

				foreach ( $deps as $dep ) {
					// font-awesome is framework-owned and cheap to register on demand;
					// other deps are enqueued only if something already registered them.
					if ( 'font-awesome' === $dep && ! wp_style_is( $dep, 'registered' ) ) {
						wp_register_style(
							'font-awesome',
							fw_get_framework_directory_uri( '/static/libs/font-awesome/css/font-awesome.min.css' ),
							array(),
							fw()->manifest->get_version()
						);
					}

					if ( wp_style_is( $dep, 'registered' ) ) {
						wp_enqueue_style( $dep );
					}
				}
			}
		}
	}

	/**
	 * Load each block's shortcode assets into the block EDITOR.
	 *
	 * ServerSideRender fetches a block's markup over the REST API — a separate
	 * request from the one that renders the editor page. Any wp_enqueue_style() /
	 * wp_enqueue_script() the shortcode performs during that REST request is
	 * therefore discarded, and the preview arrives as unstyled HTML: absolutely
	 * positioned layers collapse into normal flow and the images stack vertically
	 * instead of overlaying.
	 *
	 * So the same static.php is loaded up-front here, on the editor page itself.
	 * The JS side needs the companion re-init hook (see below) because these
	 * scripts run at page load, while the preview markup appears later.
	 *
	 * @internal
	 */
	public function _action_editor_assets() {
		/**
		 * Container blocks default to `align: full` in the editor, so the canvas shows them
		 * as the full-bleed bands they are on the front end instead of boxing them into the
		 * content column. Editor-only on purpose: driving the inner width with
		 * `supports.layout` was measured to clamp `fw-container-fluid` down to theme.json's
		 * contentSize on the FRONT END, which inverted the Full Width option. See the header
		 * of container-width.js for the measurements.
		 */
		wp_enqueue_script(
			'fw-blocks-container-width',
			$this->get_uri( '/static/js/container-width.js' ),
			array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-blocks' ),
			$this->manifest->get_version(),
			true
		);

		$shortcodes = fw_ext( 'shortcodes' );

		if ( ! $shortcodes ) {
			return;
		}

		foreach ( $this->get_blocks() as $definition ) {
			$shortcode = $shortcodes->get_shortcode( $definition['shortcode'] );

			if ( $shortcode ) {
				$shortcode->_enqueue_static();
			}
		}
	}

	/**
	 * Minified bundle in production, readable bundle under SCRIPT_DEBUG.
	 *
	 * Deliberately NOT fw_min_uri(): that helper only rewrites paths recorded in
	 * framework/build-manifest.php, which is build.mjs's artifact. These bundles
	 * come from the separate modern-JS pipeline (build/build-controls.mjs), so
	 * they resolve the same way UnysonPlus\Admin\Controls\Registry does rather
	 * than coupling the two build systems together.
	 *
	 * @return string '.js' or '.min.js'
	 */
	private function asset_suffix() {
		return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';
	}

	/**
	 * Build a render callback that delegates to an Unyson+ shortcode.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return callable
	 */
	private function make_render_callback( $tag ) {
		/*
		 * $content is the rendered INNER BLOCKS, which WordPress passes as the second
		 * argument. Container elements — section, column, container, flexbox — render
		 * it inside their own wrapper via do_shortcode( $content ), exactly as the
		 * page builder hands them their children.
		 *
		 * Non-container blocks have no inner blocks, so they receive '' and behave
		 * as before.
		 */
		$definitions = $this->get_blocks();
		$structural  = array();

		foreach ( $definitions as $definition ) {
			if ( $definition['shortcode'] === $tag && ! empty( $definition['atts_defaults'] ) ) {
				$structural = $definition['atts_defaults'];
			}
		}

		return function ( $attributes, $content = '' ) use ( $tag, $structural ) {
			$shortcodes = fw_ext( 'shortcodes' );

			if ( ! $shortcodes ) {
				return '';
			}

			$shortcode = $shortcodes->get_shortcode( $tag );

			if ( ! $shortcode ) {
				return '';
			}

			// Same assets the page builder would enqueue for this element.
			$shortcode->_enqueue_static();

			$atts = isset( $attributes['upOptions'] ) && is_array( $attributes['upOptions'] )
				? $attributes['upOptions']
				: array();

			/**
			 * Fill in the shortcode's declared defaults underneath the block's own
			 * values.
			 *
			 * A block stores ONLY what the user changed — a freshly inserted block
			 * has `upOptions = {}`. The page builder, by contrast, saves a complete
			 * value set, so the shortcode's view has always been handed every att
			 * it declares. Passing the block's sparse array straight through meant
			 * every untouched option arrived empty: a new Counter rendered a target
			 * of 0 instead of its declared 100, and read as broken on insert.
			 *
			 * fw_get_options_values_from_input() with no input returns exactly the
			 * declared defaults, flattened the same way the builder stores them
			 * (group / tab containers do not namespace their children), so the two
			 * paths now hand the view the same shape.
			 *
			 * Block values win: this only supplies what the user has not set.
			 */
			/*
			 * Structural atts the PAGE BUILDER supplies from its item model rather
			 * than from the element's options — a column's `width` comes from the row
			 * it sits in, and is not a declared option, so nothing in the options
			 * pass produces it.
			 *
			 * A block has no such parent. Without a default the element reads an
			 * undefined key: `column` emitted "Undefined array key width" on every
			 * block render, which is a PHP warning printed into the block preview.
			 */
			if ( $structural ) {
				$atts = array_merge( $structural, $atts );
			}

			$options = $shortcode->get_options();

			if ( ! empty( $options ) ) {
				$defaults = fw_get_options_values_from_input( $options, array() );

				if ( is_array( $defaults ) ) {
					$atts = array_merge( $defaults, $atts );
				}
			}

			/*
			 * NOT validated here, and that is a measured decision rather than an
			 * oversight. The obvious improvement is to pass $atts into the call above
			 * instead of an empty array, which would run every option type's
			 * _get_value_from_input() — the same validation the page builder's save
			 * path performs — and make the two surfaces agree by construction.
			 *
			 * It was tried, against a snapshot of all blocks' rendered output. Three
			 * changed:
			 *
			 * - `icon-box` and `image-content` gained the <p> wrappers wpautop adds.
			 *   Genuine improvements: the same text authored in a block and in the
			 *   builder currently produces different markup.
			 * - `notification` LOST its dismiss button, and that is the blocker.
			 *
			 * FW_Option_Type_Switch::_get_value_from_input() json_decode()s its input
			 * and then compares with `in_array( …, true )`. It therefore requires the
			 * JSON-encoded form: the string 'true' passes, boolean true does NOT
			 * (json_decode(true) yields int 1, which matches neither choice, so the
			 * option default is returned instead).
			 *
			 * The React switch control emits the DECLARED value — boolean true — which
			 * is correct for this unvalidated path and is what every block already
			 * saved. Turning validation on would silently flip every such switch OFF,
			 * in content that already exists.
			 *
			 * Doing this properly means changing the switch control's wire format and
			 * its comparison logic, and migrating already-saved block attributes. That
			 * is a deliberate piece of work, not a one-line improvement, so the
			 * unvalidated path stands until it is done.
			 *
			 * What holds the line meanwhile: framework/tests/core-contracts-test.php
			 * asserts that what each React control emits survives the PHP path
			 * unchanged. The controls agreeing with the validators is what keeps the
			 * two surfaces interchangeable while nothing enforces it at render time.
			 */

			return $shortcode->render( $atts, (string) $content );
		};
	}

	/**
	 * Add the Unyson+ block category.
	 *
	 * @internal
	 *
	 * @param array $categories Existing categories.
	 *
	 * @return array
	 */
	public function _filter_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'unysonplus' === $category['slug'] ) {
				return $categories;
			}
		}

		array_unshift(
			$categories,
			array(
				'slug'  => 'unysonplus',
				'title' => __( 'Unyson+', 'fw' ),
			)
		);

		return $categories;
	}
}
