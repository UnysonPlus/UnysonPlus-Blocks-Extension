/**
 * Counter — the block editor half.
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
const { PanelBody, Disabled } = wp.components;
const { useRef } = wp.element;
const ServerSideRender = wp.serverSideRender;

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

		/*
		 * Unlike Before/After there is no "not configured yet" state to guard
		 * against — `number` defaults to 100 in the schema, so a freshly inserted
		 * counter already renders something real. No Placeholder branch is needed.
		 */
		return (
			<div { ...blockProps }>
				<Inspector
					options={ options }
					value={ upOptions }
					onChange={ ( next ) => setAttributes( { upOptions: next } ) }
				/>

				{ /*
				  * The preview is deliberately INERT.
				  *
				  * A dynamic block's preview is a picture of the element, not a working
				  * copy of it. The counter animates on an IntersectionObserver, so left
				  * live it would re-run its count-up every time the editor scrolls the
				  * block in and out of view — which reads as the preview flickering
				  * while you work.
				  *
				  * The shortcode's front-end runtime is deliberately NOT replayed over
				  * this preview (Before/After does replay it). The server-rendered markup
				  * already contains the final formatted number, so replaying the count-up
				  * init would only reset the preview to the START value — showing a
				  * static "0" where the useful preview is "100".
				  *
				  * `Disabled` is what core blocks use for this; the explicit
				  * pointerEvents rule is belt-and-braces so behaviour does not depend on
				  * component CSS reaching the canvas iframe. Visitors still get the
				  * animated version on the front end.
				  */ }
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
			</div>
		);
	},

	// Dynamic block: the front end is rendered in PHP by the shortcode, so
	// nothing is serialized into post content but the attributes themselves.
	save() {
		return null;
	},
} );
