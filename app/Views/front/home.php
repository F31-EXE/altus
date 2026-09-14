<?php
/**
 * Главная страница.
 *
 * @var array $services @var array $works @var array $reviews
 * @var array $videos   @var array $about @var array $posts
 */
?>
<!-- ==========================================================================
     1. HERO. Семейство раскладки: асимметричный сплит.
     ========================================================================== -->
<section class="hero">
  <div class="shell hero__grid">

    <div class="hero__content">
      <h1 class="display h1 hero__title">Вывеска, которую <em>видно за квартал</em></h1>
      <p class="lede hero__lede">
        Проектируем, изготавливаем и монтируем световые и несветовые вывески,
        объёмные буквы, короба и крышные установки. Полный цикл от эскиза до монтажа.
      </p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#contacts">
          Обсудить проект
          <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
        </a>
        <?php if ($works): ?>
          <!-- Вторая кнопка ведёт в «Работы». Если работ в базе нет, раздела
               на странице тоже нет, и ссылка была бы в пустоту. -->
          <a class="btn btn--ghost" href="#works">Смотреть работы</a>
        <?php elseif ($services): ?>
          <a class="btn btn--ghost" href="#services">Наши услуги</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="media media--tinted hero__media">
      <img src="<?= asset('assets/img/hero.jpg') ?>" width="975" height="1300"
           fetchpriority="high" decoding="async"
           alt="Световая вывеска «Дом цветов» на фасаде здания">
    </div>

  </div>
</section>

<?php if ($services): ?>
<!-- ==========================================================================
     НАШИ УСЛУГИ. Горизонтальная лента. Данные из админки, раздел «Наши услуги».

     Лентой, а не сеткой: услуг в админке может стать сколько угодно, и сеткой
     раздел уводил страницу вниз на несколько экранов.

     В базе у услуги одно текстовое поле. Вёрстке нужно два куска: короткий
     на виду и длинный под «Показать полностью». Делим по первой пустой
     строке: первый абзац - короткий текст, всё дальше - длинный. Нет пустой
     строки - нет и кнопки, карточка просто короче.
     ========================================================================== -->
<section class="section" id="services">
  <div class="shell">

    <div class="rail-head">
      <div class="section-head u-mb-0">
        <h2 class="display h2">Наши услуги</h2>
        <p class="lede">
          Любые формы и виды наружной рекламы: проектирование, дизайн, изготовление,
          монтаж, высотные работы и брендирование транспорта.
        </p>
      </div>
      <?php if (count($services) > 1): ?>
        <div class="rail-controls">
          <button class="rail-btn" type="button" data-rail-prev="services-row" aria-label="Предыдущие услуги">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
          </button>
          <button class="rail-btn" type="button" data-rail-next="services-row" aria-label="Следующие услуги">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
          </button>
        </div>
      <?php endif; ?>
    </div>

    <div class="rail rail--services" id="services-row" data-rail tabindex="0"
         role="region" aria-label="Услуги, прокручивается по горизонтали"
         data-reveal-group>
      <?php foreach (array_values($services) as $i => $s): ?>
        <?php
          [$lead, $more] = split_lead($s['description']);
          $moreId = 'svc-' . (int) $s['id'] . '-more';
        ?>
        <article class="svc" data-reveal>
          <button class="media-btn" type="button"
                  data-lb-src="<?= e(media_url($s['image_path'])) ?>"
                  data-lb-group="services"
                  data-lb-title="<?= e($s['title']) ?>"
                  data-lb-alt="<?= e($s['title']) ?>"
                  <?= $lead !== '' ? 'data-lb-text="' . e($lead) . '"' : '' ?>>
            <span class="media media--tinted svc__media">
              <img src="<?= e(media_url($s['image_path'])) ?>"<?= img_dims($s['image_path']) ?>
                   loading="lazy" decoding="async" alt="<?= e($s['title']) ?>">
              <span class="media-btn__zoom" aria-hidden="true">
                <svg class="icon"><use href="#i-zoom-in"></use></svg>
              </span>
            </span>
            <span class="visually-hidden">Открыть фотографию услуги «<?= e($s['title']) ?>»</span>
          </button>

          <div class="svc__body">
            <div class="svc__head">
              <h3 class="h3 display"><?= e($s['title']) ?></h3>
              <p class="svc__price<?= (float) $s['price'] > 0 ? '' : ' svc__price--ask' ?>"><?= e(service_price($s['price'])) ?></p>
            </div>
            <?php if ($lead !== ''): ?>
              <p class="svc__lead"><?= nl2br(e($lead)) ?></p>
            <?php endif; ?>
            <?php if ($more !== ''): ?>
              <div class="svc__more" id="<?= e($moreId) ?>" aria-hidden="true">
                <div>
                  <?php foreach (preg_split('/\R\s*\R/u', $more) as $para): ?>
                    <p><?= nl2br(e(trim($para))) ?></p>
                  <?php endforeach; ?>
                </div>
              </div>
              <button class="disclosure" type="button" data-disclosure
                      aria-expanded="false" aria-controls="<?= e($moreId) ?>">
                <span data-disclosure-label>Показать полностью</span>
                <svg class="icon" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ==========================================================================
     3. ХОД РАБОТЫ. Семейство раскладки: липкая колонка слева, вертикальный
     рельс с этапами справа. Тексты этапов взяты с сайта заказчика.
     ========================================================================== -->
