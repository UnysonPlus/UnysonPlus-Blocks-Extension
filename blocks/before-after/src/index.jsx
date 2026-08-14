/**
 * Before / After — the block editor half.
 *
 * There is almost nothing block-specific in here, and that is the point. The
 * inspector is generated from the option schema PHP handed over in
 * `window.fwBlocks[ name ].options`, rendered by the shared React control layer
 * (`fw.controls`). The canvas preview is ServerSideRender, so what the editor
 * shows is literally the shortcode's own output — not a second implementation
 * that can drift from it.
 *
 * Adding another block is therefore mostly a PHP job: declare the shortcode tag
 * and the option paths, and reuse this file's shape.
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, Placeholder, Disabled } = wp.components;
const { useEffect, useRef } = wp.element;
const ServerSideRender = wp.serverSideRender;

/**
 * Re-run the shortcode front-end runtimes over freshly-inserted preview markup.
 *
 * ServerSideRender replaces its contents asynchronously (a REST round trip) —
 * long after the shortcode scripts ran their DOMContentLoaded init. Each
 * shortcode that needs post-load wiring pushes its idempotent init onto
 * `window.fwShortcodeInit`; this watches the preview subtree and replays them
 * whenever the markup changes.
 *
 * A MutationObserver is used rather than a render callback because
 * ServerSideRender exposes no "finished" hook.
 *
 * @param {Object} ref React ref to the element wrapping the preview.
 */
function useShortcodeRuntime( ref ) {
	useEffect( () => {
		const node = ref.current;

		if ( ! node || typeof window.MutationObserver === 'undefined' ) {
			return undefined;
		}

		let frame = null;

		const run = () => {
			window.cancelAnimationFrame( frame );

			// Coalesce the burst of mutations a single swap produces, and let
			// the DOM settle before measuring — the sliders read element sizes.
			frame = window.requestAnimationFrame( () => {
				// The canvas is an iframe (WP 6.3+), so the preview markup lives
				// in a DIFFERENT document from the one this script was loaded
				// into. Hand the runtimes the node's OWN document, or they will
				// query the outer document and find nothing.
				const scope = node.ownerDocument || window.document;

				( window.fwShortcodeInit || [] ).forEach( ( init ) => {
					try {
						init( scope );
					} catch ( e ) {
						// One misbehaving runtime must not stop the others.
						window.console.error( e );
					}
				} );
			} );
		};

		const observer = new window.MutationObserver( run );

		observer.observe( node, { childList: true, subtree: true } );
		run(); // markup may already be present on remount

		return () => {
			window.cancelAnimationFrame( frame );
			observer.disconnect();
		};
	}, [ ref ] );
}

import metadata from '../block.json';

/**
 * Read a `/`-delimited path out of a nested object.
 *
 * Mirrors the PHP side's fw_akg(), which is how the shortcode's view reads the
 * very same paths — so the inspector and the renderer agree by construction.
 *
 * @param {string} path   e.g. 'type/comparison/design'
 * @param {Object} source Nested object.
 * @return {*} The value, or undefined.
 */
function getPath( path, source ) {
	return path.split( '/' ).reduce(
		( carry, key ) => ( carry && typeof carry === 'object' ? carry[ key ] : undefined ),
		source
	);
}

/**
 * Immutably set a `/`-delimited path, cloning only the branch that changes.
 *
 * Every untouched key is carried over by reference, which is what lets a block
 * edit a handful of options without disturbing the rest of a value that may have
 * been authored in the page builder.
 *
 * @param {string} path  e.g. 'type/comparison/design'
 * @param {*}      value Next value.
 * @param {Object} source Nested object.
 * @return {Object} A new object.
 */
function setPath( path, value, source ) {
	const [ head, ...rest ] = path.split( '/' );
	const base = source && typeof source === 'object' ? source : {};

	return {
		...base,
		[ head ]: rest.length
			? setPath( rest.join( '/' ), value, base[ head ] )
			: value,
	};
}

/**
 * The block's sidebar, generated from the schema.
 *
 * @param {Object}   props
 * @param {Object}   props.options  Map of path => option schema entry.
 * @param {Object}   props.value    The current upOptions object.
 * @param {Function} props.onChange Called with the next upOptions object.
 */
function Inspector( { options, value, onChange } ) {
	const controls = window.fw && window.fw.controls;

	if ( ! controls ) {
		return null;
	}

	return (
		<InspectorControls>
			<PanelBody title="Settings" initialOpen={ true }>
				{ Object.keys( options ).map( ( path ) => (
					<controls.Option
						key={ path }
						option={ options[ path ] }
						value={ getPath( path, value ) ?? options[ path ].value }
						onChange={ ( next ) => onChange( setPath( path, next, value ) ) }
					/>
				) ) }
			</PanelBody>
		</InspectorControls>
	);
}

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const previewRef = useRef( null );
		const config = ( window.fwBlocks || {} )[ metadata.name ] || {};
		const options = config.options || {};
		const upOptions = attributes.upOptions || {};

		const ready = !! ( getPath( 'before_image/url', upOptions ) &&
			getPath( 'after_image/url', upOptions ) );

		useShortcodeRuntime( previewRef );

		return (
			<div { ...blockProps }>
				<Inspector
					options={ options }
					value={ upOptions }
					onChange={ ( next ) => setAttributes( { upOptions: next } ) }
				/>

				{ ready ? (
					/*
					 * The preview is deliberately INERT.
					 *
					 * A dynamic block's preview is a picture of the element, not a
					 * working copy of it. Left interactive, the slider's own
					 * pointerdown handlers swallow the click that should select the
					 * block, and a drag on the handle becomes a Gutenberg BLOCK drag
					 * (the block fades out and starts moving). Both are the editor
					 * and the element fighting over the same gesture.
					 *
					 * `Disabled` is what core blocks use for this; the explicit
					 * pointerEvents rule is belt-and-braces so behaviour does not
					 * depend on component CSS reaching the canvas iframe. The
					 * element is still initialised underneath, so the preview shows
					 * the real start position and design — it just cannot be
					 * operated. Visitors get the interactive version on the front end.
					 */
					<Disabled>
						<div
							ref={ previewRef }
							style={ { pointerEvents: 'none', userSelect: 'none' } }
						>
							<ServerSideRender
								block={ metadata.name }
								attributes={ { upOptions } }
							/>
						</div>
					</Disabled>
				) : (
					<Placeholder
						icon="image-flip-horizontal"
						label="Before / After"
						instructions="Choose a Before and an After image in the block settings to preview the comparison."
					/>
				) }
			</div>
		);
	},

	// Dynamic block: the front end is rendered in PHP by the shortcode, so
	// nothing is serialized into post content but the attributes themselves.
	save() {
		return null;
	},
} );
