/* ==========================================================================
   ALTUS - Контактная форма
   Полный цикл состояний: покой, проверка, отправка, успех, ошибка.

   БЭКЕНД
   Адрес обработчика стоит в разметке: data-endpoint="/api/lead",
   это App\Controllers\LeadController. Форма шлёт JSON вида
       { name, phone, contact, message, consent, website }
   Обязательны только name, phone и consent. Токен CSRF идёт заголовком
   X-CSRF-Token, поле website - ловушка для роботов.
   Без data-endpoint форма работает на заглушке и показывает все состояния.

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
    /* Поля «Что нужно сделать» здесь нет намеренно: оно необязательное.
       Имени и телефона достаточно, чтобы перезвонить и расспросить самим,
       а лишнее обязательное поле - лишний повод закрыть форму. */
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

    /* Заглушка на случай, если адрес обработчика не задан. Держим
       искусственную паузу, чтобы состояние отправки было видно. */
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

    var headers = { 'Content-Type': 'application/json' };

    /* Токен идёт заголовком, а не полем формы: тело запроса это JSON,
       и обычная проверка по $_POST до него не доберётся. */
    var token = form.getAttribute('data-csrf');
    if (token) headers['X-CSRF-Token'] = token;

    return fetch(endpoint, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (res.ok) return;

      /* Сервер отвечает разборчиво: показываем его сообщение, а не общее.
         Поля с ошибками подсвечиваем, как при проверке на месте. */
      return res.json().catch(function () { return null; }).then(function (data) {
        if (data && data.errors) {
          Object.keys(data.errors).forEach(function (field) {
            var el = form.elements[field];
            if (el) setError(el, data.errors[field][0]);
          });
        }
        var e = new Error('HTTP ' + res.status);
        e.userMessage = (data && data.error) ||
          (data && data.errors ? 'Проверьте отмеченные поля и отправьте ещё раз.' : null);
        throw e;
      });
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
      message: form.elements.message.value.trim(),
      consent: !!(form.elements.consent && form.elements.consent.checked),
      /* Ловушка для роботов: поле спрятано от человека и должно быть пустым. */
      website: form.elements.website ? form.elements.website.value : ''
    };

    send(payload)
      .then(function () {
        form.reset();
        Object.keys(RULES).forEach(function (n) {
          if (form.elements[n]) clearError(form.elements[n]);
        });
        showStatus('success', 'Заявка отправлена. Свяжемся с вами в течение рабочего дня.');
      })
      .catch(function (err) {
        showStatus('error', (err && err.userMessage) ||
          'Не получилось отправить заявку. Позвоните нам или напишите в мессенджер.');
      })
      .finally(function () {
        setLoading(false);
        /* Уводим фокус на сообщение, чтобы результат был озвучен и виден. */
        status.setAttribute('tabindex', '-1');
        status.focus({ preventScroll: false });
      });
  });
})();
