import Alpine from 'alpinejs';
import tinymce from 'tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/anchor';
import skinCss from 'tinymce/skins/ui/oxide/skin.min.css?inline';
import skinDarkCss from 'tinymce/skins/ui/oxide-dark/skin.min.css?inline';
import contentCss from 'tinymce/skins/content/default/content.min.css?inline';
import contentDarkCss from 'tinymce/skins/content/dark/content.min.css?inline';

window.Alpine = Alpine;

function injectSkin() {
    const dark = document.documentElement.dataset.theme === 'dark';
    let el = document.getElementById('tiny-skin');
    if (!el) { el = document.createElement('style'); el.id = 'tiny-skin'; document.head.appendChild(el); }
    el.textContent = dark ? skinDarkCss : skinCss;
}

window.initEditor = function (selector, uploadUrl, csrf) {
    injectSkin();
    const dark = document.documentElement.dataset.theme === 'dark';
    return tinymce.init({
        selector, license_key: 'gpl', skin: false, content_css: false,
        content_style: (dark ? contentDarkCss : contentCss) + ' body{font-family:system-ui,sans-serif;max-width:none;padding:1rem}',
        plugins: 'link image lists table code codesample fullscreen autoresize searchreplace anchor',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough codeformat | link image | blockquote bullist numlist | table codesample hr | code fullscreen',
        toolbar_mode: 'wrap',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre',
        menubar: false, branding: false, promotion: false, min_height: 480, autoresize_bottom_margin: 24,
        convert_urls: false, relative_urls: false, link_default_target: '', link_title: false,
        codesample_languages: [
            { text: 'Plain', value: 'plaintext' }, { text: 'Bash', value: 'bash' }, { text: 'PHP', value: 'php' },
            { text: 'JavaScript', value: 'javascript' }, { text: 'JSON', value: 'json' }, { text: 'HTML', value: 'html' },
            { text: 'CSS', value: 'css' }, { text: 'SQL', value: 'sql' }, { text: 'YAML', value: 'yaml' }, { text: 'Python', value: 'python' },
        ],
        images_upload_url: uploadUrl,
        images_upload_credentials: true,
        images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
            const fd = new FormData(); fd.append('file', blobInfo.blob(), blobInfo.filename());
            fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, credentials: 'same-origin' })
                .then(async (r) => { const j = await r.json(); r.ok ? resolve(j.location) : reject(j.message || 'Upload failed'); })
                .catch(() => reject('Upload failed'));
        }),
        setup(editor) { editor.on('change keyup', () => editor.save()); },
    });
};

window.addEventListener('laradoc:theme', injectSkin);

Alpine.start();
