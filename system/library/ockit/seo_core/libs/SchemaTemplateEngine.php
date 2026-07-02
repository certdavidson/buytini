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
 * Minimal Handlebars-like template engine for custom Schema.org JSON-LD.
 *
 * Supported syntax:
 *   {{foo.bar}}              — dot-path lookup in the context
 *   {{#each items}}…{{/each}} — iterate (with {{@index}}, {{@last}}, {{.}})
 *   {{#if cond}}…{{/if}}      — truthy check (also {{#unless}}…{{/unless}})
 *   {{^unless @last}},{{/unless}}  — negation
 *
 * All scalar substitutions are HTML-escaped via htmlspecialchars.
 */
class SchemaTemplateEngine
{
    public function render(string $template, array $context): string
    {
        // #each
        $template = $this->renderEach($template, $context);
        // #if / #unless
        $template = $this->renderConditionals($template, $context);
        // {{var}}
        $template = $this->renderVars($template, $context);
        return $template;
    }

    public function buildContext(string $route, array $get, int $languageId, $config = null): array
    {
        $ctx = [
            'get'    => $get,
            'route'  => $route,
            'config' => [
                'store_name' => $config ? (string)$config->get('config_name') : '',
                'url'        => $config ? (string)$config->get('config_url')  : '',
            ],
            'page' => [
                'url'      => isset($_SERVER['REQUEST_URI'])
                    ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI'])
                    : '',
                'language' => $languageId,
            ],
        ];
        return $ctx;
    }

    /**
     * Pre-render validation. Returns [] on success or array of error strings.
     */
    public function validate(string $template): array
    {
        $errors = [];
        $eachO = preg_match_all('/\{\{\s*#each\b/', $template);
        $eachC = preg_match_all('/\{\{\s*\/each\s*\}\}/', $template);
        if ($eachO !== $eachC) $errors[] = "Unbalanced {{#each}}: {$eachO}/{$eachC}";

        $ifO = preg_match_all('/\{\{\s*#if\b/', $template);
        $ifC = preg_match_all('/\{\{\s*\/if\s*\}\}/', $template);
        if ($ifO !== $ifC) $errors[] = "Unbalanced {{#if}}: {$ifO}/{$ifC}";

        if (strpos($template, '{{') === false) {
            json_decode($template);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON: ' . json_last_error_msg();
            }
        }
        return $errors;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function renderEach(string $tpl, array $ctx): string
    {
        return preg_replace_callback(
            '/\{\{\s*#each\s+([\w\.]+)\s*\}\}(.*?)\{\{\s*\/each\s*\}\}/s',
            function ($m) use ($ctx) {
                $collection = $this->lookup($m[1], $ctx);
                if (!is_array($collection)) return '';
                $inner = $m[2];
                $out = [];
                $total = count($collection);
                $i = 0;
                foreach ($collection as $_key => $item) {
                    $sub = is_array($item) ? $item : ['.' => $item];
                    $sub['@index'] = $i;
                    $sub['@first'] = $i === 0;
                    $sub['@last']  = $i === $total - 1;
                    // Nested conditionals + vars
                    $rendered = $this->renderConditionals($inner, $sub);
                    $rendered = $this->renderVars($rendered, $sub);
                    $out[] = $rendered;
                    $i++;
                }
                return implode('', $out);
            },
            $tpl
        );
    }

    private function renderConditionals(string $tpl, array $ctx): string
    {
        // {{#if cond}}…{{else}}…{{/if}}
        $tpl = preg_replace_callback(
            '/\{\{\s*#if\s+([\w\.@]+)\s*\}\}(.*?)(?:\{\{\s*else\s*\}\}(.*?))?\{\{\s*\/if\s*\}\}/s',
            function ($m) use ($ctx) {
                return $this->isTruthy($this->lookup($m[1], $ctx))
                    ? $m[2]
                    : ($m[3] ?? '');
            },
            $tpl
        );

        // {{#unless cond}}…{{/unless}} and {{^unless cond}}…{{/unless}} (synonyms)
        $tpl = preg_replace_callback(
            '/\{\{\s*[\^#]unless\s+([\w\.@]+)\s*\}\}(.*?)\{\{\s*\/unless\s*\}\}/s',
            function ($m) use ($ctx) {
                return $this->isTruthy($this->lookup($m[1], $ctx)) ? '' : $m[2];
            },
            $tpl
        );

        return $tpl;
    }

    private function renderVars(string $tpl, array $ctx): string
    {
        return preg_replace_callback(
            '/\{\{\s*([\w\.@]+)\s*\}\}/',
            function ($m) use ($ctx) {
                $val = $this->lookup($m[1], $ctx);
                if (is_array($val) || is_object($val)) return '';
                if (is_bool($val)) return $val ? 'true' : 'false';
                return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
            },
            $tpl
        );
    }

    private function lookup(string $path, array $ctx)
    {
        if ($path === '.') return $ctx['.'] ?? null;
        if (isset($ctx[$path])) return $ctx[$path];

        $cur = $ctx;
        foreach (explode('.', $path) as $segment) {
            if (is_array($cur) && array_key_exists($segment, $cur)) {
                $cur = $cur[$segment];
            } else {
                return null;
            }
        }
        return $cur;
    }

    private function isTruthy($val): bool
    {
        if ($val === null || $val === false || $val === '' || $val === '0' || $val === 0) return false;
        if (is_array($val)) return count($val) > 0;
        return true;
    }
}
