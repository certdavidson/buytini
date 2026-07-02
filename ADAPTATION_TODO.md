# Адаптація `prod/` → тема `catalog/view/theme/default`

Процес-файл: натягування статичного прототипу (`prod/*.html`) на OpenCart 3-шаблони.
Оновлюється під час роботи. Легенда статусів: ✅ зроблено · ⬜ standard, в черзі · ⏭️ кастом — пропущено (див. причину).

## Конвенції адаптації (Phase 1 — структура + локальні асети)

1. **Обгортка.** Тіло сторінки (унікальний `<main>…</main>` + її модалки) переноситься в цільовий
   OC-шаблон між `{{ header }}` і `{{ footer }}`. `<!DOCTYPE>/<head>/<header>/<footer>` з prod
   **не** переносяться — їх дає `common/header.twig` + `common/footer.twig`.
2. **Асети — лише локальні** (зроблено, див. нижче). Жодних CDN/Google Fonts.
3. **Зображення-дані:** інлайн `style="background-image:url(...)"` лишається як точка даних
   (на бойовому → `url({{ image }})`).
4. **Відносні `.html`-лінки** → OC-роути там, де є очевидний відповідник (home → `{{ home }}`/`{{ continue }}`).
   Глибоке зв'язування лінків і **підстановка реальних даних OC (цикли/змінні) — Phase 2**, окремо.
   Phase 1 лишає контент статичним (як вже зроблено в `product.twig`).
5. **Кастом** (oc-kit модулі, лендінги без OC-аналога) — НЕ чіпаємо, фіксуємо тут зі статусом ⏭️.

## Локалізація асетів — ✅ зроблено

Винесено з CDN у `catalog/view/theme/default/assets/`, `header.twig` оновлено:
- **Geologica** → `fonts/geologica/geologica.css` + `fonts/geologica/files/*.woff2` (6 субсетів, варіативний).
- **Swiper 11** → `vendor/swiper/swiper-bundle.min.{css,js}`.
- **flag-icons** → `vendor/flag-icons/flag-icons.min.css` + `flags/4x3/{de,es,eu,gb,jp,kr,nl,ua,us}.svg`
  (мінімальний набір під реально вживані прапори; додавати нові — класти svg + рядок у css).

## Standard — адаптувати в OC-шаблон

| prod | → OC template | статус |
|---|---|---|
| index.html | common/home.twig | ✅ |
| product.html | product/product.twig | ✅ |
| not-found.html | error/not_found.twig | ✅ |
| maintenance.html | common/maintenance.twig | ✅ |
| catalog.html | product/category.twig | ⬜ |
| search.html | product/search.twig | ⬜ |
| sale.html | product/special.twig | ⬜ |
| brands.html | product/manufacturer_list.twig | ⬜ |
| brand.html | product/manufacturer_info.twig | ⬜ |
| compare.html | product/compare.twig | ⬜ |
| cart.html | checkout/cart.twig | ⬜ |
| checkout.html | checkout/checkout.twig | ⬜ |
| order-success.html | common/success.twig | ⬜ |
| login.html | account/login.twig | ⬜ |
| register.html | account/register.twig | ⬜ |
| forgot.html | account/forgotten.twig | ⬜ |
| account.html | account/account.twig | ⬜ |
| profile.html | account/edit.twig | ⬜ |
| addresses.html | account/address_list.twig (+ address_form.twig) | ⬜ |
| orders.html | account/order_list.twig | ⬜ |
| order-detail.html | account/order_info.twig | ⬜ |
| wishlist.html | account/wishlist.twig | ⬜ |
| contacts.html | information/contact.twig | ⬜ |
| info.html | information/information.twig | ⬜ |
| sitemap.html | information/sitemap.twig | ⬜ |

## ⏭️ Кастом — пропущено (немає стокового OC-аналога / oc-kit-модуль)

Потребують власних модулів/контролерів або це бекендні фічі oc-kit. Дизайн є в `prod/`, натягувати —
коли робитимемо відповідний модуль.

- **Викуп (oc-kit):** buyout.html, buyout-detail.html, buyout-orders.html, order-link.html
- **Калькулятор доставки (oc-kit):** calculator.html
- **Блог (oc-kit):** blog.html, article.html
- **Бонуси / промокоди:** bonuses.html, promocodes.html
- **Партнери:** partners.html, partner.html, our-partners.html
- **Бренд-стори:** stores.html, store.html
- **Таблиці розмірів:** size-chart.html, size-charts.html
- **Відгуки (окремі сторінки):** reviews.html, leave-reviews.html, review-expired.html
- **SMS-вхід (oc-kit):** login-sms.html
- **Варіанти головної (A/B/C/D дизайн):** index-b.html, index-c.html, index-d.html
- **Стан «товар недоступний»:** product-unavailable.html (стан product/product — фолдиться в логіку product)

### Потребують рішення (CMS-сторінка vs кастомний лендінг)

Мають бекендний аналог `information/information`, але в prod — беспонний лендінг-дизайн.
Вирішити: натягувати як CMS-сторінку чи робити окремий модуль.

- delivery.html, how-it-works.html, services.html, support.html

## Phase 2 (після структури) — окремо

- Підстановка реальних даних OC: цикли товарів/категорій, ціни, опції, пагінація, breadcrumbs.
- Повне зв'язування навігаційних лінків (`.html` → `{{ ... }}`).
- Винесення повторюваних блоків (картка товару, рейтинг-зірки) в `include/`.
