(function (wp) {
  var el = wp.element.createElement;
  var useSelect = wp.data.useSelect;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var Placeholder = wp.components.Placeholder;
  var registerBlockType = wp.blocks.registerBlockType;

  registerBlockType('stemlp/carousel', {
    edit: function (props) {
      var blockProps = useBlockProps();
      var categories = useSelect(function (select) {
        return select('core').getEntityRecords('taxonomy', 'category', { per_page: -1, orderby: 'name' }) || [];
      }, []);

      var options = [{ value: 0, label: '— Select category —' }];
      if (categories && categories.length) {
        categories.forEach(function (cat) {
          options.push({ value: cat.id, label: cat.name });
        });
      }

      var selectedName = '';
      if (props.attributes.categoryId && categories && categories.length) {
        var found = categories.find(function (c) { return c.id === props.attributes.categoryId; });
        if (found) selectedName = found.name;
      }

      return el(
        wp.element.Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Carousel settings', initialOpen: true },
            el(SelectControl, {
              label: 'Category',
              value: props.attributes.categoryId || 0,
              options: options,
              onChange: function (value) {
                props.setAttributes({ categoryId: parseInt(value, 10) || 0 });
              },
            })
          )
        ),
        el(
          'div',
          blockProps,
          el(
            Placeholder,
            {
              icon: 'slides',
              label: 'Posts carousel',
              instructions: selectedName
                ? 'Showing posts from category: ' + selectedName
                : 'Select a category in the sidebar to display its posts in a carousel.',
            },
            selectedName ? el('p', { style: { marginTop: '8px', opacity: 0.8 } }, 'Preview appears on the front end.') : null
          )
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
