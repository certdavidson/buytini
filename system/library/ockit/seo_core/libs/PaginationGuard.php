<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

/**
 * Guards paginated pages (product/category, blog/category, product/search).
 *
 * ТЗ §17 + redirect_last mode added per user feedback.
 *
 * Modes (config: module_oc_kit_seo_core_pagination_mode):
 *   off            — do nothing for invalid (out-of-range) pages
 *   404            — return HTTP 404 + error view
 *   robots         — keep showing the page but emit noindex (header/meta toggles)
 *   redirect_last  — 301 redirect to the last valid page (or page-1 dropped if 1)
 *
 * "Empty page" = page > 1 AND ($page - 1) * $limit >= $total. We cannot rely on
 * "$total === 0" because $total is store-wide for the filter — it's only zero
 * when the WHOLE category is empty, not when the current page is past the end.
 */
class PaginationGuard
{
    private $config;
    private $response;
    private $document;

    private $registry;

    public function __construct($config, $response, $document = null, $registry = null)
    {
        $this->config   = $config;
        $this->response = $response;
        $this->document = $document;
        $this->registry = $registry;
    }

    /**
     * True if the requested page is past the last valid page.
     */
    public function isEmpty(int $page, int $total, int $limit): bool
    {
        if ($page <= 1) return false;
        if ($limit <= 0) $limit = 20;
        $start = ($page - 1) * $limit;
        return $start >= $total;
    }

    /**
     * Apply guard logic. Call from catalog controllers after $products loaded.
     *
     * @param int $page   current ?page value
     * @param int $total  total records matching current filter (NOT current page)
     * @param int $limit  per-page limit (defaults from config_catalog_limit)
     */
    public function apply(int $page, int $total, int $limit = 0): void
    {
        if ($page <= 1) return;

        if ($limit <= 0) {
            $limit = (int)$this->config->get('config_catalog_limit') ?: 20;
        }

        $mode = (string)($this->config->get('module_oc_kit_seo_core_pagination_mode') ?? 'off');

        // Invalid (out-of-range) pages
        if ($this->isEmpty($page, $total, $limit)) {
            if ($mode === '404') {
                $this->render404();
                return;
            }
            if ($mode === 'redirect_last') {
                $lastPage = max(1, (int)ceil($total / $limit));
                $this->redirectToPage($lastPage);
                return;
            }
            if ($mode === 'robots') {
                $applyHeader = (bool)$this->config->get('module_oc_kit_seo_core_pagination_apply_header');
                $applyMeta   = (bool)$this->config->get('module_oc_kit_seo_core_pagination_apply_meta');
                if (!$applyHeader && !$applyMeta) { $applyHeader = true; $applyMeta = true; }
                $this->applyRobotsNoindex($applyHeader, $applyMeta);
            }
        }

        // Noindex for valid pagination pages — independent of the above
        $noindexAll = !empty($this->config->get('module_oc_kit_seo_core_noindex_all_pagination'));
        if ($noindexAll) {
            $fromPage = max(1, (int)($this->config->get('module_oc_kit_seo_core_noindex_from_page') ?: 2));
            if ($page >= $fromPage) {
                $hdr  = (bool)$this->config->get('module_oc_kit_seo_core_noindex_header');
                $meta = (bool)$this->config->get('module_oc_kit_seo_core_noindex_meta');
                // Legacy fallback: if the module only stored `noindex_delivery` (meta|header|both), honour it
                if (!$hdr && !$meta) {
                    $dlv  = (string)$this->config->get('module_oc_kit_seo_core_noindex_delivery') ?: 'meta';
                    $hdr  = in_array($dlv, ['header', 'both'], true);
                    $meta = in_array($dlv, ['meta', 'both'], true);
                }
                $this->applyRobotsNoindex($hdr, $meta);
            }
        }
    }

    /**
     * Add noindex using the chosen delivery mechanisms.
     */
    public function applyRobotsNoindex(bool $header = true, bool $meta = false): void
    {
        if ($header) {
            $this->response->addHeader('X-Robots-Tag: noindex, follow');
        }
        if ($meta) {
            DocumentExtra::addMeta([
                'name'    => 'robots',
                'content' => 'noindex, follow',
            ]);
        }
    }

    /**
     * True if the current request should include `<meta name="robots" content="noindex">`.
     * Called from header.php OCMOD-patch alongside the canonical / hreflang tag injection.
     */
    public function shouldNoindexMeta(int $page, int $total = 1): bool
    {
        if ($page <= 1) return false;

        $mode = (string)($this->config->get('module_oc_kit_seo_core_pagination_mode') ?? 'off');
        if ($mode === 'robots' && $this->isEmpty($page, $total)
            && (bool)$this->config->get('module_oc_kit_seo_core_pagination_apply_meta')) {
            return true;
        }

        if (!empty($this->config->get('module_oc_kit_seo_core_noindex_all_pagination'))) {
            $fromPage = max(1, (int)($this->config->get('module_oc_kit_seo_core_noindex_from_page') ?: 2));
            if ($page >= $fromPage) {
                if ((bool)$this->config->get('module_oc_kit_seo_core_noindex_meta')) return true;
                $dlv = (string)$this->config->get('module_oc_kit_seo_core_noindex_delivery');
                if (in_array($dlv, ['meta', 'both'], true)) return true;
            }
        }

        return false;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function render404(): void
    {
        // 404 status — addHeader so OC's response.output() emits it
        $this->response->addHeader($this->httpProtocol() . ' 404 Not Found');
        http_response_code(404);

        // Render OC's standard error/not_found page (the controller writes
        // its HTML via $this->response->setOutput(...)). Then flush the
        // response and exit so the calling listing controller stops.
        if ($this->registry) {
            $action = new \Action('error/not_found');
            $action->execute($this->registry);
            $this->response->output();
        }
        exit;
    }

    /**
     * Redirect to a given pagination page (or page-less URL if $targetPage <= 1).
     */
    private function redirectToPage(int $targetPage): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = parse_url($uri);
        $path  = $parts['path'] ?? '/';

        $get = [];
        if (!empty($parts['query'])) parse_str($parts['query'], $get);

        if ($targetPage <= 1) {
            unset($get['page']);
        } else {
            $get['page'] = $targetPage;
        }

        $target = $path . (empty($get) ? '' : '?' . http_build_query($get));
        if (method_exists($this->response, 'redirect')) {
            $this->response->redirect($target, 301);
        } else {
            header('Location: ' . $target, true, 301);
        }
        exit;
    }

    private function httpProtocol(): string
    {
        return isset($_SERVER['SERVER_PROTOCOL'])
            ? (string)$_SERVER['SERVER_PROTOCOL']
            : 'HTTP/1.1';
    }
}
