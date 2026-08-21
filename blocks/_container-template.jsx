/**
 * CONTAINER BLOCK TEMPLATE — the block editor half.
 *
 * Not a block itself. This is the shape every container block uses, kept in one
 * place so the archetype does not drift across the six that share it. The
 * generator copies it and swaps the title and the preview comment.
 *
 * ## Why containers do NOT use ServerSideRender
 *
 * Every other Unyson+ block previews itself with `ServerSideRender`, which is a
 * picture of the finished element. A container cannot: its whole purpose is to
 * hold OTHER blocks, and those have to stay editable in place. A server-rendered
 * picture of a section would be exactly as uneditable as a screenshot.
 *
 * So containers render `InnerBlocks` instead, and the children are real blocks
 * the editor owns. The element's own wrapper — background, padding, width, the
 * design preset — is applied by PHP on the front end and is NOT reproduced in
 * the canvas.
 *
 * That is a real limitation and worth being plain about rather than papering
 * over: a section with a dark background looks like a plain outline while you
 * edit it. The alternative — approximating the wrapper's styling in JS — would
 * be a second implementation of the element's CSS, guaranteed to disagree with
 * the first the moment either changes. A neutral outline that is honestly
 * neutral beats a preview that is subtly wrong.
 *
 * ## The value still round-trips
 *
 * `save()` returns `<InnerBlocks.Content />`, so the children's markup is stored
 * in post content and reaches the render callback as `$content`. PHP passes it
 * to the element, which renders it inside its wrapper with `do_shortcode()` —
 * the same thing the page builder does with a container's children.
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, InnerBlocks, useBlockProps, useInnerBlocksProps } = wp.blockEditor;
const { PanelBody } = wp.components;

import metadata from '../block.json';

/**
 * Read a `/`-delimited path out of a nested object. Mirrors fw_akg().
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
 * @param {string} path   e.g. 'type/comparison/design'
 * @param {*}      value  Next value.
 * @param {Object} source Nested object.
 * @return {Object} A new object.
 */
function setPath( path, value, source ) {
	const [ head, ...rest ] = path.split( '/' );
	const base = source && typeof source === 'object' ? source : {};

	return {
		...base,
		[ head ]: rest.length ? setPath( rest.join( '/' ), value, base[ head ] ) : value,
	};
}

/**
 * The block's sidebar, generated from the option schema.
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
		const config = ( window.fwBlocks || {} )[ metadata.name ] || {};
		const options = config.options || {};
		const upOptions = attributes.upOptions || {};

		const blockProps = useBlockProps( {
			style: {
				// A neutral outline so the container's extent is visible while editing.
				// Deliberately NOT the element's own styling — see the note above.
				outline: '1px dashed #c3c4c7',
				outlineOffset: '-1px',
				padding: '12px',
				minHeight: '48px',
			},
		} );

		const innerBlocksProps = useInnerBlocksProps( blockProps, {
			// No template and no allowed-blocks restriction: an Unyson+ container
			// holds whatever the page builder's equivalent holds, which is anything.
			renderAppender: InnerBlocks.ButtonBlockAppender,
		} );

		return (
			<>
				<Inspector
					options={ options }
					value={ upOptions }
					onChange={ ( next ) => setAttributes( { upOptions: next } ) }
				/>
				<div { ...innerBlocksProps } />
			</>
		);
	},

	/**
	 * Dynamic block WITH inner blocks: the attributes render in PHP, but the
	 * children's markup must be serialized into post content so the render
	 * callback receives it as $content.
	 */
	save() {
		return <InnerBlocks.Content />;
	},
} );
