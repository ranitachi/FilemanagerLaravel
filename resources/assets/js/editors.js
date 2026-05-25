/**
 * Laravel Secure File Manager — TinyMCE Integration
 *
 * @example
 * <script src="/vendor/filemanager/js/tinymce-integration.js"></script>
 * <script>
 *   FileManagerTinyMCE.init('#editor', { pickerUrl: '/filemanager/picker' });
 * </script>
 */
window.FileManagerTinyMCE = {

    /**
     * Initialize TinyMCE with File Manager file picker.
     *
     * @param {string} selector  - CSS selector for the textarea
     * @param {Object} options   - Optional overrides
     */
    init: function (selector, options) {
        options = Object.assign({
            pickerUrl:    '/filemanager/picker',
            popupWidth:   960,
            popupHeight:  640,
            plugins:      'image link lists code table',
            toolbar:      'undo redo | blocks | bold italic | alignleft aligncenter alignright | image link | code',
        }, options || {});

        var self = this;

        tinymce.init({
            selector: selector,
            plugins:  options.plugins,
            toolbar:  options.toolbar,

            // Disable built-in file picker in favour of our File Manager
            file_picker_types: 'image media file',

            file_picker_callback: function (callback, value, meta) {
                var type = 'file';
                if (meta.filetype === 'image') type = 'image';
                if (meta.filetype === 'media') type = 'video';

                var callbackName = 'fmTinyMCE_' + Date.now();

                window[callbackName] = function (fileUrl, fileName) {
                    // TinyMCE callback signature: callback(url, { alt, title })
                    callback(fileUrl, { alt: fileName, title: fileName });
                    delete window[callbackName];
                };

                self._openPopup(
                    options.pickerUrl + '?callback=' + encodeURIComponent(callbackName) + '&type=' + type,
                    options.popupWidth,
                    options.popupHeight
                );
            },
        });
    },

    _openPopup: function (url, width, height) {
        var left = Math.max(0, (screen.width  - width)  / 2);
        var top  = Math.max(0, (screen.height - height) / 2);
        window.open(url, 'fm_picker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes');
    },
};


/**
 * Laravel Secure File Manager — Summernote Integration
 *
 * @example
 * <script src="/vendor/filemanager/js/tinymce-summernote-integration.js"></script>
 * <script>
 *   $('#editor').summernote(FileManagerSummernote.config({ pickerUrl: '/filemanager/picker' }));
 * </script>
 */
window.FileManagerSummernote = {

    /**
     * Returns a Summernote config object with File Manager button injected.
     *
     * @param {Object} options
     * @returns {Object} Summernote init config
     */
    config: function (options) {
        options = Object.assign({
            pickerUrl:   '/filemanager/picker',
            popupWidth:  960,
            popupHeight: 640,
            height:      300,
        }, options || {});

        var self = this;

        return {
            height: options.height,
            toolbar: [
                ['style',  ['style']],
                ['font',   ['bold', 'italic', 'underline', 'clear']],
                ['para',   ['ul', 'ol', 'paragraph']],
                ['insert', ['filemanager', 'picture', 'link', 'hr']],
                ['view',   ['fullscreen', 'codeview', 'help']],
            ],
            buttons: {
                filemanager: function (context) {
                    var ui = $.summernote.ui;

                    var button = ui.button({
                        contents: '<i class="note-icon-picture"></i> Files',
                        tooltip:  'Open File Manager',
                        container: 'body',
                        click: function () {
                            var callbackName = 'fmSummernote_' + Date.now();

                            window[callbackName] = function (fileUrl, fileName, meta) {
                                var mimeType = (meta && meta.mimeType) || '';

                                if (mimeType.startsWith('image/')) {
                                    context.invoke('editor.insertImage', fileUrl, function ($image) {
                                        $image.attr('alt', fileName);
                                    });
                                } else {
                                    context.invoke('editor.createLink', {
                                        text: fileName,
                                        url:  fileUrl,
                                        isNewWindow: true,
                                    });
                                }

                                delete window[callbackName];
                            };

                            self._openPopup(
                                options.pickerUrl + '?callback=' + encodeURIComponent(callbackName) + '&type=file',
                                options.popupWidth,
                                options.popupHeight
                            );
                        },
                    });

                    return button.render();
                },
            },
        };
    },

    _openPopup: function (url, width, height) {
        var left = Math.max(0, (screen.width  - width)  / 2);
        var top  = Math.max(0, (screen.height - height) / 2);
        window.open(url, 'fm_picker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes');
    },
};
