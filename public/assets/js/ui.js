/* ==========================================================================
   ALTUS - Поведение интерфейса
   Мобильное меню, состояние шапки, появление секций при прокрутке,
   раскрытие длинного текста услуги, прокрутка ряда отзывов.

   Прослушивания события scroll на window в файле нет: положение шапки и
   появление блоков считает IntersectionObserver, он не будит поток на
   каждом кадре прокрутки.
   ========================================================================== */

(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  /* Класс на <html> нужен, чтобы без JS контент не оставался скрытым. */
  document.documentElement.classList.add('js');

  /* --- 0. Логотип ---------------------------------------------------------
     Знак стоит в разметке картинкой. Если файл не отдался, убираем её,
     чтобы не висела иконка битого изображения: остаётся наборная надпись
     «АЛЬТУС». Так замена знака - это замена одного файла
     в assets/img/logo-mark.png, править разметку не нужно.               */
  (function logo() {
    document.querySelectorAll('[data-logo-mark]').forEach(function (img) {
      if (img.complete && img.naturalWidth === 0) { img.remove(); return; }
      img.addEventListener('error', function () { img.remove(); });
    });
  })();

  /* --- 1. Мобильное меню -------------------------------------------------- */
  (function nav() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var menu = document.querySelector('[data-nav]');
    if (!toggle || !menu) return;

    var mq = window.matchMedia('(max-width: 1023px)');

    function setOpen(open) {
      menu.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    }

    /* На десктопе меню всегда в разметке и всегда видимо. */
    function sync() { mq.matches ? setOpen(false) : (menu.hidden = false); }
    sync();
    mq.addEventListener('change', sync);

    toggle.addEventListener('click', function () {
      setOpen(menu.hidden);
    });

    menu.addEventListener('click', function (e) {
      if (mq.matches && e.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && mq.matches && !menu.hidden) {
        setOpen(false);
        toggle.focus();
      }
    });
  })();

  /* --- 2. Граница у шапки после отрыва от верха ---------------------------
     Обоснование движения: подсказывает, что страница прокручена.
     Сторож высотой 1px вместо обработчика scroll.                          */
  (function header() {
    var head = document.querySelector('[data-header]');
    if (!head || !('IntersectionObserver' in window)) return;

    var sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:1px;';
    document.body.prepend(sentinel);

    new IntersectionObserver(function (entries) {
      head.classList.toggle('is-stuck', !entries[0].isIntersecting);
    }).observe(sentinel);
  })();

  /* --- 3. Появление при прокрутке ----------------------------------------
     Срабатывает один раз на элемент, после чего наблюдение снимается.      */
  (function reveal() {
    var items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (!('IntersectionObserver' in window) || reduced.matches) {
      items.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el, i) {
      /* Лесенка внутри одной группы: порядок чтения слева направо. */
      var group = el.closest('[data-reveal-group]');
      if (group) {
        var sibs = Array.prototype.slice.call(group.querySelectorAll('[data-reveal]'));
        el.style.setProperty('--reveal-delay', Math.min(sibs.indexOf(el), 5) * 70 + 'ms');
      }
      io.observe(el);
    });
  })();

  /* --- 4. «Показать полностью» -------------------------------------------
     Кнопка появляется только если у услуги есть скрытая часть текста.      */
  (function disclosure() {
    document.querySelectorAll('[data-disclosure]').forEach(function (btn) {
      var panel = document.getElementById(btn.getAttribute('aria-controls'));
      if (!panel) return;

      var label = btn.querySelector('[data-disclosure-label]');
      var openText = btn.getAttribute('data-label-open') || 'Показать полностью';
      var closeText = btn.getAttribute('data-label-close') || 'Свернуть';

      btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        panel.classList.toggle('is-open', !open);
        panel.setAttribute('aria-hidden', String(open));
        if (label) label.textContent = open ? openText : closeText;
      });
    });
  })();

  /* --- 5. Уведомление о cookie ---------------------------------------------
     Показываем только тем, кто ещё не закрывал. Отметка лежит в localStorage,
     а не в cookie: ставить cookie ради уведомления о cookie - странно, и она
     улетала бы на сервер при каждом запросе без всякой пользы.

     localStorage может быть недоступен: приватное окно, запрет на хранение
     данных сайта. Поэтому все обращения к нему в try, и при отказе баннер
     просто показывается каждый раз - это хуже для привычки, но лучше, чем
     сломанная страница.                                                     */
  (function cookieNotice() {
    var box = document.querySelector('[data-cookie]');
    if (!box) return;

    var KEY = 'altus_cookie_notice';

    function remembered() {
      try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
    }
    function remember() {
      try { localStorage.setItem(KEY, '1'); } catch (e) { /* приватное окно */ }
    }

    if (remembered()) return;

    box.hidden = false;
    /* Класс ставим отдельным кадром, иначе появление не анимируется. */
    requestAnimationFrame(function () { box.classList.add('is-in'); });

    var ok = box.querySelector('[data-cookie-ok]');
    if (!ok) return;

    ok.addEventListener('click', function () {
      remember();
      box.classList.remove('is-in');
      if (reduced.matches) { box.hidden = true; return; }
      box.addEventListener('transitionend', function () { box.hidden = true; }, { once: true });
    });
  })();

  /* --- 6. Карта по нажатию -------------------------------------------------
     Виджет карты приходит на страницу только когда его попросили. Заглушка
     подменяется самим iframe, высота у них одна, поэтому блок не прыгает.
     Фокус переводим на карту: кнопки, с которой пришло нажатие, уже нет.   */
  (function map() {
    document.querySelectorAll('[data-map-src]').forEach(function (cover) {
      cover.addEventListener('click', function () {
        var frame = document.createElement('iframe');
        frame.src = cover.getAttribute('data-map-src');
        frame.title = cover.getAttribute('data-map-title') || 'Карта';
        frame.setAttribute('allowfullscreen', '');
        cover.replaceWith(frame);
        frame.focus();
      });
    });
  })();

  /* --- 7. Прокрутка ряда отзывов ------------------------------------------
     Кнопки двигают ряд на одну карточку. Состояние «дальше некуда»
     показывается через disabled, а не молчаливым бездействием.             */
  (function rail() {
    var rails = document.querySelectorAll('[data-rail]');

    rails.forEach(function (row) {
      var prev = document.querySelector('[data-rail-prev="' + row.id + '"]');
      var next = document.querySelector('[data-rail-next="' + row.id + '"]');
      if (!prev || !next) return;

      function stepSize() {
        var card = row.firstElementChild;
        if (!card) return row.clientWidth;
        var gap = parseFloat(getComputedStyle(row).columnGap) || 0;
        return card.getBoundingClientRect().width + gap;
      }

      function sync() {
        var max = row.scrollWidth - row.clientWidth - 2;
        prev.disabled = row.scrollLeft <= 2;
        next.disabled = row.scrollLeft >= max;
      }

      prev.addEventListener('click', function () {
        row.scrollBy({ left: -stepSize(), behavior: reduced.matches ? 'auto' : 'smooth' });
      });
      next.addEventListener('click', function () {
        row.scrollBy({ left: stepSize(), behavior: reduced.matches ? 'auto' : 'smooth' });
      });

      row.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync, { passive: true });
      sync();
    });
  })();
})();
