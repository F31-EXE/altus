// Инициализация TinyMCE для раздела «Блог» + автогенерация slug.
(function () {
    'use strict';

    // --- Автогенерация slug из заголовка (только на странице создания) ---
    var titleEl = document.getElementById('post-title');
    var slugEl = document.getElementById('post-slug');
    var slugPreview = document.getElementById('slug-preview');

    var MAP = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh',
        'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
        'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
        'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
    };

    function slugify(text) {
        text = (text || '').toLowerCase().trim();
        var out = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text[i];
            out += MAP.hasOwnProperty(ch) ? MAP[ch] : ch;
        }
        return out.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function refreshPreview() {
        if (slugPreview && slugEl) {
            slugPreview.textContent = slugEl.value || slugify(titleEl ? titleEl.value : '') || '…';
        }
    }

    if (titleEl && slugEl) {
        var autoslug = slugEl.getAttribute('data-autoslug') === '1' && slugEl.value === '';

        titleEl.addEventListener('input', function () {
            if (autoslug) {
                slugEl.value = slugify(titleEl.value);
            }
            refreshPreview();
        });

        slugEl.addEventListener('input', function () {
            autoslug = false; // пользователь правит вручную — больше не перетираем
            slugEl.value = slugify(slugEl.value);
            refreshPreview();
        });
    }
    refreshPreview();

    // --- TinyMCE ---
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE не загрузился (нет соединения с CDN?)');
        return;
    }

    tinymce.init({
        selector: '#post-body',
        license_key: 'gpl',
        height: 520,
        menubar: 'edit view insert format table',
        plugins: 'advlist autolink lists link image table code fullscreen media wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image media table | alignleft aligncenter alignright | code fullscreen',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        automatic_uploads: true,
        paste_data_images: true,
        images_upload_credentials: true,
        images_upload_handler: function (blobInfo) {
            return new Promise(function (resolve, reject) {
                var fd = new FormData();
                fd.append('file', blobInfo.blob(), blobInfo.filename());

                fetch(window.BLOG_UPLOAD_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': window.CSRF_TOKEN },
                    credentials: 'same-origin',
                    body: fd
                })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            return { ok: res.ok, data: data };
                        });
                    })
                    .then(function (r) {
                        if (r.ok && r.data && r.data.location) {
                            resolve(r.data.location);
                        } else {
                            reject((r.data && r.data.error) || 'Ошибка загрузки изображения');
                        }
                    })
                    .catch(function () {
                        reject('Сеть недоступна при загрузке изображения');
                    });
            });
        }
    });

    // Синхронизировать редактор с textarea перед отправкой формы
    var form = document.getElementById('post-form');
    if (form) {
        form.addEventListener('submit', function () {
            if (window.tinymce) {
                tinymce.triggerSave();
            }
        });
    }
})();
