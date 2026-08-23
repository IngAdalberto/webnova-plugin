(function (wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var ToggleControl = wp.components.ToggleControl;
    var createElement = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useDispatch = wp.data.useDispatch;
    var useSelect = wp.data.useSelect;

    var TITLE_META_KEY = '_webnova_show_page_title';
    var HEADER_META_KEY = '_webnova_show_primary_menu';
    var FOOTER_META_KEY = '_webnova_show_footer';
    var TITLE_BODY_CLASS = 'webnova-hide-page-title';
    var HEADER_BODY_CLASS = 'webnova-hide-header';
    var FOOTER_BODY_CLASS = 'webnova-hide-footer';

    function setEditorCanvasClasses(visibility) {
        var documents = [document];
        var iframe = document.querySelector('iframe[name="editor-canvas"]');

        if (iframe && iframe.contentDocument) {
            documents.push(iframe.contentDocument);
        }

        documents.forEach(function (doc) {
            if (! doc.body) {
                return;
            }

            doc.body.classList.toggle(TITLE_BODY_CLASS, ! visibility.title);
            doc.body.classList.toggle(HEADER_BODY_CLASS, ! visibility.header);
            doc.body.classList.toggle(FOOTER_BODY_CLASS, ! visibility.footer);

            if (! doc.getElementById('webnova-page-title-visibility-style')) {
                var style = doc.createElement('style');
                style.id = 'webnova-page-title-visibility-style';
                style.textContent = [
                    'body.' + TITLE_BODY_CLASS + ' .wp-block-post-title{display:none!important;}',
                    'body.' + HEADER_BODY_CLASS + ' .webnova-header{display:none!important;}',
                    'body.' + FOOTER_BODY_CLASS + ' .webnova-footer{display:none!important;}',
                ].join('');
                doc.head.appendChild(style);
            }
        });
    }

    function PageTitleVisibilityPanel() {
        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);
        var editPost = useDispatch('core/editor').editPost;
        var showTitle = meta[TITLE_META_KEY] !== false;
        var showHeader = meta[HEADER_META_KEY] !== false;
        var showFooter = meta[FOOTER_META_KEY] !== false;
        var visibility = {
            title: showTitle,
            header: showHeader,
            footer: showFooter,
        };

        useEffect(function () {
            setEditorCanvasClasses(visibility);

            var timeout = window.setTimeout(function () {
                setEditorCanvasClasses(visibility);
            }, 250);

            return function () {
                window.clearTimeout(timeout);
            };
        }, [showTitle, showHeader, showFooter]);

        return createElement(
            PluginDocumentSettingPanel,
            {
                name: 'webnova-page-title-visibility',
                title: 'Visibilidad de página',
                className: 'webnova-page-title-visibility-panel',
            },
            createElement(ToggleControl, {
                label: 'Mostrar título visible',
                checked: showTitle,
                onChange: function (value) {
                    editPost({
                        meta: Object.assign({}, meta, {
                            [TITLE_META_KEY]: value,
                        }),
                    });
                },
            }),
            createElement(ToggleControl, {
                label: 'Mostrar encabezado',
                checked: showHeader,
                onChange: function (value) {
                    editPost({
                        meta: Object.assign({}, meta, {
                            [HEADER_META_KEY]: value,
                        }),
                    });
                },
            }),
            createElement(ToggleControl, {
                label: 'Mostrar pie de página',
                checked: showFooter,
                onChange: function (value) {
                    editPost({
                        meta: Object.assign({}, meta, {
                            [FOOTER_META_KEY]: value,
                        }),
                    });
                },
            })
        );
    }

    registerPlugin('webnova-page-title-visibility', {
        render: PageTitleVisibilityPanel,
    });
})(window.wp);
