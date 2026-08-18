(() => {
  // ../framework/extensions/gutenberg/blocks/special-heading/block.json
  var block_default = {
    $schema: "https://schemas.wp.org/trunk/block.json",
    apiVersion: 3,
    name: "unysonplus/special-heading",
    title: "Special Heading",
    category: "unysonplus",
    icon: "heading",
    description: "A section heading with an overline, title and subtitle \u2014 the standard way to open a section.",
    keywords: ["heading", "title", "subtitle", "section", "eyebrow", "overline"],
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
    editorScript: "fw-block-special-heading"
  };

  // ../framework/extensions/gutenberg/blocks/special-heading/src/index.jsx
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
      const hasContent = !!((upOptions.overline || "").trim() || (upOptions.title || "").trim() || (upOptions.subtitle || "").trim());
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
         * working copy. Special Heading renders entirely server-side and ships
         * no front-end JS, so there is no runtime to replay: the markup the
         * server returns IS the finished element.
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
          icon: "heading",
          label: "Special Heading",
          instructions: "Add an overline, title or subtitle in the block settings."
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
