<?php
/**
 * Advanced Search Pro — Homoglyph Normalizer
 *
 * Detects visually identical characters from different Unicode scripts
 * (e.g. Latin 'o' U+006F vs Cyrillic 'о' U+043E) that silently break search.
 * Determines the majority script in the query and replaces minority-script
 * lookalikes with their majority-script equivalents.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class HomoglyphNormalizer {

    // Latin → Cyrillic lookalikes (char-by-char replacements)
    private static $latinToCyrillic = [
        // lowercase
        'a' => 'а', 'c' => 'с', 'e' => 'е', 'i' => 'і',
        'o' => 'о', 'p' => 'р', 'x' => 'х', 'y' => 'у',
        // uppercase
        'A' => 'А', 'B' => 'В', 'C' => 'С', 'E' => 'Е',
        'H' => 'Н', 'I' => 'І', 'K' => 'К', 'M' => 'М',
        'O' => 'О', 'P' => 'Р', 'T' => 'Т', 'X' => 'Х',
        'Y' => 'У',
    ];

    // Cyrillic → Latin lookalikes (reverse)
    private static $cyrillicToLatin = [
        'а' => 'a', 'с' => 'c', 'е' => 'e', 'і' => 'i',
        'о' => 'o', 'р' => 'p', 'х' => 'x', 'у' => 'y',
        'А' => 'A', 'В' => 'B', 'С' => 'C', 'Е' => 'E',
        'Н' => 'H', 'І' => 'I', 'К' => 'K', 'М' => 'M',
        'О' => 'O', 'Р' => 'P', 'Т' => 'T', 'Х' => 'X',
        'У' => 'Y',
    ];

    /**
     * Normalize homoglyphs in a query string.
     *
     * Counts Cyrillic and Latin characters. If Cyrillic dominates (or is equal),
     * replaces Latin lookalikes → Cyrillic. If Latin dominates, replaces
     * Cyrillic lookalikes → Latin.
     *
     * Returns the original string unchanged if it is already pure-script
     * or if no lookalikes are present.
     */
    public function normalize(string $text): string {
        if ($text === '' || !$this->hasMixedScript($text)) {
            return $text;
        }

        // Normalize PER WORD, by each token's own dominant script. A whole-query
        // majority vote mangles a genuinely Latin word inside a Cyrillic query
        // ("камера fpv" → "камера fрv", "куртка nike" → "куртка nіkе") because the
        // Cyrillic words outvote it. Judging each token on its own leaves a
        // pure-Latin word intact, still fixes a mostly-Cyrillic word that carries
        // a stray Latin lookalike ("сухий" with a Latin 'х'), and also repairs the
        // inverse — a Latin word a shopper typed with a Cyrillic lookalike
        // ("fрv" → "fpv").
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $token) {
            if (trim($token) === '') {
                continue; // preserve the whitespace separators verbatim
            }
            $parts[$i] = $this->normalizeToken($token);
        }
        return implode('', $parts);
    }

    /**
     * Normalize a single whitespace-free token by its own dominant script. A
     * pure (single-script) token is returned unchanged; a mixed token has its
     * minority-script lookalikes converted to the majority script.
     */
    private function normalizeToken(string $token): string {
        $cyrCount = preg_match_all('/[\x{0400}-\x{04FF}]/u', $token, $m);
        $latCount = preg_match_all('/[a-zA-Z]/u', $token, $m2);

        if ($cyrCount === 0 || $latCount === 0) {
            return $token; // pure script (or no letters) — nothing to normalize
        }

        if ($cyrCount >= $latCount) {
            return strtr($token, self::$latinToCyrillic);
        }
        return strtr($token, self::$cyrillicToLatin);
    }

    /**
     * Check if the text contains mixed-script homoglyphs.
     */
    public function hasMixedScript(string $text): bool {
        $cyrCount = preg_match_all('/[\x{0400}-\x{04FF}]/u', $text, $m);
        $latCount = preg_match_all('/[a-zA-Z]/u', $text, $m2);
        return $cyrCount > 0 && $latCount > 0;
    }
}
