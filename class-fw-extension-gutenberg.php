<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Unyson+ Gutenberg blocks.
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
class FW_Extension_Gutenberg extends FW_Extension {

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
		 * Filter the block definitions.
		 *
		 * @param array $blocks Block definitions keyed by directory name.
		 */
		$this->blocks = apply_filters( 'fw_ext_gutenberg_blocks', $this->blocks );

		return $this->blocks;
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
				$out[ $id ] = $found[ $id ];
			}
		}

		return $out;
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
		foreach ( $this->get_blocks() as $dir => $definition ) {
			$path = $this->get_path( '/blocks/' . $dir );

			if ( ! file_exists( $path . '/block.json' ) ) {
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
					. wp_json_encode( array( 'options' => $definition['options'] ) ) . ';',
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

			foreach ( $definition['styles'] as $handle => $rel ) {
				$src = fw_min_uri( $shortcodes->get_declared_URI( $rel ) );

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
		return function ( $attributes ) use ( $tag ) {
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
			$options = $shortcode->get_options();

			if ( ! empty( $options ) ) {
				$defaults = fw_get_options_values_from_input( $options, array() );

				if ( is_array( $defaults ) ) {
					$atts = array_merge( $defaults, $atts );
				}
			}

			return $shortcode->render( $atts, '' );
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