<section class="section" id="process">
  <div class="shell process__layout">

    <div class="process__aside">
      <h2 class="display h2">Ход работы</h2>
      <p class="lede u-mt-4">
        Весь комплекс работ от проекта до монтажа ведёт одна команда.
        Каждый следующий этап начинается после вашего согласования.
      </p>
      <a class="btn btn--primary" href="#contacts">
        Обсудить проект
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
      </a>
    </div>

    <ol class="process__steps" data-reveal-group>
      <li class="step" data-reveal>
        <span class="step__icon" aria-hidden="true">
          <svg class="icon icon--lg"><use href="#i-pencil-bolt"></use></svg>
        </span>
        <div>
          <h3 class="step__title">Разработка дизайна</h3>
          <p class="step__text">Один из самых важных этапов. Правильно подобранный логотип вывески станет вашей визитной карточкой на многие годы.</p>
        </div>
      </li>
      <li class="step" data-reveal>
        <span class="step__icon" aria-hidden="true">
          <svg class="icon icon--lg"><use href="#i-file-description"></use></svg>
        </span>
        <div>
          <h3 class="step__title">Согласование</h3>
          <p class="step__text">Согласование размещения наружной рекламы с администрацией города обязательно. Поможем подготовить пакет документов и проведём согласование.</p>
        </div>
      </li>
      <li class="step" data-reveal>
        <span class="step__icon" aria-hidden="true">
          <svg class="icon icon--lg"><use href="#i-tools"></use></svg>
        </span>
        <div>
          <h3 class="step__title">Производство</h3>
          <p class="step__text">Собственная производственная база позволяет выполнять работы в короткие сроки. В работе только качественные сертифицированные материалы.</p>
        </div>
      </li>
      <li class="step" data-reveal>
        <span class="step__icon" aria-hidden="true">
          <svg class="icon icon--lg"><use href="#i-crane"></use></svg>
        </span>
        <div>
          <h3 class="step__title">Монтаж</h3>
          <p class="step__text">От качества монтажа зависит не только внешний вид рекламы, но и безопасность окружающих. Монтажники-альпинисты выполнят монтаж и демонтаж любых конструкций.</p>
        </div>
      </li>
      <li class="step" data-reveal>
        <span class="step__icon" aria-hidden="true">
          <svg class="icon icon--lg"><use href="#i-shield-check"></use></svg>
        </span>
        <div>
          <h3 class="step__title">Обслуживание</h3>
          <p class="step__text">Каждый клиент получает гарантию на продукцию сроком на один год. По истечении срока выполняем обслуживание конструкций: ремонт, чистку и замену расходников.</p>
        </div>
      </li>
    </ol>
  </div>
