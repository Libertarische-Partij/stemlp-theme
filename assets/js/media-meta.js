( function ( wp ) {
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var SelectControl = wp.components.SelectControl;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;

    function HeaderHeroPanel() {
        var meta = useSelect( function ( select ) {
            return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
        }, [] );
        var editPost = useDispatch( 'core/editor' ).editPost;

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'header-hero-panel',
                title: __( 'Header Hero', 'stemlp' ),
                className: 'header-hero-panel',
            },
            el( SelectControl, {
                label: __( 'Header hero', 'stemlp' ),
                help: __( 'Show or hide the header hero on this page.', 'stemlp' ),
                value: meta.title_position || '',
                options: [
                    { label: __( 'Show (default)', 'stemlp' ), value: '' },
                    { label: __( 'Off', 'stemlp' ), value: 'off' },
                ],
                onChange: function ( newVal ) {
                    editPost( { meta: Object.assign( {}, meta, { title_position: newVal } ) } );
                },
            } )
        );
    }

    registerPlugin( 'header-hero-plugin', { render: HeaderHeroPanel } );
}( window.wp ) );
