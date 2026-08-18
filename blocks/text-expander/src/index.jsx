/**
 * Text Expander — the block editor half.
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
const { PanelBody, Disabled, Placeholder } = wp.components;
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
		 * Both content fields start empty, and an expander with nothing to expand
		 * renders as an isolated toggle button — which looks like a bug rather than
		 * an unconfigured block. Show a Placeholder until there is something to read.
		 */
		const hasContent = !! (
			( upOptions.visible_content || '' ).trim() ||
			( upOptions.hidden_content || '' ).trim()
		);
		return (
			<div { ...blockProps }>
				<Inspector
					options={ options }
					value={ upOptions }
					onChange={ ( next ) => setAttributes( { upOptions: next } ) }
				/>

				{ hasContent ? (
					/*
					 * The preview is deliberately INERT — a picture of the element, not a
					 * working copy.
					 *
					 * This element DOES ship front-end JS (the toggle), and it is deliberately
					 * not replayed here — so the preview shows BOTH the visible excerpt and
					 * the hidden content, with both button labels, rather than the collapsed
					 * state a visitor first sees.
					 *
					 * That is the better preview for an editor: you are writing both halves and
					 * want to see both. Replaying the runtime would hide the content you just
					 * typed, and a click meant to select the block would silently expand the
					 * text instead. The collapse happens for visitors on the front end.
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
						icon="editor-expand"
						label="Text Expander"
						instructions="Add the visible excerpt and the hidden content in the block settings."
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