</section>

<?php if ($about): ?>
<!-- ==========================================================================
     О НАС. Семейство раскладки: полоса фотографий во всю ширину и текстовая
     карточка, наезжающая на её нижний край.
     Фотографии приходят из админки, раздел «О нас». Текст и три факта
     свёрстаны здесь: под них в базе полей нет.
     ========================================================================== -->
<section class="section" id="about">
  <div class="shell">
    <div class="section-head">
      <h2 class="display h2">О нас</h2>
      <p class="lede">
        Рекламно-производственная группа «Альтус». Работаем в Екатеринбурге
        и по области.
      </p>
    </div>
  </div>

  <div class="about__band" data-reveal-group>
    <?php foreach ($about as $item): ?>
      <?php
        $src   = $item['image_path'];
        $cap   = trim((string) ($item['title'] ?? '')) ?: 'Фотография производства';
        $text  = trim((string) ($item['description'] ?? ''));
      ?>
      <button class="media-btn" type="button" data-reveal
              data-lb-src="<?= e(media_url($src)) ?>"
              data-lb-group="about"
              data-lb-title="<?= e($cap) ?>"
              data-lb-alt="<?= e($cap) ?>"
              <?= $text !== '' ? 'data-lb-text="' . e($text) . '"' : '' ?>>
        <span class="media media--tinted">
          <img src="<?= e(media_url($src)) ?>"<?= img_dims($src) ?>
               loading="lazy" decoding="async" alt="<?= e($cap) ?>">
          <span class="media-btn__zoom" aria-hidden="true">
            <svg class="icon"><use href="#i-zoom-in"></use></svg>
          </span>
        </span>
      </button>
    <?php endforeach; ?>
  </div>
  <div class="shell">
    <div class="about__card" data-reveal>
      <div class="about__text">
        <p>
          Предлагаем любые формы и виды наружной рекламы: проектирование,
          разработку дизайна, изготовление, монтаж, высотные работы,
          брендирование авто. Создаём световые и несветовые вывески, стелы,
          рекламные щиты, оформляем витрины и интерьеры.
        </p>
        <p>
          Слаженный коллектив компании в кратчайшие сроки и на высоком уровне
          выполнит весь комплекс работ, от проекта до монтажа вашей рекламы.
        </p>
        <p>
          Осуществляем монтаж вывесок, а при необходимости и демонтаж
          конструкции любой степени сложности. К вашим услугам группа
          монтажников-альпинистов с многолетним опытом и допусками
          на выполнение работ на любой высоте.
        </p>
      </div>

      <ul class="about__facts">
        <li class="about__fact">
          <svg class="icon icon--lg" aria-hidden="true"><use href="#i-building-factory-2"></use></svg>
          <div>
            <b>Собственное производство</b>
            <span>Работы выполняются в короткие сроки.</span>
          </div>
        </li>
        <li class="about__fact">
          <svg class="icon icon--lg" aria-hidden="true"><use href="#i-rosette-discount-check"></use></svg>
          <div>
            <b>Высокое качество</b>
            <span>В работе только сертифицированные материалы.</span>
          </div>
        </li>
        <li class="about__fact">
          <svg class="icon icon--lg" aria-hidden="true"><use href="#i-shield-check"></use></svg>
          <div>
            <b>Гарантия на продукцию</b>
            <span>Один год, дальше обслуживание конструкций.</span>
          </div>
        </li>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($works): ?>
