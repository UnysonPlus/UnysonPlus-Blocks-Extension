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
				wp_enqueue_style(
					$handle,
					fw_min_uri( $shortcodes->get_declared_URI( $rel ) ),
					array(),
					$version
				);
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
