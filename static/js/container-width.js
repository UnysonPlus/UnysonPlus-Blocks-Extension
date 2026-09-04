/**
 * Container blocks — real width in the block editor.
 *
 * Section / Container / Bleed Section / Masonry Section are full-bleed BANDS in the page
 * builder: the <section> spans its parent and an inner `.fw-container` (or, with Full Width
 * on, `.fw-container-fluid`) holds the content. The block editor knows none of that, so it
 * boxed them into the 780px content column and the canvas disagreed with the rendered page.
 *
 * Fix: default these blocks to `align: full`, which is what actually widens the editor
 * canvas. Only an UNSET value is filled in, so a user who picks Wide or None from the block
 * toolbar keeps their choice.
 *
 * ## Why this does not touch the inner width (measured, not assumed)
 *
 * The obvious move — declaring `supports.layout` with a `constrained` default — is wrong. It
 * makes WordPress emit `is-layout-constrained` plus its own max-width CSS onto the RENDERED
 * block, on top of the theme's container. Measured on the front end (theme container 1170px,
 * theme.json contentSize 780px):
 *
 *     Full Width OFF   fw-container        1170px (max-width 1218px)   unchanged
 *     Full Width ON    fw-container-fluid   780px (max-width  780px)   *** WRONG ***
 *
 * Gutenberg clamps a deliberately FLUID container down to contentSize, so turning Full Width
 * ON rendered the section NARROWER than OFF — the option inverted. Without layout support the
 * theme's own containers are correct unaided. `align` was checked the same way and is safe:
 * with `align:full` stored the front end is identical to without it, because a classic theme
 * does not style `alignfull`.
 *
 * That is this extension's principle holding: a block is a second AUTHORING surface, never a
 * second RENDERING path. So the fix stays editor-only, and the theme keeps owning width.
 */
( function ( wp ) {
	if ( ! wp || ! wp.hooks || ! wp.compose || ! wp.element ) { return; }

	// Only the container blocks that carry Unyson's Full Width option. flexbox / column are
	// deliberately absent — they have no full-width option at all.
	var BLOCKS = [
		'unysonplus/section',
		'unysonplus/container',
		'unysonplus/bleed-section',
		'unysonplus/masonry-section'
	];

	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var useEffect = wp.element.useEffect;
	var createElement = wp.element.createElement;

	var withContainerWidth = createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var isOurs = BLOCKS.indexOf( props.name ) !== -1;
			var align = ( props.attributes || {} ).align;

			// Hooks must run unconditionally — the guard lives inside the effect.
			useEffect( function () {
				if ( isOurs && align === undefined ) {
					props.setAttributes( { align: 'full' } );
				}
			}, [ isOurs, align ] );

			return createElement( BlockEdit, props );
		};
	}, 'withUpwContainerWidth' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'unysonplus/container-width', withContainerWidth );
} )( window.wp );
