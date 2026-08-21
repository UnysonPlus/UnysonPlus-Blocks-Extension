/**
 * NO-OPTIONS BLOCK TEMPLATE — the block editor half.
 *
 * Not a block itself. The shape used by elements that expose NO settings at all,
 * kept in one place so the archetype does not drift.
 *
 * Some elements genuinely have nothing to configure: WooCommerce's cart,
 * checkout and account elements render WooCommerce's own templates, and their
 * appearance is governed by WooCommerce settings and the theme, not by the
 * element. An empty "Settings" panel would imply otherwise — a panel you open
 * expecting controls and find blank reads as something failing to load.
 *
 * So the inspector says plainly that there is nothing to set, and where the
 * relevant settings actually live. The canvas preview is the normal
 * ServerSideRender.
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, Disabled, Notice } = wp.components;
const ServerSideRender = wp.serverSideRender;

import metadata from '../block.json';

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps();
		const config = ( window.fwBlocks || {} )[ metadata.name ] || {};
		const note = config.no_options_note || 'This element has no settings of its own.';

		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title="Settings" initialOpen={ true }>
						<Notice status="info" isDismissible={ false }>
							{ note }
						</Notice>
					</PanelBody>
				</InspectorControls>

				{ /* PREVIEW_COMMENT */ }
				<Disabled>
					<div style={ { pointerEvents: 'none', userSelect: 'none' } }>
						<ServerSideRender
							block={ metadata.name }
							attributes={ { upOptions: {} } }
						/>
					</div>
				</Disabled>
			</div>
		);
	},

	// Dynamic block: the front end is rendered in PHP by the shortcode.
	save() {
		return null;
	},
} );
