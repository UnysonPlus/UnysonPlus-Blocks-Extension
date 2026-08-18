(() => {
  // ../framework/extensions/gutenberg/blocks/text-expander/block.json
  var block_default = {
    $schema: "https://schemas.wp.org/trunk/block.json",
    apiVersion: 3,
    name: "unysonplus/text-expander",
    title: "Text Expander",
    category: "unysonplus",
    icon: "editor-expand",
    description: "Show a short excerpt with a Read more toggle that reveals the rest \u2014 for long copy, FAQs and disclosures.",
    keywords: ["read more", "expand", "collapse", "toggle", "excerpt", "accordion"],
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
    editorScript: "fw-block-text-expander"
  };

  // ../framework/extensions/gutenberg/blocks/text-expander/src/index.jsx
  var { registerBlockType } = wp.blocks;
  var { InspectorControls, useBlockProps } = wp.blockEditor;
  var { PanelBody, Disabled, Placeholder } = wp.components;
  var { useRef } = wp.element;
  var ServerSideRender = wp.serverSideRender;
  function getPath(path, source) {
    return path.split("/").reduce(
      (carry, key) => carry && typeof carry === "object" ? carry[key] : void 0,
      source
    );
  }
  function setPath(path, value, source) {
    const [head, ...rest] = path.split("/");
    const base = source && typeof source === "object" ? source : {};
    return {
      ...base,
      [head]: rest.length ? setPath(rest.join("/"), value, base[head]) : value
    };
  }
  function Inspector({ options, value, onChange }) {
    const controls = window.fw && window.fw.controls;
    if (!controls) {
      return null;
    }
    return /* @__PURE__ */ wp.element.createElement(InspectorControls, null, /* @__PURE__ */ wp.element.createElement(PanelBody, { title: "Settings", initialOpen: true }, Object.keys(options).map((path) => {
      var _a;
      return /* @__PURE__ */ wp.element.createElement(
        controls.Option,
        {
          key: path,
          option: options[path],
          value: (_a = getPath(path, value)) != null ? _a : options[path].value,
          onChange: (next) => onChange(setPath(path, next, value))
        }
      );
    })));
  }
  registerBlockType(block_default.name, {
    edit({ attributes, setAttributes }) {
      const blockProps = useBlockProps();
      const previewRef = useRef(null);
      const config = (window.fwBlocks || {})[block_default.name] || {};
      const options = config.options || {};
      const upOptions = attributes.upOptions || {};
      const hasContent = !!((upOptions.visible_content || "").trim() || (upOptions.hidden_content || "").trim());
      return /* @__PURE__ */ wp.element.createElement("div", { ...blockProps }, /* @__PURE__ */ wp.element.createElement(
        Inspector,
        {
          options,
          value: upOptions,
          onChange: (next) => setAttributes({ upOptions: next })
        }
      ), hasContent ? (
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
        /* @__PURE__ */ wp.element.createElement(Disabled, null, /* @__PURE__ */ wp.element.createElement(
          "div",
          {
            ref: previewRef,
            style: { pointerEvents: "none", userSelect: "none" }
          },
          /* @__PURE__ */ wp.element.createElement(
            ServerSideRender,
            {
              block: block_default.name,
              attributes: { upOptions }
            }
          )
        ))
      ) : /* @__PURE__ */ wp.element.createElement(
        Placeholder,
        {
          icon: "editor-expand",
          label: "Text Expander",
          instructions: "Add the visible excerpt and the hidden content in the block settings."
        }
      ));
    },
    // Dynamic block: the front end is rendered in PHP by the shortcode, so
    // nothing is serialized into post content but the attributes themselves.
    save() {
      return null;
    }
  });
})();
//# sourceMappingURL=index.js.map
