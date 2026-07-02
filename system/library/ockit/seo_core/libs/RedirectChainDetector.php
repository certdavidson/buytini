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
 * Detects redirect chains and loops in the redirect table.
 *
 * A → B → C is a chain (depth > 1 hop).
 * A → B → A is a loop.
 *
 * Called before saving a new redirect to prevent bad data,
 * and from the admin audit action to find existing problems.
 */
class RedirectChainDetector
{
    /** @var RedirectManager */
    private $manager;
    public function __construct(RedirectManager $manager) {
        $this->manager = $manager;
    }

    /**
     * Check if adding fromUrl → toUrl would create a loop.
     * @param  string[] $existing map of from→to already in the table
     */
    public function wouldCreateLoop(string $fromUrl, string $toUrl, array $existing): bool
    {
        $visited = [$fromUrl => true];
        $current = $toUrl;

        while (isset($existing[$current])) {
            if (isset($visited[$current])) return true;
            $visited[$current] = true;
            $current = $existing[$current];
        }

        return false;
    }

    /**
     * Detect all chains (A→B where B also has a redirect) in the full table.
     *
     * @param  array<string,string> $map from_url → to_url
     * @return array[] list of chains: [['from','via','to'], ...]
     */
    public function detectChains(array $map): array
    {
        $chains = [];

        foreach ($map as $from => $to) {
            if (!isset($map[$to])) continue;

            // $to has a further redirect — this is a chain
            $hops = [$from, $to];
            $cur  = $to;

            while (isset($map[$cur]) && count($hops) < 20) {
                $cur    = $map[$cur];
                $hops[] = $cur;
                if ($cur === $from) break; // loop
            }

            $chains[] = $hops;
        }

        return $chains;
    }

    /**
     * Flatten all chains: for A→B→C, return A→C so each rule resolves in one hop.
     *
     * @param  array<string,string> $map
     * @return array<string,string> flattened map
     */
    public function flattenChains(array $map): array
    {
        $resolved = [];

        foreach ($map as $from => $_) {
            $cur     = $from;
            $visited = [];

            while (isset($map[$cur]) && !isset($visited[$cur])) {
                $visited[$cur] = true;
                $cur = $map[$cur];
            }

            $resolved[$from] = $cur;
        }

        return $resolved;
    }
}