<!-- ==========================================================================
     НАШИ РАБОТЫ. Горизонтальная лента. Данные из админки, раздел «Наши работы».
     Кадр вертикальный: механизм тот же, что у услуг и видео, но ритм другой,
     и разделы не читаются одним и тем же блоком.

     В базе у работы одно поле описания. Первая строка идёт подписью под
     фотографию, остальное - текстом в лайтбоксе. Если описание в одну
     строку, в лайтбоксе будет она же, и ничего не ломается.
     ========================================================================== -->
<section class="section" id="works">
  <div class="shell">

    <div class="rail-head">
      <div class="section-head u-mb-0">
        <h2 class="display h2">Наши работы</h2>
        <p class="lede">
          Которые говорят сами за себя. Нажмите на фотографию, чтобы рассмотреть
          её крупно и прочитать описание.
        </p>
      </div>
      <?php if (count($works) > 1): ?>
        <div class="rail-controls">
          <button class="rail-btn" type="button" data-rail-prev="works-row" aria-label="Предыдущие работы">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
          </button>
          <button class="rail-btn" type="button" data-rail-next="works-row" aria-label="Следующие работы">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
          </button>
        </div>
      <?php endif; ?>
    </div>

    <div class="rail rail--works" id="works-row" data-rail tabindex="0"
         role="region" aria-label="Наши работы, прокручивается по горизонтали"
         data-reveal-group>
      <?php foreach (array_values($works) as $i => $w): ?>
        <?php
          $name = first_line($w['description']) ?: 'Работа';
          $rest = rest_lines($w['description']);
          $full = trim($w['description']) !== '' ? trim($w['description']) : $name;
        ?>
        <figure class="work" data-reveal>
          <button class="media-btn" type="button"
                  data-lb-src="<?= e(media_url($w['image_path'])) ?>"
                  data-lb-group="works"
                  data-lb-title="<?= e($name) ?>"
                  data-lb-alt="<?= e($name) ?>"
                  data-lb-text="<?= e($rest !== '' ? $rest : $full) ?>">
            <span class="media">
              <img src="<?= e(media_url($w['image_path'])) ?>"<?= img_dims($w['image_path']) ?>
                   loading="lazy" decoding="async" alt="<?= e($name) ?>">
              <span class="media-btn__zoom" aria-hidden="true">
                <svg class="icon"><use href="#i-zoom-in"></use></svg>
              </span>
            </span>
          </button>
          <figcaption class="work__caption">
            <span class="work__name"><?= e($name) ?></span>
            <?php if ($rest !== ''): ?>
              <span class="work__kind"><?= e(first_line($rest)) ?></span>
            <?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<?php if ($videos): ?>
<!-- ==========================================================================
     ВИДЕО. Семейство раскладки: равномерная сетка 16 к 9. У видео один
     естественный формат кадра, поэтому плитки одинаковые, в отличие
     от мозаики работ. Данные из админки, раздел «Видео».

     Постера в базе нет, отдельного поля под него в таблице videos тоже.
     Поэтому плиткой стоит сам ролик с preload="metadata" и якорем #t=0.5:
     браузер тянет только заголовок файла и рисует кадр с половины секунды.
     Первый кадр часто чёрный, поэтому именно половина секунды, а не ноль.
     Если в таблицу добавят poster_path, здесь достаточно вернуть <img>.
     ========================================================================== -->
