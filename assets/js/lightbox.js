/* ==========================================================================
   ALTUS - Лайтбокс
   ОДИН компонент на весь сайт. Его используют:
     - «Наши услуги»  увеличение фотографии услуги
     - «Наши работы»  увеличение плюс описание работы под фотографией
     - «Отзывы»       скан оригинала благодарственного письма
     - статьи блога   увеличение иллюстраций

   Открывает три вида содержимого, тип определяется атрибутом:
     data-lb-src   ="/path/big.jpg"   фотография
     data-lb-video ="/path/clip.mp4"  свой видеофайл, играет через <video>
     data-lb-embed ="https://..."     внешний плеер (VK Видео, YouTube, Rutube)

   Разметка триггера, любой элемент:
     <button class="media-btn"
             data-lb-src="/path/big.jpg"      один из трёх выше, обязателен
             data-lb-alt="alt для доступности"
             data-lb-title="Заголовок"        необязательный
             data-lb-text="Описание"          необязательный, встаёт под медиа
             data-lb-group="works">           необязательный, объединяет в галерею

   Построен на <dialog>.showModal(): фокус-трап, закрытие по Esc и возврат
   фокуса на триггер работают средствами браузера, без своего кода.
   ========================================================================== */

(function () {
  'use strict';

  var SELECTOR = '[data-lb-src], [data-lb-video], [data-lb-embed]';
  var dialog = null;
  var els = {};
  var group = [];      // текущая галерея
  var index = 0;

  /* --- Разметка диалога собирается один раз и переиспользуется ------------ */
  function build() {
    dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.setAttribute('aria-label', 'Просмотр изображения');
    dialog.innerHTML = [
      '<div class="lightbox__inner">',
      '  <div class="lightbox__stage">',
      '    <img class="lightbox__img" alt="" data-lb-el="img">',
      '    <video class="lightbox__video" data-lb-el="video" controls playsinline preload="metadata" hidden></video>',
      '    <iframe class="lightbox__embed" data-lb-el="embed" title="Видео"',
      '            allow="autoplay; fullscreen; encrypted-media; picture-in-picture"',
      '            allowfullscreen hidden></iframe>',
      '    <div class="lightbox__bar">',
      '      <span class="lightbox__count" data-lb-el="count"></span>',
      '      <button type="button" class="lightbox__btn" data-lb-el="close" aria-label="Закрыть">',
      '        <svg class="icon" aria-hidden="true"><use href="' + spriteBase() + '#i-x"></use></svg>',
      '      </button>',
      '    </div>',
      '    <button type="button" class="lightbox__btn lightbox__nav lightbox__nav--prev" data-lb-el="prev" aria-label="Предыдущее изображение">',
      '      <svg class="icon" aria-hidden="true"><use href="' + spriteBase() + '#i-chevron-left"></use></svg>',
      '    </button>',
      '    <button type="button" class="lightbox__btn lightbox__nav lightbox__nav--next" data-lb-el="next" aria-label="Следующее изображение">',
      '      <svg class="icon" aria-hidden="true"><use href="' + spriteBase() + '#i-chevron-right"></use></svg>',
      '    </button>',
      '  </div>',
      '  <div class="lightbox__caption" data-lb-el="caption"></div>',
      '</div>'
    ].join('');
    document.body.appendChild(dialog);

    dialog.querySelectorAll('[data-lb-el]').forEach(function (n) {
      els[n.getAttribute('data-lb-el')] = n;
    });

    els.close.addEventListener('click', close);
    els.prev.addEventListener('click', function () { step(-1); });
    els.next.addEventListener('click', function () { step(1); });

    /* Клик по подложке закрывает окно. Диалог занимает весь экран, поэтому
       отличаем фон от содержимого по границам самого элемента. */
    dialog.addEventListener('click', function (e) {
      if (e.target !== dialog) return;
      var r = dialog.getBoundingClientRect();
      var outside = e.clientY < r.top || e.clientY > r.bottom ||
                    e.clientX < r.left || e.clientX > r.right;
      if (outside) close();
    });

    dialog.addEventListener('keydown', function (e) {
      /* Внутри плеера стрелки перематывают запись, поэтому листаем галерею
         только когда фокус не на элементах управления видео. */
      if (e.target === els.video) return;
      if (e.key === 'ArrowLeft')  { e.preventDefault(); step(-1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
    });

    /* Esc обрабатывает браузер, нам остаётся снять блокировку прокрутки. */
    dialog.addEventListener('close', function () {
      document.documentElement.style.overflow = '';
      stopPlayback();
    });
  }

  /* Спрайт встроен в страницу, поэтому ссылаемся локальным якорем.
     Внешний файл браузер блокирует при открытии по file://. */
  function spriteBase() { return ''; }

  /* Видео и внешний плеер надо именно останавливать и выгружать источник.
     Если этого не сделать, звук продолжает играть после закрытия окна. */
  function stopPlayback() {
    if (els.video) {
      els.video.pause();
      els.video.removeAttribute('src');
      els.video.load();
      els.video.hidden = true;
    }
    if (els.embed) {
      els.embed.removeAttribute('src');
      els.embed.hidden = true;
    }
  }

  function kindOf(el) {
    if (el.hasAttribute('data-lb-video')) return 'video';
    if (el.hasAttribute('data-lb-embed')) return 'embed';
    return 'image';
  }

  function render() {
    var el = group[index];
    var kind = kindOf(el);
    var title = el.getAttribute('data-lb-title') || '';
    var text = el.getAttribute('data-lb-text') || '';

    stopPlayback();
    els.img.hidden = true;
    els.img.classList.remove('skeleton');

    if (kind === 'image') {
      var src = el.getAttribute('data-lb-src');
      els.img.hidden = false;
      /* Состояние загрузки: пока файл не пришёл, показываем мерцающую заглушку
         вместо пустого места или сломанной иконки. */
      els.img.classList.add('skeleton');
      els.img.removeAttribute('src');
      els.img.alt = el.getAttribute('data-lb-alt') || title;

      var probe = new Image();
      probe.onload = function () {
        els.img.src = src;
        els.img.classList.remove('skeleton');
      };
      probe.onerror = function () {
        els.img.classList.remove('skeleton');
        els.img.alt = 'Не удалось загрузить изображение';
        els.caption.innerHTML = '<p class="lightbox__text">Изображение недоступно. Обновите страницу или попробуйте позже.</p>';
      };
      probe.src = src;

    } else if (kind === 'video') {
      els.video.hidden = false;
      var poster = el.getAttribute('data-lb-poster');
      if (poster) els.video.setAttribute('poster', poster);
      else els.video.removeAttribute('poster');
      els.video.src = el.getAttribute('data-lb-video');
      els.video.currentTime = 0;
      /* Браузер вправе отказать в автозапуске. Это не ошибка: у плеера есть
         элементы управления, человек нажмёт play сам. */
      var pl = els.video.play();
      if (pl && pl.catch) pl.catch(function () {});

    } else {
      els.embed.hidden = false;
      els.embed.src = el.getAttribute('data-lb-embed');
      els.embed.title = title || 'Видео';
    }

    var html = '';
    if (title) html += '<p class="lightbox__title">' + esc(title) + '</p>';
    if (text)  html += '<p class="lightbox__text">' + esc(text) + '</p>';
    els.caption.innerHTML = html;

    var many = group.length > 1;
    els.prev.hidden = !many;
    els.next.hidden = !many;
    els.count.textContent = many ? (index + 1) + ' / ' + group.length : '';

    preload(index + 1);
    preload(index - 1);
  }

  /* Предзагружаем только соседние фотографии. Видео заранее тянуть незачем:
     это десятки мегабайт трафика за кадры, которые могут не понадобиться. */
  function preload(i) {
    if (i < 0 || i >= group.length) return;
    var el = group[i];
    if (kindOf(el) !== 'image') return;
    var im = new Image();
    im.src = el.getAttribute('data-lb-src');
  }

  function step(dir) {
    if (group.length < 2) return;
    index = (index + dir + group.length) % group.length;
    render();
  }

  function open(trigger) {
    var name = trigger.getAttribute('data-lb-group');
    group = name
      ? Array.prototype.slice.call(document.querySelectorAll(
          '[data-lb-group="' + name + '"]'))
      : [trigger];
    index = Math.max(0, group.indexOf(trigger));

    render();
    document.documentElement.style.overflow = 'hidden';   // фон не прокручивается
    dialog.showModal();
  }

  function close() { if (dialog.open) dialog.close(); }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* Делегирование: работает и для контента, добавленного после загрузки
     (например, когда список приедет с бэкенда). */
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest ? e.target.closest(SELECTOR) : null;
    if (!trigger) return;
    e.preventDefault();
    if (!dialog) build();
    open(trigger);
  });
})();
