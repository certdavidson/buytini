# buytini — продакшн-верстка

Чистий статичний HTML + SCSS + ванільний JS, підготовлений під натягування на OpenCart.

## Структура

```
production/
├─ *.html                  Сторінки (index, catalog, product, cart, checkout, …)
├─ partials/               Еталонні спільні блоки (продубльовані в кожну сторінку)
│  ├─ header.html          Шапка: topbar, пошук, дропдауни, кошик, мега-меню, модалка входу
│  ├─ footer.html
│  └─ account-nav.html     Сайдбар кабінету
├─ scss/                   Джерела стилів
│  ├─ parts/               Партіали за секціями (токени → база → компоненти → сторінки)
│  │                       _00-base, _01-tokens, _02-reset … _05-buttons, _06-forms,
│  │                       _07..15-header/footer, _40-product-card, _41-filters,
│  │                       _44..67-сторінки (catalog, product, home, cart, checkout, …)
│  └─ main.scss            Точка збірки — імпортує всі партіали по черзі
├─ css/
│  └─ main.css             Скомпільований CSS (на нього посилаються всі сторінки)
├─ js/
│  └─ main.js              UI-інтерактив (без рендеру даних)
└─ images/
   └─ buytini-logo.svg
```

## Стилі

- Єдиний стильовий файл — `css/main.css`, скомпільований з `scss/main.scss`.
- Партіали в `scss/parts/` розбито за секціями й підключено по порядку в `main.scss`,
  тож `sass scss/main.scss css/main.css` дає байт-у-байт той самий `main.css`.
- **Презентаційних інлайн-стилів немає.** Інлайн у розмітці лишився **лише**
  `style="background-image:url(...)"` на медіа-блоках — це контент-дані
  (на OpenCart → `style="background-image:url({{ image }})"`).

## Збірка стилів

```bash
sass scss/main.scss css/main.css         # одноразово
sass --watch scss/main.scss css/main.css # у розробці
```

Правки вносьте у партіали `scss/parts/` і перекомпілюйте.

## Конвенції

- **BEM**: `block__element--modifier` (напр. `product-card__price--sale`, `mega__cat.is-active`).
- **Стани через клас** `.is-active`, `.is-open`, `.is-checked`, `.is-on` — їх перемикає `js/main.js`.
- **Токени** — CSS custom properties у `:root` (`var(--accent)`, `var(--radius-md)`, …).
  Темна тема — `:root[data-theme="dark"]`; перемикач у шапці зберігає вибір у `localStorage`.
- Жодних утиліт-класів виду `.uN` — лише логічні імена блоків та елементів.

## JS (`js/main.js`)

Лише UI-інтерактив через делегування подій і `data-`атрибути — без рендеру даних:
тема, дропдауни (мова/валюта/акаунт), мега-меню каталогу, міні-кошик, тости (`window.buytiniToast`),
сортування, перемикач сітка/список, фільтри, FAQ-акордеон, таби, галерея товару + лайтбокс,
модалки (`data-modal-open="name"` → `#modal-name`), калькулятор доставки. Підключається в кінці
`<body>`: `<script src="js/main.js" defer></script>`.

## Натягування на OpenCart

- Картки товарів у каталозі/списках — **статична розмітка прямо в коді сторінки** (не генерується JS).
  Картка `.product-card` — еталон для циклу `{% for product in products %}`.
- Місця для динамічних даних (заголовки, ціни, лічильники, бейджі, статуси) підставляються Twig-плейсхолдерами.
- Зображення товарів/банерів — наразі плейсхолдери з Unsplash; заміняються на реальні шляхи OpenCart
  (інлайн `background-image:url(...)` → `url({{ image }})`).
- Спільні блоки (`header.html`, `footer.html`, `account-nav.html`) винесені в `partials/` як еталон,
  а на сторінках продубльовані — зручно різати на `header.twig` / `footer.twig`.
