<?php
/**
 * Sitemap Generator — Catalog Controller (Dynamic Mode)
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitSitemapGenerator extends Controller
{
    /**
     * Dynamic sitemap serving.
     *
     * Route: extension/module/oc_kit_sitemap_generator
     * Only active when generation_mode = 'dynamic'.
     *
     * Typical .htaccess rewrite (add before the main OC rewrite):
     *   RewriteRule ^(sitemap[a-zA-Z0-9_\-]*)\.xml$ index.php?route=extension/module/oc_kit_sitemap_generator&filename=$1 [L,QSA]
     *
     * Nginx equivalent (in server block, before the main OC location):
     *   location ~* ^/(sitemap[a-zA-Z0-9_\-]*)\.xml$ {
     *     rewrite ^/(sitemap[a-zA-Z0-9_\-]*)\.xml$ /index.php?route=extension/module/oc_kit_sitemap_generator&filename=$1 last;
     *   }
     */
    public function index(): void
    {
        if (!$this->config->get('module_oc_kit_sitemap_generator_status')) {
            $this->response->addHeader('HTTP/1.1 404 Not Found');
            $this->response->setOutput('Not found');
            return;
        }

        if ($this->config->get('module_oc_kit_sitemap_generator_generation_mode') !== 'dynamic') {
            $this->response->addHeader('HTTP/1.1 404 Not Found');
            $this->response->setOutput('Not found');
            return;
        }

        $filename = (string)($this->request->get['filename'] ?? '');

        // Sanitize: only alphanumeric, hyphens, underscores
        if (!$filename || !preg_match('/^[a-zA-Z0-9_\-]+$/', $filename)) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            $this->response->setOutput('Bad request');
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';

        try {
            // serveXml() sends headers and output directly (header + echo)
            // We must exit afterwards to prevent OC from sending its own output.
            $sg = new \OcKit\SitemapGenerator\SitemapGenerator($this->registry);
            $sg->serveXml($filename . '.xml');
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain');
            echo 'Sitemap error: ' . $e->getMessage();
        }

        exit;
    }
}
