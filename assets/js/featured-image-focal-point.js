( function ( wp ) {
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var __ = wp.i18n.__;
    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var FocalPointPicker = wp.components.FocalPointPicker;
    var PanelBody = wp.components.PanelBody;
    var useEntityProp = wp.coreData.useEntityProp;
    var useSelect = wp.data.useSelect;

    var DEFAULT_FOCAL_POINT = { x: 0.5, y: 0.5 };

    function useFeaturedImageFocalPoint() {
        var postType = useSelect( function ( select ) {
            return select( 'core/editor' ).getCurrentPostType();
        }, [] );
        var metaState = useEntityProp( 'postType', postType, 'meta' );
        var meta = metaState[ 0 ];
        var setMeta = metaState[ 1 ];
        var focalPoint = ( meta && meta.featured_image_focal_point ) || DEFAULT_FOCAL_POINT;

        function setFocalPoint( value ) {
            setMeta( Object.assign( {}, meta || {}, { featured_image_focal_point: value } ) );
        }

        return { focalPoint: focalPoint, setFocalPoint: setFocalPoint };
    }

    var wrapPostFeaturedImage = createHigherOrderComponent( function ( PostFeaturedImage ) {
        return function ( props ) {
            var media = props.media;
            var focal = useFeaturedImageFocalPoint();
            var focalPoint = focal.focalPoint;
            var setFocalPoint = focal.setFocalPoint;

            if ( ! media || ! media.source_url ) {
                return el( PostFeaturedImage, props );
            }

            var position = Math.round( focalPoint.x * 100 ) + '% ' + Math.round( focalPoint.y * 100 ) + '%';

            return el(
                Fragment,
                null,
                el( 'style', null, [
                    '.stemlp-featured-image-focal-point { margin-inline: -16px; overflow: clip; }',
                    '.editor-post-featured-image__preview-image { object-position: ' + position + ' !important; }',
                ].join( '\n' ) ),
                el( PostFeaturedImage, props ),
                el(
                    PanelBody,
                    {
                        name: 'featured-image-focal-point',
                        title: __( 'Featured image focal point', 'stemlp' ),
                        initialOpen: true,
                        className: 'stemlp-featured-image-focal-point',
                    },
                    el( FocalPointPicker, {
                        label: __( 'Choose the part of the image that should stay visible when cropped.', 'stemlp' ),
                        url: media.source_url,
                        value: focalPoint,
                        onChange: setFocalPoint,
                        __nextHasNoMarginBottom: true,
                    } )
                )
            );
        };
    }, 'stemlpWrapPostFeaturedImage' );

    addFilter( 'editor.PostFeaturedImage', 'stemlp/featured-image-focal-point', wrapPostFeaturedImage );

    var addFeaturedImageObjectPosition = createHigherOrderComponent( function ( BlockListBlock ) {
        return function ( props ) {
            var focal = useFeaturedImageFocalPoint();
            var focalPoint = focal.focalPoint;

            if ( props.name !== 'core/post-featured-image' ) {
                return el( BlockListBlock, props );
            }

            var position = Math.round( focalPoint.x * 100 ) + '% ' + Math.round( focalPoint.y * 100 ) + '%';
            var wrapperProps = Object.assign( {}, props.wrapperProps || {}, {
                style: Object.assign( {}, ( props.wrapperProps && props.wrapperProps.style ) || {}, {
                    '--featured-image-focal-point': position,
                } ),
                className: ( props.className || '' ) + ' stemlp-has-featured-image-focal-point',
            } );

            return el( BlockListBlock, Object.assign( {}, props, { wrapperProps: wrapperProps } ) );
        };
    }, 'stemlpAddFeaturedImageObjectPosition' );

    addFilter( 'editor.BlockListBlock', 'stemlp/featured-image-focal-point', addFeaturedImageObjectPosition );
}( window.wp ) );
