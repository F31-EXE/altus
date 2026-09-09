/* ==========================================================================
   ALTUS - Контактная форма
   Полный цикл состояний: покой, проверка, отправка, успех, ошибка.

   ПОДКЛЮЧЕНИЕ БЭКЕНДА
   Пока бэкенда нет, форма работает на заглушке и показывает все состояния.
   Чтобы включить реальную отправку, поставьте адрес обработчика в разметке:
       <form class="form" data-endpoint="/api/lead.php">
   Обработчик на PHP принимает JSON вида
       { name, phone, contact, message }
   и пересылает его в сообщения сообщества ВК (метод messages.send с ключом
   доступа сообщества). Ключ доступа хранится на сервере и на фронтенд
   не передаётся.

   Чтобы посмотреть состояние ошибки без бэкенда, добавьте на форму
   атрибут data-mock-fail="true".
   ========================================================================== */

(function () {
  'use strict';

  var form = document.querySelector('[data-form]');
  if (!form) return;

  var submit = form.querySelector('[data-submit]');
  var status = form.querySelector('[data-status]');

  /* --- Правила проверки --------------------------------------------------
     Ключ совпадает с атрибутом name у поля.                                */
  var RULES = {
    name: {
      test: function (v) { return v.trim().length >= 2; },
      message: 'Напишите, как к вам обращаться. Минимум 2 символа.'
    },
    phone: {
      /* Достаточно свободно, чтобы принять любой городской и мобильный
         формат записи, и достаточно строго, чтобы отсечь опечатки. */
      test: function (v) {
        var digits = v.replace(/\D/g, '');
        return digits.length >= 10 && digits.length <= 15;
      },
      message: 'Проверьте номер. Нужно от 10 до 15 цифр.'
    },
    message: {
      test: function (v) { return v.trim().length >= 10; },
      message: 'Опишите задачу чуть подробнее. Минимум 10 символов.'
    },
    consent: {
      test: function (v, field) { return field.checked; },
      message: 'Без согласия на обработку данных мы не сможем ответить.'
    }
  };

  function fieldWrap(el) { return el.closest('.field') || el.closest('.check-wrap') || el.parentElement; }

  function setError(el, message) {
    var wrap = fieldWrap(el);
    var box = wrap.querySelector('[data-error]');
    wrap.classList.add('is-invalid');
    el.setAttribute('aria-invalid', 'true');
    if (box) {
      box.querySelector('[data-error-text]').textContent = message;
      /* Связываем поле с текстом ошибки, чтобы скринридер прочитал причину. */
      el.setAttribute('aria-describedby', box.id);
    }
  }

  function clearError(el) {
    var wrap = fieldWrap(el);
    wrap.classList.remove('is-invalid');
    el.removeAttribute('aria-invalid');
    el.removeAttribute('aria-describedby');
  }

  function validateField(el) {
    var rule = RULES[el.name];
    if (!rule) return true;
    var ok = rule.test(el.value, el);
    ok ? clearError(el) : setError(el, rule.message);
    return ok;
  }

  function validateAll() {
    var firstBad = null;
    Object.keys(RULES).forEach(function (name) {
      var el = form.elements[name];
      if (!el) return;
      if (!validateField(el) && !firstBad) firstBad = el;
    });
    return firstBad;
  }

  /* Ошибку показываем после того, как человек ушёл из поля, а не во время
     набора: подсвечивать незаконченный ввод преждевременно. */
  Object.keys(RULES).forEach(function (name) {
    var el = form.elements[name];
    if (!el) return;
    el.addEventListener('blur', function () { validateField(el); });
    el.addEventListener('input', function () {
      if (fieldWrap(el).classList.contains('is-invalid')) validateField(el);
    });
    el.addEventListener('change', function () {
      if (el.type === 'checkbox') validateField(el);
    });
  });

  /* --- Состояния ---------------------------------------------------------- */

  function showStatus(kind, text) {
    status.className = 'form__status form__status--' + kind + ' is-visible';
    status.querySelector('[data-status-text]').textContent = text;
    status.querySelector('[data-status-icon] use')
          .setAttribute('href', spriteHref(kind === 'success' ? 'i-check' : 'i-alert-circle'));
  }

  function hideStatus() { status.className = 'form__status'; }

  /* Спрайт встроен в страницу: ссылаемся локальным якорем. */
  function spriteHref(id) { return '#' + id; }

  function setLoading(on) {
    form.classList.toggle('is-loading', on);
    submit.disabled = on;
    submit.setAttribute('aria-busy', String(on));
  }

  /* --- Отправка ----------------------------------------------------------- */

  function send(payload) {
    var endpoint = form.getAttribute('data-endpoint');

    /* Заглушка: бэкенда пока нет. Держим искусственную паузу, чтобы
       состояние отправки было видно и его можно было проверить. */
    if (!endpoint) {
      return new Promise(function (resolve, reject) {
        setTimeout(function () {
          if (form.getAttribute('data-mock-fail') === 'true') {
            reject(new Error('mock'));
          } else {
            resolve();
          }
        }, 900);
      });
    }

    return fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    hideStatus();

    var firstBad = validateAll();
    if (firstBad) {
      firstBad.focus();
      showStatus('error', 'Проверьте отмеченные поля и отправьте ещё раз.');
      return;
    }

    setLoading(true);

    var payload = {
      name: form.elements.name.value.trim(),
      phone: form.elements.phone.value.trim(),
      contact: form.elements.contact ? form.elements.contact.value.trim() : '',
      message: form.elements.message.value.trim()
    };

    send(payload)
      .then(function () {
        form.reset();
        Object.keys(RULES).forEach(function (n) {
          if (form.elements[n]) clearError(form.elements[n]);
        });
        showStatus('success', 'Заявка отправлена. Свяжемся с вами в течение рабочего дня.');
      })
      .catch(function () {
        showStatus('error', 'Не получилось отправить заявку. Позвоните нам или напишите в мессенджер.');
      })
      .finally(function () {
        setLoading(false);
        /* Уводим фокус на сообщение, чтобы результат был озвучен и виден. */
        status.setAttribute('tabindex', '-1');
        status.focus({ preventScroll: false });
      });
  });
})();
