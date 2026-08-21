(() => {
  // ../framework/extensions/gutenberg/blocks/column/block.json
  var block_default = {
    $schema: "https://schemas.wp.org/trunk/block.json",
    apiVersion: 3,
    name: "unysonplus/column",
    title: "Column",
    category: "unysonplus",
    icon: "columns",
    description: "A column inside a row, with a responsive width and its own alignment.",
    keywords: ["column", "col", "layout", "grid", "responsive"],
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
    editorScript: "fw-block-column"
  };

  // ../framework/extensions/gutenberg/blocks/column/src/index.jsx
  var { registerBlockType } = wp.blocks;
  var { InspectorControls, InnerBlocks, useBlockProps, useInnerBlocksProps } = wp.blockEditor;
  var { PanelBody } = wp.components;
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
      const config = (window.fwBlocks || {})[block_default.name] || {};
      const options = config.options || {};
      const upOptions = attributes.upOptions || {};
      const blockProps = useBlockProps({
        style: {
          // A neutral outline so the container's extent is visible while editing.
          // Deliberately NOT the element's own styling — see the note above.
          outline: "1px dashed #c3c4c7",
          outlineOffset: "-1px",
          padding: "12px",
          minHeight: "48px"
        }
      });
      const innerBlocksProps = useInnerBlocksProps(blockProps, {
        // No template and no allowed-blocks restriction: an Unyson+ container
        // holds whatever the page builder's equivalent holds, which is anything.
        renderAppender: InnerBlocks.ButtonBlockAppender
      });
      return /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, /* @__PURE__ */ wp.element.createElement(
        Inspector,
        {
          options,
          value: upOptions,
          onChange: (next) => setAttributes({ upOptions: next })
        }
      ), /* @__PURE__ */ wp.element.createElement("div", { ...innerBlocksProps }));
    },
    /**
     * Dynamic block WITH inner blocks: the attributes render in PHP, but the
     * children's markup must be serialized into post content so the render
     * callback receives it as $content.
     */
    save() {
      return /* @__PURE__ */ wp.element.createElement(InnerBlocks.Content, null);
    }
  });
})();
//# sourceMappingURL=index.js.map