<section class="section" id="video">
  <div class="shell">

    <div class="rail-head">
      <div class="section-head u-mb-0">
        <h2 class="display h2">Видео</h2>
        <p class="lede">
          Видеоотчёты с объектов и съёмка производства.
        </p>
      </div>
      <?php if (count($videos) > 1): ?>
        <div class="rail-controls">
          <button class="rail-btn" type="button" data-rail-prev="video-row" aria-label="Предыдущие ролики">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
          </button>
          <button class="rail-btn" type="button" data-rail-next="video-row" aria-label="Следующие ролики">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
          </button>
        </div>
      <?php endif; ?>
    </div>

    <div class="rail rail--video" id="video-row" data-rail tabindex="0"
         role="region" aria-label="Видео, прокручивается по горизонтали"
         data-reveal-group>
      <?php foreach ($videos as $v): ?>
          <?php
            $name = trim((string) ($v['title'] ?? '')) ?: 'Видео';
            $meta = trim((string) ($v['subtitle'] ?? ''));
            $src  = media_url($v['video_path']);
          ?>
          <article class="vid" data-reveal>
            <button class="media-btn" type="button"
                    data-lb-video="<?= e($src) ?>"
                    data-lb-group="video"
                    data-lb-title="<?= e($name) ?>"
                    data-lb-alt="<?= e($name) ?>"
                    <?= $meta !== '' ? 'data-lb-text="' . e($meta) . '"' : '' ?>>
              <span class="media">
                <video class="vid__frame" src="<?= e($src) ?>#t=0.5"
                       preload="metadata" muted playsinline tabindex="-1"
                       aria-hidden="true"></video>
                <span class="vid__play" aria-hidden="true">
                  <span><svg class="icon icon--filled"><use href="#i-player-play"></use></svg></span>
                </span>
              </span>
              <span class="visually-hidden">Смотреть видео «<?= e($name) ?>»</span>
            </button>
            <div class="vid__caption">
              <span class="vid__name"><?= e($name) ?></span>
              <?php if ($meta !== ''): ?>
                <span class="vid__meta"><?= e($meta) ?></span>
              <?php endif; ?>
            </div>
          </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<?php if ($reviews): ?>
<!-- ==========================================================================
     ОТЗЫВЫ. Семейство раскладки: горизонтальный ряд со scroll-snap.
     Данные из админки, раздел «Отзывы».

     Скан письма открывается в том же лайтбоксе, если это картинка. PDF
     лайтбокс показать не может, его открывает сам браузер, поэтому для
     PDF здесь обычная ссылка в новой вкладке, а не кнопка.
     ========================================================================== -->
<section class="section" id="reviews">
  <div class="shell">

    <div class="rail-head">
      <div class="section-head u-mb-0">
        <h2 class="display h2">Отзывы</h2>
        <p class="lede">Лучшая характеристика нашей компании.</p>
      </div>
      <?php if (count($reviews) > 1): ?>
        <div class="rail-controls">
          <button class="rail-btn" type="button" data-rail-prev="reviews-row" aria-label="Предыдущий отзыв">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
          </button>
          <button class="rail-btn" type="button" data-rail-next="reviews-row" aria-label="Следующий отзыв">
            <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
          </button>
        </div>
      <?php endif; ?>
    </div>

    <div class="rail rail--reviews" id="reviews-row" data-rail tabindex="0"
         role="region" aria-label="Отзывы клиентов, прокручивается по горизонтали">
      <?php foreach ($reviews as $r): ?>
        <?php
          $doc  = trim((string) ($r['document_path'] ?? ''));
          /* Автор: строка 1 - имя или компания, строка 2 - должность.
             Одна строка - подпись просто без должности. */
          $who  = first_line($r['author']);
          $role = first_line(rest_lines($r['author']));
        ?>
        <figure class="review">
          <svg class="icon icon--lg review__mark" aria-hidden="true"><use href="#i-quote"></use></svg>
          <blockquote class="review__text"><?= e($r['body']) ?></blockquote>
          <figcaption class="review__foot">
            <span class="review__who">
              <span class="review__name"><?= e($who) ?></span>
              <?php if ($role !== ''): ?>
                <span class="review__role"><?= e($role) ?></span>
              <?php endif; ?>
            </span>
            <?php if ($doc !== '' && is_image_file($doc)): ?>
              <button class="btn btn--ghost btn--sm" type="button"
                      data-lb-src="<?= e(media_url($doc)) ?>"
                      data-lb-title="Благодарственное письмо, <?= e($who) ?>"
                      data-lb-alt="Скан благодарственного письма"
                      data-lb-text="Оригинал письма.">
                Прочитать оригинал
              </button>
            <?php elseif ($doc !== ''): ?>
              <a class="btn btn--ghost btn--sm" href="<?= e(media_url($doc)) ?>"
                 target="_blank" rel="noopener">
                Прочитать оригинал
              </a>
            <?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<!-- ==========================================================================
     БЛОГ. Семейство раскладки: одна крупная запись и столбец из мелких.
     Данные из админки, раздел «Блог», только опубликованные.
     Если запись одна, крупная занимает всю ширину и столбца просто нет.
     ========================================================================== -->
