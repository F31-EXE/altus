// Общий скрипт админки. Пока минимум — подтверждения делаются через inline onsubmit.
(function () {
    'use strict';

    // Автоскрытие flash-сообщений через 5 секунд
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });
})();
