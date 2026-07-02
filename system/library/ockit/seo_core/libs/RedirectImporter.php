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
 * Imports redirects from CSV/TSV text content.
 *
 * Expected format (one rule per line):
 *   /old-url, /new-url [, code]
 *   /old-url	/new-url [	code]   (tab-separated also accepted)
 *
 * Empty lines and lines starting with # are skipped.
 * Default code: 301.
 *
 * Returns ['imported' => N, 'skipped' => N, 'errors' => [...]]
 */
class RedirectImporter
{
    /** @var RedirectManager */
    private $manager;
    /** @var RedirectChainDetector */
    private $chainDetector;
    public function __construct(RedirectManager $manager, RedirectChainDetector $chainDetector) {
        $this->manager = $manager;
        $this->chainDetector = $chainDetector;
    }

    public function importCsv(string $content, int $storeId, bool $skipChains = true): array
    {
        $lines    = preg_split('/\r?\n/', $content);
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        // Pre-load existing map for chain detection
        $existingData = $this->manager->getList($storeId, 1, 100000);
        $existing     = [];
        foreach ($existingData['items'] as $dto) {
            $existing[$dto->fromUrl] = $dto->toUrl;
        }

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;

            $parts = preg_split('/[\t,]/', $line, 3);
            $from  = trim($parts[0] ?? '');
            $to    = trim($parts[1] ?? '');
            $code  = (int)trim($parts[2] ?? '301');

            if (!in_array($code, [301, 302, 303, 307, 308, 410], true)) $code = 301;

            if (!$from || ($code !== 410 && !$to)) {
                $errors[] = 'Line ' . ($lineNum + 1) . ': missing from or to URL';
                $skipped++;
                continue;
            }

            if ($skipChains && $this->chainDetector->wouldCreateLoop($from, $to, $existing)) {
                $errors[] = 'Line ' . ($lineNum + 1) . ': skipped (would create loop) — ' . $from;
                $skipped++;
                continue;
            }

            $this->manager->save($storeId, $from, $to, $code);
            $existing[$from] = $to;
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