<section class="section" id="blog">
  <div class="shell">

    <div class="section-head">
      <h2 class="display h2">Блог</h2>
      <p class="lede">
        Разбираем, из чего складывается цена, чем отличаются материалы
        и что стоит проверить до монтажа.
      </p>
    </div>

    <?php
      $list    = array_values($posts);
      $feature = array_shift($list);
    ?>
    <div class="blog__grid<?= $list ? '' : ' blog__grid--solo' ?>" data-reveal-group>

      <a class="post post--feature" href="<?= base_url('/blog/' . rawurlencode($feature['slug'])) ?>" data-reveal>
        <span class="media media--tinted post__media">
          <img src="<?= e(media_url($feature['preview_image_path'])) ?>"<?= img_dims($feature['preview_image_path']) ?>
               loading="lazy" decoding="async" alt="<?= e($feature['title']) ?>">
        </span>
        <span>
          <time class="post__date" datetime="<?= e(iso_date($feature['created_at'])) ?>"><?= e(ru_date($feature['created_at'])) ?></time>
          <h3 class="post__title display u-mt-2"><?= e($feature['title']) ?></h3>
          <?php if (trim((string) $feature['excerpt']) !== ''): ?>
            <p class="post__excerpt u-mt-3"><?= e($feature['excerpt']) ?></p>
          <?php endif; ?>
        </span>
      </a>

      <?php if ($list): ?>
        <div class="blog__side">
          <?php foreach ($list as $p): ?>
            <a class="post post--row" href="<?= base_url('/blog/' . rawurlencode($p['slug'])) ?>" data-reveal>
              <span class="media media--tinted post__media">
                <img src="<?= e(media_url($p['preview_image_path'])) ?>"<?= img_dims($p['preview_image_path']) ?>
                     loading="lazy" decoding="async" alt="<?= e($p['title']) ?>">
              </span>
              <span>
                <time class="post__date" datetime="<?= e(iso_date($p['created_at'])) ?>"><?= e(ru_date($p['created_at'])) ?></time>
                <h3 class="post__title display u-mt-1"><?= e($p['title']) ?></h3>
                <?php if (trim((string) $p['excerpt']) !== ''): ?>
                  <p class="post__excerpt u-mt-2"><?= e($p['excerpt']) ?></p>
                <?php endif; ?>
              </span>
            </a>
          <?php endforeach; ?>

          <div class="blog__foot">
            <a class="link-arrow" href="<?= base_url('/blog') ?>">
              Все новости
              <svg class="icon" aria-hidden="true"><use href="#i-arrow-up-right"></use></svg>
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="blog__foot">
          <a class="link-arrow" href="<?= base_url('/blog') ?>">
            Все новости
            <svg class="icon" aria-hidden="true"><use href="#i-arrow-up-right"></use></svg>
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>
<?php endif; ?>

<!-- ==========================================================================
     9. КОНТАКТЫ. Семейство раскладки: сплит «инфо и форма», под ним карта.
     Телефон и адрес взяты с сайта заказчика.
     ========================================================================== -->
