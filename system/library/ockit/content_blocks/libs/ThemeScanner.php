<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

/**
 * Scans the catalog theme directory for available block themes.
 *
 * Theme directory structure:
 *   catalog/view/theme/{theme}/{module}/{block_type}/{theme_name}/theme.twig
 *   catalog/view/theme/{theme}/{module}/{block_type}/{theme_name}/preview.jpg  (optional)
 */
class ThemeScanner
{
    private string $catalogDir;
    private string $catalogUrl;
    private string $themeName;
    private string $moduleDir = 'oc_kit_content_blocks';

    public function __construct(string $catalogDir, string $catalogUrl, string $themeName)
    {
        $this->catalogDir = rtrim($catalogDir, '/');
        $this->catalogUrl = rtrim($catalogUrl, '/');
        $this->themeName  = $themeName ?: 'default';
    }

    /**
     * Returns all themes grouped by block type.
     * ['grid' => [['name' => 'default', 'preview' => 'url|false'], ...], ...]
     */
    public function getAllThemes(): array
    {
        $baseDir = $this->catalogDir . '/view/theme/' . $this->themeName . '/template/' . $this->moduleDir;

        if (!is_dir($baseDir)) {
            return [];
        }

        $result    = [];
        $blockDirs = glob($baseDir . '/*', GLOB_ONLYDIR);

        if (!$blockDirs) {
            return [];
        }

        foreach ($blockDirs as $blockDir) {
            $blockType  = basename($blockDir);
            $themeDirs  = glob($blockDir . '/*', GLOB_ONLYDIR);

            if (!$themeDirs) {
                continue;
            }

            $themes = [];
            foreach ($themeDirs as $themeDir) {
                if (!file_exists($themeDir . '/theme.twig')) {
                    continue;
                }
                $themes[] = $this->buildThemeEntry(basename($themeDir), $blockType, $themeDir);
            }

            if ($themes) {
                $result[$blockType] = $themes;
            }
        }

        return $result;
    }

    /**
     * Returns themes for a specific block type.
     */
    public function getThemesForType(string $blockType): array
    {
        $typeDir = $this->catalogDir . '/view/theme/' . $this->themeName
            . '/template/' . $this->moduleDir . '/' . $blockType;

        if (!is_dir($typeDir)) {
            return [['name' => 'default', 'preview' => false]];
        }

        $themeDirs = glob($typeDir . '/*', GLOB_ONLYDIR);

        if (!$themeDirs) {
            return [['name' => 'default', 'preview' => false]];
        }

        $themes = [];
        foreach ($themeDirs as $themeDir) {
            if (!file_exists($themeDir . '/theme.twig')) {
                continue;
            }
            $themes[] = $this->buildThemeEntry(basename($themeDir), $blockType, $themeDir);
        }

        return $themes ?: [['name' => 'default', 'preview' => false]];
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function buildThemeEntry(string $themeName, string $blockType, string $themeDir): array
    {
        $previewFile = $themeDir . '/preview.jpg';
        $previewUrl  = false;

        if (file_exists($previewFile)) {
            $previewUrl = $this->catalogUrl . '/view/theme/' . $this->themeName
                . '/template/' . $this->moduleDir . '/' . $blockType . '/' . $themeName . '/preview.jpg';
        }

        return [
            'name'    => $themeName,
            'preview' => $previewUrl,
        ];
    }
}
