(() => {
  // ../framework/extensions/gutenberg/blocks/before-after/block.json
  var block_default = {
    $schema: "https://schemas.wp.org/trunk/block.json",
    apiVersion: 3,
    name: "unysonplus/before-after",
    title: "Before / After",
    category: "unysonplus",
    icon: "image-flip-horizontal",
    description: "An interactive before/after image comparison slider \u2014 drag, hover or click to reveal.",
    keywords: ["compare", "comparison", "slider", "reveal", "image"],
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
    editorScript: "fw-block-before-after"
  };

  // ../framework/extensions/gutenberg/blocks/before-after/src/index.jsx
  var { registerBlockType } = wp.blocks;
  var { InspectorControls, useBlockProps } = wp.blockEditor;
  var { PanelBody, Placeholder, Disabled } = wp.components;
  var { useEffect, useRef } = wp.element;
  var ServerSideRender = wp.serverSideRender;
  function useShortcodeRuntime(ref) {
    useEffect(() => {
      const node = ref.current;
      if (!node || typeof window.MutationObserver === "undefined") {
        return void 0;
      }
      let frame = null;
      const run = () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(() => {
          const scope = node.ownerDocument || window.document;
          (window.fwShortcodeInit || []).forEach((init) => {
            try {
              init(scope);
            } catch (e) {
              window.console.error(e);
            }
          });
        });
      };
      const observer = new window.MutationObserver(run);
      observer.observe(node, { childList: true, subtree: true });
      run();
      return () => {
        window.cancelAnimationFrame(frame);
        observer.disconnect();
      };
    }, [ref]);
  }
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
      const ready = !!(getPath("before_image/url", upOptions) && getPath("after_image/url", upOptions));
      useShortcodeRuntime(previewRef);
      return /* @__PURE__ */ wp.element.createElement("div", { ...blockProps }, /* @__PURE__ */ wp.element.createElement(
        Inspector,
        {
          options,
          value: upOptions,
          onChange: (next) => setAttributes({ upOptions: next })
        }
      ), ready ? (
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
          icon: "image-flip-horizontal",
          label: "Before / After",
          instructions: "Choose a Before and an After image in the block settings to preview the comparison."
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