<section class="section" id="contacts">
  <div class="shell">

    <div class="section-head">
      <h2 class="display h2">Контакты</h2>
      <p class="lede">
        Позвоните или оставьте заявку на бесплатный выезд специалиста.
        Посчитаем смету и подберём материалы.
      </p>
    </div>

    <div class="contacts__grid">

      <div>
        <div class="contact-list">

          <div class="contact-item">
            <svg class="icon" aria-hidden="true"><use href="#i-phone"></use></svg>
            <div>
              <p class="contact-item__label">Телефон</p>
              <a class="contact-item__value" href="tel:+79120454444">+7 912 045-44-44</a>
            </div>
          </div>

          <div class="contact-item">
            <svg class="icon" aria-hidden="true"><use href="#i-mail"></use></svg>
            <div>
              <p class="contact-item__label">Почта</p>
              <a class="contact-item__value" href="mailto:scharapov.wadym@yandex.ru">scharapov.wadym@yandex.ru</a>
            </div>
          </div>

          <div class="contact-item">
            <svg class="icon" aria-hidden="true"><use href="#i-map-pin"></use></svg>
            <div>
              <p class="contact-item__label">Адрес</p>
              <p class="contact-item__value">Екатеринбург, улица Данилы Зверева, 23, офис 311</p>
            </div>
          </div>

          <div class="contact-item">
            <svg class="icon" aria-hidden="true"><use href="#i-clock"></use></svg>
            <div>
              <p class="contact-item__label">Режим работы</p>
              <p class="contact-item__value">Будни с 10:00 до 18:00</p>
            </div>
          </div>

        </div>

        <!-- TODO заказчик: подставить ссылки на сообщество ВКонтакте и Telegram.
             На старом сайте их не было. WhatsApp собран из вашего номера телефона,
             проверьте, что мессенджер на нём действительно подключён. -->
        <div class="socials">
          <a class="social" href="#" rel="noopener">
            <svg class="icon" aria-hidden="true"><use href="#i-brand-vk"></use></svg>
            ВКонтакте
          </a>
          <a class="social" href="#" rel="noopener">
            <svg class="icon" aria-hidden="true"><use href="#i-brand-telegram"></use></svg>
            Telegram
          </a>
          <a class="social" href="https://wa.me/79120454444" rel="noopener" target="_blank">
            <svg class="icon" aria-hidden="true"><use href="#i-brand-whatsapp"></use></svg>
            WhatsApp
          </a>
        </div>
      </div>

      <!-- Форма. Label над полем, подсказка под label, ошибка под полем.
           Адрес обработчика ставится в data-endpoint, см. assets/js/form.js -->
      <div class="form-panel">
        <form class="form" data-form novalidate
              data-endpoint="<?= e(base_url('/api/lead')) ?>"
              data-csrf="<?= e(\App\Core\Csrf::token()) ?>">

          <!-- Ловушка для роботов. Спрятана от человека и от скринридера,
               но автозаполнитель её видит и заполняет. Заполнено - заявка
               молча отбрасывается на сервере. Спрятана классом, не inline
               стилем: inline-стилей в разметке на сайте нет. -->
          <div class="form__trap" aria-hidden="true">
            <label for="f-website">Не заполняйте это поле</label>
            <input id="f-website" name="website" type="text" tabindex="-1" autocomplete="off">
          </div>

          <div class="form__row">
            <div class="field">
              <label class="field__label" for="f-name">Имя <span class="req" aria-hidden="true">*</span></label>
              <input class="input" id="f-name" name="name" type="text"
                     autocomplete="name" placeholder="Как к вам обращаться" required>
              <p class="field__error" id="err-name" data-error>
                <svg class="icon" aria-hidden="true"><use href="#i-alert-circle"></use></svg>
                <span data-error-text></span>
              </p>
            </div>

            <div class="field">
              <label class="field__label" for="f-phone">Телефон <span class="req" aria-hidden="true">*</span></label>
              <input class="input" id="f-phone" name="phone" type="tel" inputmode="tel"
                     autocomplete="tel" placeholder="+7 (___) ___-__-__" required>
              <p class="field__error" id="err-phone" data-error>
                <svg class="icon" aria-hidden="true"><use href="#i-alert-circle"></use></svg>
                <span data-error-text></span>
              </p>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="f-contact">Мессенджер или почта</label>
            <p class="field__hint">Необязательно. Напишите, если удобнее отвечать туда.</p>
            <input class="input" id="f-contact" name="contact" type="text"
                   placeholder="Telegram, WhatsApp или адрес почты">
          </div>

          <div class="field">
            <label class="field__label" for="f-message">Что нужно сделать <span class="req" aria-hidden="true">*</span></label>
            <p class="field__hint">Тип конструкции, примерный размер и адрес объекта, если он уже известен.</p>
            <textarea class="textarea" id="f-message" name="message"
                      placeholder="Например: световой короб на фасад кофейни, около 2 метров" required></textarea>
            <p class="field__error" id="err-message" data-error>
              <svg class="icon" aria-hidden="true"><use href="#i-alert-circle"></use></svg>
              <span data-error-text></span>
            </p>
          </div>

          <div class="field">
            <label class="check">
              <input type="checkbox" name="consent" required>
              <span>Согласен на <a href="<?= base_url('/terms') ?>">обработку персональных данных</a>
                и принимаю <a href="<?= base_url('/privacy') ?>">политику конфиденциальности</a>.</span>
            </label>
            <p class="field__error" id="err-consent" data-error>
              <svg class="icon" aria-hidden="true"><use href="#i-alert-circle"></use></svg>
              <span data-error-text></span>
            </p>
          </div>

          <div class="form__status" data-status role="status" aria-live="polite">
            <svg class="icon" aria-hidden="true" data-status-icon><use href="#i-check"></use></svg>
            <span data-status-text></span>
          </div>

          <button class="btn btn--primary btn--block" type="submit" data-submit>
            <svg class="icon btn__spinner" aria-hidden="true"><use href="#i-loader-2"></use></svg>
            <span class="btn__label">Отправить заявку</span>
          </button>

        </form>
      </div>

    </div>

    <!-- Карта подгружается по нажатию. Виджет Яндекса всегда светлый, и
         постоянно открытым он выглядит как белая врезка в тёмной странице,
         а по ТЗ секции не инвертируются. Побочная польза: сторонний iframe
         не приходит на страницу, пока его не попросили. Адрес читается
         и без нажатия, в подписи под картой. -->
    <div class="map">
      <button class="map__cover" type="button"
              data-map-src="https://yandex.ru/map-widget/v1/?text=%D0%95%D0%BA%D0%B0%D1%82%D0%B5%D1%80%D0%B8%D0%BD%D0%B1%D1%83%D1%80%D0%B3%2C%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%94%D0%B0%D0%BD%D0%B8%D0%BB%D1%8B%20%D0%97%D0%B2%D0%B5%D1%80%D0%B5%D0%B2%D0%B0%2C%2023&amp;z=17"
              data-map-title="Карта: Екатеринбург, улица Данилы Зверева, 23">
        <span class="map__pin" aria-hidden="true">
          <svg class="icon" aria-hidden="true"><use href="#i-map-pin"></use></svg>
        </span>
        <span class="map__cta">Показать карту</span>
        <span class="map__sub">Яндекс Карты, откроются здесь же</span>
      </button>
      <p class="map__note">
        Екатеринбург, улица Данилы Зверева, 23, офис 311.
        <a class="map__link" href="https://yandex.ru/maps/?text=%D0%95%D0%BA%D0%B0%D1%82%D0%B5%D1%80%D0%B8%D0%BD%D0%B1%D1%83%D1%80%D0%B3%2C%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%94%D0%B0%D0%BD%D0%B8%D0%BB%D1%8B%20%D0%97%D0%B2%D0%B5%D1%80%D0%B5%D0%B2%D0%B0%2C%2023&amp;z=17" target="_blank" rel="noopener">Проложить маршрут<svg class="icon" aria-hidden="true"><use href="#i-arrow-up-right"></use></svg></a>
      </p>
    </div>

  </div>
</section>
