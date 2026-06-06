(function (wp) {
  var el = wp.element.createElement;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var RangeControl = wp.components.RangeControl;
  var SelectControl = wp.components.SelectControl;
  var Placeholder = wp.components.Placeholder;
  var registerBlockType = wp.blocks.registerBlockType;

  registerBlockType('stemlp/events-carousel', {
    edit: function (props) {
      var blockProps = useBlockProps();
      var limit = props.attributes.limit || 10;
      var order = props.attributes.order || 'start_asc';
      var orderLabel = order === 'start_asc' ? 'Upcoming first' : 'Newest first';

      return el(
        wp.element.Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Events carousel settings', initialOpen: true },
            el(RangeControl, {
              label: 'Number of events',
              value: limit,
              onChange: function (value) {
                props.setAttributes({ limit: value ? parseInt(value, 10) : 10 });
              },
              min: 1,
              max: 30,
            }),
            el(SelectControl, {
              label: 'Order',
              value: order,
              options: [
                { value: 'start_asc', label: 'Upcoming first' },
                { value: 'start_desc', label: 'Newest first' },
              ],
              onChange: function (value) {
                props.setAttributes({ order: value || 'start_asc' });
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
              icon: 'calendar-alt',
              label: 'Events carousel',
              instructions: 'Shows events from Events Manager. Order: ' + orderLabel + ', limit: ' + limit + '. Preview appears on the front end.',
            }
          )
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
