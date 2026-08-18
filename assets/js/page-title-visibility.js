(function (wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var ToggleControl = wp.components.ToggleControl;
    var createElement = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useDispatch = wp.data.useDispatch;
    var useSelect = wp.data.useSelect;

    var META_KEY = '_webnova_show_page_title';
    var BODY_CLASS = 'webnova-hide-page-title';

    function setEditorCanvasClass(shouldHide) {
        var documents = [document];
        var iframe = document.querySelector('iframe[name="editor-canvas"]');

        if (iframe && iframe.contentDocument) {
            documents.push(iframe.contentDocument);
        }

        documents.forEach(function (doc) {
            if (! doc.body) {
                return;
            }

            doc.body.classList.toggle(BODY_CLASS, shouldHide);

            if (! doc.getElementById('webnova-page-title-visibility-style')) {
                var style = doc.createElement('style');
                style.id = 'webnova-page-title-visibility-style';
                style.textContent = 'body.' + BODY_CLASS + ' .wp-block-post-title{display:none!important;}';
                doc.head.appendChild(style);
            }
        });
    }

    function PageTitleVisibilityPanel() {
        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);
        var editPost = useDispatch('core/editor').editPost;
        var showTitle = meta[META_KEY] !== false;

        useEffect(function () {
            setEditorCanvasClass(! showTitle);

            var timeout = window.setTimeout(function () {
                setEditorCanvasClass(! showTitle);
            }, 250);

            return function () {
                window.clearTimeout(timeout);
            };
        }, [showTitle]);

        return createElement(
            PluginDocumentSettingPanel,
            {
                name: 'webnova-page-title-visibility',
                title: 'Titulo de pagina',
                className: 'webnova-page-title-visibility-panel',
            },
            createElement(ToggleControl, {
                label: 'Mostrar titulo visible',
                checked: showTitle,
                onChange: function (value) {
                    editPost({
                        meta: Object.assign({}, meta, {
                            [META_KEY]: value,
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
