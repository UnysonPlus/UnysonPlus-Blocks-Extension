(() => {
  // ../framework/extensions/gutenberg/blocks/wc-free-shipping/block.json
  var block_default = {
    $schema: "https://schemas.wp.org/trunk/block.json",
    apiVersion: 3,
    name: "unysonplus/wc-free-shipping",
    title: "Free Shipping Bar",
    category: "unysonplus",
    icon: "info",
    description: "A progress bar showing how far the cart is from free shipping.",
    keywords: ["free shipping", "progress", "cart", "threshold", "woocommerce"],
    textdomain: "fw",
    attributes: {
      upOptions: {
        type: "object",
        default: {}
      }
    },
    supports: {
      align: ["wide", "full"],
      spacing: { margin: true, padding: true },
      html: false
    },
    editorScript: "fw-block-wc-free-shipping"
  };

  // ../framework/extensions/gutenberg/blocks/wc-free-shipping/src/index.jsx
  var { registerBlockType } = wp.blocks;
  var { InspectorControls, useBlockProps } = wp.blockEditor;
  var { PanelBody, Disabled, Notice } = wp.components;
  var ServerSideRender = wp.serverSideRender;
  registerBlockType(block_default.name, {
    edit() {
      const blockProps = useBlockProps();
      const config = (window.fwBlocks || {})[block_default.name] || {};
      const note = config.no_options_note || "This element has no settings of its own.";
      return /* @__PURE__ */ wp.element.createElement("div", { ...blockProps }, /* @__PURE__ */ wp.element.createElement(InspectorControls, null, /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Settings", initialOpen: true }, /* @__PURE__ */ wp.element.createElement(Notice, { status: "info", isDismissible: false }, note))), /* @__PURE__ */ wp.element.createElement(Disabled, null, /* @__PURE__ */ wp.element.createElement("div", { style: { pointerEvents: "none", userSelect: "none" } }, /* @__PURE__ */ wp.element.createElement(
        ServerSideRender,
        {
          block: block_default.name,
          attributes: { upOptions: {} }
        }
      ))));
    },
    // Dynamic block: the front end is rendered in PHP by the shortcode.
    save() {
      return null;
    }
  });
})();
//# sourceMappingURL=index.js.map
