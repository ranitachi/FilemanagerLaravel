/**
 * Laravel Secure File Manager — CKEditor 5 Plugin
 * Usage: import and add to extraPlugins in ClassicEditor.create()
 *
 * @example
 * import { FileManagerPlugin } from '/vendor/filemanager/js/ckeditor-plugin.js';
 *
 * ClassicEditor.create(document.querySelector('#editor'), {
 *   extraPlugins: [FileManagerPlugin],
 *   fileManager: {
 *     pickerUrl: '/filemanager/picker',
 *   }
 * });
 */

export function FileManagerPlugin(editor) {
    const config = editor.config.get('fileManager') || {};
    const pickerUrl = config.pickerUrl || '/filemanager/picker';

    editor.ui.componentFactory.add('fileManager', (locale) => {
        // Lazy import ButtonView to avoid issues in SSR
        const { ButtonView } = window.CKEDITOR_MODULES || {};

        const button = new (ButtonView || createFallbackButton)(locale);

        button.set({
            label:   'File Manager',
            tooltip: true,
            withText: false,
        });

        // Icon: folder SVG inline
        button.iconView = button.iconView || { set: () => {} };
        button.template = button.template || null;

        if (button.element) {
            button.element.innerHTML = '📁';
            button.element.title     = 'File Manager';
        }

        button.on('execute', () => {
            openPicker(editor, pickerUrl, 'image');
        });

        return button;
    });

    // Also override the default image upload button if configured
    editor.on('ready', () => {
        const toolbar = editor.ui.view.toolbar;
        if (toolbar && config.replaceImageUpload) {
            // CKEditor will use file_picker_callback automatically
        }
    });
}

/**
 * Opens the File Manager picker in a popup window.
 * After selection, the callback inserts the file URL into the editor.
 *
 * @param {Object} editor  - CKEditor 5 instance
 * @param {string} baseUrl - Picker base URL
 * @param {string} type    - 'image' | 'file' | 'video'
 */
export function openPicker(editor, baseUrl, type = 'image') {
    const callbackName = 'fmCKCallback_' + Date.now();

    // Register global callback that CKEditor will use
    window[callbackName] = function (fileUrl, fileName, meta) {
        editor.model.change((writer) => {
            const mimeType = (meta && meta.mimeType) || '';

            if (type === 'image' || mimeType.startsWith('image/')) {
                const imageElement = writer.createElement('imageBlock', {
                    src: fileUrl,
                    alt: fileName,
                });
                editor.model.insertContent(imageElement);
            } else {
                // Insert as a link
                const linkText = writer.createText(fileName, {
                    linkHref: fileUrl,
                });
                editor.model.insertContent(linkText);
            }
        });

        // Clean up global
        delete window[callbackName];
    };

    const url = `${baseUrl}?callback=${encodeURIComponent(callbackName)}&type=${type}`;
    openPopup(url);
}

function openPopup(url, width = 960, height = 640) {
    const left = Math.max(0, (screen.width  - width)  / 2);
    const top  = Math.max(0, (screen.height - height) / 2);
    window.open(url, 'fm_picker', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
}

function createFallbackButton() {
    // Minimal fallback if CKEditor ButtonView is not available
    const btn = document.createElement('button');
    btn.textContent = '📁';
    return { on: () => {}, set: () => {}, element: btn };
}
