<?php
/**
 * Content Blocks Pro — AJAX: Demo page generator
 * Creates an Information page with one block per (type × theme) combo.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksDemoPage extends Controller
{
    private const DEMO_KEYWORD = 'cb-demo';
    private const DEMO_TITLE   = 'Content Blocks Demo';
    private const PAGE_ROUTE   = 'catalog/information';

    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = $this->language->get('error_permission');
            $this->respond($json);
            return;
        }

        $action = $this->request->post['action'] ?? 'create';

        try {
            if ($action === 'delete') {
                $json = $this->actionDelete();
            } else {
                $json = $this->actionCreate();
            }
        } catch (\Throwable $e) {
            $json['error'] = $e->getMessage();
        }

        $this->respond($json);
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    private function actionCreate(): array
    {
        $existing = $this->findDemoInformationId();
        if ($existing > 0) {
            // Idempotent — clear existing blocks and re-seed
            $this->load->model('extension/module/oc_kit_content_blocks');
            $this->model_extension_module_oc_kit_content_blocks->removePageBlocks(self::PAGE_ROUTE, $existing);
            $infoId = $existing;
        } else {
            $infoId = $this->createInformationPage();
        }

        $blocks = $this->buildDemoBlocks();

        $this->load->model('extension/module/oc_kit_content_blocks');
        $ids = $this->model_extension_module_oc_kit_content_blocks->saveBlocks([
            'page_route' => self::PAGE_ROUTE,
            'page_id'    => $infoId,
            'blocks'     => $blocks,
        ]);

        // Build description with [cb block_id=N] shortcodes interleaved with type/theme labels
        $descHtml = $this->buildDescriptionShortcodes($blocks, $ids);
        $this->updateInformationDescription($infoId, $descHtml);

        return [
            'success'        => sprintf($this->language->get('text_demo_created'), count($ids)),
            'information_id' => $infoId,
            'view_url'       => HTTP_CATALOG . 'index.php?route=information/information&information_id=' . $infoId,
            'edit_url'       => $this->url->link('catalog/information/edit', 'user_token=' . $this->session->data['user_token'] . '&information_id=' . $infoId, true),
        ];
    }

    private function buildDescriptionShortcodes(array $blocks, array $ids): string
    {
        $html = '<h1>Content Blocks Demo</h1><p>Згенеровано автоматично — приклад кожного типу блоку × кожного дизайну.</p>';
        foreach ($blocks as $i => $b) {
            $bid = (int)($ids[$i] ?? 0);
            if (!$bid) continue;
            $html .= '<hr>'
                  . '<h2>' . htmlspecialchars($b['block_type']) . ' / ' . htmlspecialchars($b['theme']) . '</h2>'
                  . '<p>[cb block_id=' . $bid . ']</p>';
        }
        return $html;
    }

    private function updateInformationDescription(int $infoId, string $descHtml): void
    {
        $this->load->model('catalog/information');
        $info = $this->model_catalog_information->getInformation($infoId);
        if (!$info) return;
        $descriptions = $this->model_catalog_information->getInformationDescriptions($infoId);

        $newDescriptions = [];
        foreach ($descriptions as $lid => $desc) {
            $desc['description']      = $descHtml;
            $desc['meta_h1']          = $desc['meta_h1']          ?? ($desc['title'] ?? '');
            $desc['meta_title']       = $desc['meta_title']       ?? '';
            $desc['meta_description'] = $desc['meta_description'] ?? '';
            $desc['meta_keyword']     = $desc['meta_keyword']     ?? '';
            $newDescriptions[(int)$lid] = $desc;
        }

        // Need keyword + stores + layouts for editInformation
        $stores = $this->model_catalog_information->getInformationStores($infoId);
        $layouts = $this->model_catalog_information->getInformationLayouts($infoId);

        $this->load->model('design/seo_url');
        $kwQ = $this->db->query("SELECT keyword FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $this->db->escape('information_id=' . $infoId) . "' LIMIT 1");
        $keyword = $kwQ->num_rows ? $kwQ->row['keyword'] : self::DEMO_KEYWORD;

        $data = [
            'information_description' => $newDescriptions,
            'sort_order'              => $info['sort_order'] ?? 0,
            'status'                  => $info['status'] ?? 1,
            'top'                     => $info['top']      ?? 0, // ocStore-only
            'bottom'                  => $info['bottom']   ?? 0,
            'centered'                => $info['centered'] ?? 0, // ocStore-only
            'noindex'                 => $info['noindex']  ?? 0, // ocStore-only
            'information_store'       => $stores ?: [0],
            'information_layout'      => $layouts ?: [],
            'keyword'                 => $keyword,
        ];

        $this->model_catalog_information->editInformation($infoId, $data);
    }

    private function actionDelete(): array
    {
        $infoId = $this->findDemoInformationId();
        if (!$infoId) {
            return ['error' => $this->language->get('text_demo_not_found')];
        }

        // Delete blocks first
        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->model_extension_module_oc_kit_content_blocks->removePageBlocks(self::PAGE_ROUTE, $infoId);

        // Delete information page
        $this->load->model('catalog/information');
        $this->model_catalog_information->deleteInformation($infoId);

        return ['success' => $this->language->get('text_demo_deleted')];
    }

    // ─── Information page ────────────────────────────────────────────────────

    private function findDemoInformationId(): int
    {
        $q = $this->db->query(
            "SELECT `query` FROM `" . DB_PREFIX . "seo_url`
             WHERE `keyword` = '" . $this->db->escape(self::DEMO_KEYWORD) . "' LIMIT 1"
        );
        if (!$q->num_rows) return 0;
        $query = (string)$q->row['query'];
        if (strpos($query, 'information_id=') === false) return 0;
        return (int)str_replace('information_id=', '', $query);
    }

    private function createInformationPage(): int
    {
        $this->load->model('catalog/information');
        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();

        $descriptions = [];
        foreach ($languages as $lang) {
            $descriptions[(int)$lang['language_id']] = [
                'title'            => self::DEMO_TITLE,
                'description'      => '<p>Тестова сторінка з усіма типами та дизайнами Content Blocks Pro. Згенерована автоматично.</p>',
                'meta_title'       => self::DEMO_TITLE,
                'meta_description' => 'Demo page — Content Blocks Pro',
                'meta_keyword'     => '',
                'meta_h1'          => self::DEMO_TITLE, // ocStore-only
            ];
        }

        $data = [
            'information_description' => $descriptions,
            'sort_order'              => 0,
            'status'                  => 1,
            'top'                     => 0, // ocStore-only
            'bottom'                  => 0,
            'centered'                => 0, // ocStore-only
            'noindex'                 => 0, // ocStore-only
            'information_store'       => [0],
            'information_layout'      => [],
            'keyword'                 => self::DEMO_KEYWORD,
        ];

        return (int)$this->model_catalog_information->addInformation($data);
    }

    // ─── Demo blocks builder ─────────────────────────────────────────────────

    private function buildDemoBlocks(): array
    {
        $themes  = $this->scanAllThemes();
        $images  = $this->pickRandomImages(20);
        $prodIds = $this->pickEntityIds('product', 8);
        $catIds  = $this->pickEntityIds('category', 6);
        $blogIds = $this->pickEntityIds('blog_article', 6);

        $blocks  = [];
        $sort    = 0;

        foreach ($themes as $type => $themeList) {
            foreach ($themeList as $themeName) {
                $blocks[] = $this->buildBlockForType($type, $themeName, ++$sort, $images, $prodIds, $catIds, $blogIds);
            }
        }

        return $blocks;
    }

    private function buildBlockForType(
        string $type,
        string $theme,
        int $sortOrder,
        array $images,
        array $prodIds,
        array $catIds,
        array $blogIds
    ): array {
        $base = [
            'block_id'    => 0,
            'block_type'  => $type,
            'block_name'  => $type . ' — ' . $theme,
            'theme'       => $theme,
            'status'      => 1,
            'sort_order'  => $sortOrder,
            'is_global'   => 1, // required: render.php only renders global blocks via shortcodes
            'custom_class' => '',
            'custom_css'  => [],
            'params'      => [],
            'rows'        => [],
            'elements'    => [],
        ];

        switch ($type) {
            case 'grid':
                $base['rows'] = [
                    [
                        'sort_order' => 0, 'custom_css' => [], 'params' => [],
                        'cols' => [
                            ['width' => 6, 'sort_order' => 0, 'custom_css' => [], 'params' => [],
                             'elements' => [$this->elText(0, 'Колонка 1', '<h3>Заголовок</h3><p>Демонстраційний текст у першій колонці.</p>')]],
                            ['width' => 6, 'sort_order' => 1, 'custom_css' => [], 'params' => [],
                             'elements' => [$this->elImage(0, $images[array_rand($images)] ?? '', 'demo')]],
                        ],
                    ],
                ];
                break;

            case 'video':
                $base['elements'] = [$this->elVideo(0, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ')];
                break;

            case 'accordion':
                $base['rows'] = [[
                    'sort_order' => 0, 'custom_css' => [], 'params' => [],
                    'cols' => [
                        $this->accCol(0, 0, 'Питання 1', '<p>Відповідь на перше питання.</p>'),
                        $this->accCol(0, 1, 'Питання 2', '<p>Відповідь на друге питання.</p>'),
                        $this->accCol(0, 2, 'Питання 3', '<p>Відповідь на третє питання.</p>'),
                    ],
                ]];
                break;

            case 'faq':
                $base['elements'] = [
                    $this->elFaq(0, 'Як зробити замовлення?', 'Додайте товари у кошик і оформіть замовлення.'),
                    $this->elFaq(1, 'Які способи оплати?', 'Готівка, картка, переказ.'),
                    $this->elFaq(2, 'Доставка по Україні?', 'Так, Нова Пошта і Укрпошта.'),
                ];
                break;

            case 'reviews':
                $base['elements'] = [
                    $this->elReview(0, 'Олена', 5, 'Все супер, рекомендую!'),
                    $this->elReview(1, 'Андрій', 4, 'Хороший товар, доставка швидка.'),
                    $this->elReview(2, 'Марія', 5, 'Якість на висоті, дякую!'),
                ];
                break;

            case 'products_carousel':
                $base['params'] = ['per_view' => 4, 'show_price' => 1, 'show_button' => 1];
                $base['elements'] = [];
                foreach (array_slice($prodIds, 0, 6) as $i => $pid) {
                    $base['elements'][] = $this->elCarouselProduct($i, (int)$pid);
                }
                break;

            case 'images_carousel':
                $base['elements'] = [];
                foreach (array_slice($images, 0, 6) as $i => $img) {
                    $base['elements'][] = $this->elCarouselImage($i, $img);
                }
                break;

            case 'product':
                $base['params'] = ['show_price' => 1, 'show_button' => 1];
                $base['elements'] = [$this->elProductItem(0, (int)($prodIds[0] ?? 0))];
                break;

            case 'categories':
                $base['elements'] = [];
                foreach (array_slice($catIds, 0, 6) as $i => $cid) {
                    $base['elements'][] = $this->elCategoryItem($i, (int)$cid);
                }
                break;

            case 'blog_article':
                $base['elements'] = [];
                foreach (array_slice($blogIds, 0, 4) as $i => $aid) {
                    $base['elements'][] = $this->elArticleItem($i, (int)$aid);
                }
                break;
        }

        return $base;
    }

    // ─── Element factories ───────────────────────────────────────────────────

    private function elText(int $sort, string $title, string $html): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = ['content' => $html];
        }
        return [
            'element_id' => 0, 'element_type' => 'text', 'sort_order' => $sort,
            'params' => ['tag' => 'div'], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elImage(int $sort, string $image, string $alt): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = ['image' => $image, 'alt' => $alt, 'url' => ''];
        }
        return [
            'element_id' => 0, 'element_type' => 'image', 'sort_order' => $sort,
            'params' => [], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elVideo(int $sort, string $url, string $videoId): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = [
                'url' => $url, 'video_id' => $videoId,
                'thumb' => 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg',
            ];
        }
        return [
            'element_id' => 0, 'element_type' => 'video', 'sort_order' => $sort,
            'params' => ['autoplay' => 0, 'vertical' => 0], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function accCol(int $colId, int $sort, string $title, string $bodyHtml): array
    {
        $titleByLang = [];
        foreach ($this->getLanguageIds() as $lid) {
            $titleByLang[$lid] = $title;
        }
        return [
            'width' => 0, 'sort_order' => $sort,
            'custom_css' => [], 'params' => ['title' => $titleByLang],
            'elements' => [$this->elText(0, $title, $bodyHtml)],
        ];
    }

    private function elFaq(int $sort, string $q, string $a): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = ['question' => $q, 'answer' => $a];
        }
        return [
            'element_id' => 0, 'element_type' => 'faq_item', 'sort_order' => $sort,
            'params' => [], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elReview(int $sort, string $author, int $rating, string $text): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = ['content' => $text];
        }
        return [
            'element_id' => 0, 'element_type' => 'reviews_item', 'sort_order' => $sort,
            'params' => ['author' => $author, 'rating' => $rating], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elCarouselProduct(int $sort, int $productId): array
    {
        return [
            'element_id' => 0, 'element_type' => 'carousel_product', 'sort_order' => $sort,
            'params' => ['product_id' => $productId], 'data' => [],
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elCarouselImage(int $sort, string $image): array
    {
        $langData = [];
        foreach ($this->getLanguageIds() as $lid) {
            $langData[$lid] = ['image' => $image, 'alt' => 'demo', 'url' => ''];
        }
        return [
            'element_id' => 0, 'element_type' => 'carousel_image', 'sort_order' => $sort,
            'params' => [], 'data' => $langData,
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elProductItem(int $sort, int $productId): array
    {
        return [
            'element_id' => 0, 'element_type' => 'product_item', 'sort_order' => $sort,
            'params' => ['product_id' => $productId], 'data' => [],
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elCategoryItem(int $sort, int $categoryId): array
    {
        return [
            'element_id' => 0, 'element_type' => 'categories_item', 'sort_order' => $sort,
            'params' => ['category_id' => $categoryId], 'data' => [],
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    private function elArticleItem(int $sort, int $articleId): array
    {
        return [
            'element_id' => 0, 'element_type' => 'blog_article_item', 'sort_order' => $sort,
            'params' => ['article_id' => $articleId], 'data' => [],
            'custom_class' => '', 'custom_css' => [],
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function scanAllThemes(): array
    {
        // Theme list rarely changes; cache for the request lifetime AND in OC
        // cache for 1h so the demo-page endpoint doesn't re-glob on every hit.
        static $memo = null;
        if ($memo !== null) return $memo;

        $cached = $this->cache->get('cb_demo_themes');
        if (is_array($cached)) {
            return $memo = $cached;
        }

        $base = DIR_CATALOG . 'view/theme/default/template/oc_kit_content_blocks';
        if (!is_dir($base)) return $memo = [];

        $result = [];
        foreach (glob($base . '/*', GLOB_ONLYDIR) as $typeDir) {
            $type = basename($typeDir);
            if (substr($type, 0, 1) === '_') continue;
            $themes = [];
            foreach (glob($typeDir . '/*', GLOB_ONLYDIR) as $themeDir) {
                if (file_exists($themeDir . '/theme.twig')) {
                    $themes[] = basename($themeDir);
                }
            }
            if ($themes) $result[$type] = $themes;
        }

        $this->cache->set('cb_demo_themes', $result);
        return $memo = $result;
    }

    private function pickRandomImages(int $count): array
    {
        // Avoid re-scanning DIR_IMAGE/catalog/ recursively on every request —
        // cache the candidate pool, then sample from it.
        static $memo = null;
        if ($memo === null) {
            $cached = $this->cache->get('cb_demo_images');
            if (is_array($cached)) {
                $memo = $cached;
            } else {
                $memo = [];
                $base = DIR_IMAGE . 'catalog/';
                if (is_dir($base)) {
                    $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
                    foreach ($iter as $f) {
                        if (!$f->isFile()) continue;
                        $ext = strtolower($f->getExtension());
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;
                        $memo[] = 'catalog/' . ltrim(str_replace($base, '', $f->getPathname()), '/');
                        if (count($memo) >= 200) break;
                    }
                }
                $this->cache->set('cb_demo_images', $memo);
            }
        }

        if (count($memo) > $count) {
            $pool = $memo;
            shuffle($pool);
            return array_slice($pool, 0, $count);
        }
        return $memo;
    }

    private function pickEntityIds(string $entity, int $count): array
    {
        $sql = '';
        switch ($entity) {
            case 'product':      $sql = "SELECT product_id  AS id FROM `" . DB_PREFIX . "product`  WHERE status = 1 ORDER BY RAND() LIMIT " . (int)$count; break;
            case 'category':     $sql = "SELECT category_id AS id FROM `" . DB_PREFIX . "category` WHERE status = 1 ORDER BY RAND() LIMIT " . (int)$count; break;
            case 'blog_article':
                if (!$this->tableExists(DB_PREFIX . 'blog_article')) return [];
                $sql = "SELECT blog_article_id AS id FROM `" . DB_PREFIX . "blog_article` WHERE status = 1 ORDER BY RAND() LIMIT " . (int)$count;
                break;
        }
        if (!$sql) return [];
        $q = $this->db->query($sql);
        $out = [];
        foreach ($q->rows as $r) $out[] = (int)$r['id'];
        return $out;
    }

    private function tableExists(string $name): bool
    {
        $q = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($name) . "'");
        return $q->num_rows > 0;
    }

    private $langIdsCache = null;
    private function getLanguageIds(): array
    {
        if ($this->langIdsCache === null) {
            $this->load->model('localisation/language');
            $ids = [];
            foreach ($this->model_localisation_language->getLanguages() as $l) {
                $ids[] = (int)$l['language_id'];
            }
            $this->langIdsCache = $ids;
        }
        return $this->langIdsCache;
    }

    private function respond(array $json): void
    {
        \OcKit\ContentBlocks\ContentBlocks::json($this->response, $json);
    }
}
