<?php
/**
 * Advanced Search Pro — Morphological Stemmer
 *
 * Strips inflectional suffixes to a common stem so morphological variants
 * ("кормами", "кормів", "кормах") all match the root ("корм").
 *
 * Algorithms:
 *   Cyrillic (UA/RU) — simplified Snowball based on Porter (2006)
 *   English          — Porter stemmer (1980)
 *
 * DictionaryService overrides are applied BEFORE the algorithm.
 * Minimum stem length: 3 chars (shorter words are returned as-is).
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class Stemmer {

    // Cyrillic vowels (UA + RU union)
    private const CYR_VOWELS = ['а','е','є','и','і','ї','о','у','ю','я','ё','ы','э'];

    private const MIN_STEM = 3; // minimum stem length in chars

    // ── Cyrillic suffix tables ─────────────────────────────────────────────
    // Each group is ordered longest-first so the first match wins.

    private const PERFECTIVE_GROUP1_PRECEDE = ['а', 'я'];
    private const PERFECTIVE_GROUP1 = ['авшись','явшись','авши','явши','ав','яв'];
    private const PERFECTIVE_GROUP2 = ['ившись','ывшись','ивши','ывши','ив','ыв'];

    private const REFLEXIVE = ['ся','сь'];

    private const ADJECTIVE = [
        // UA-specific long forms
        'ськими','зькими','цькими','ськіх','зькіх','цькіх',
        'ський','ська','ське','ські',
        'зький','зька','зьке','зькі',
        'цький','цька','цьке','цькі',
        // Soft adjective UA
        'ньому','ньої','ньому','нього',
        'нього','ньому','ньої','ній','ніх','нім','німи',
        // Hard adjective endings (RU/UA shared)
        'ого','ему','ому','ое','ее','ие','ые','ой','ей',
        'ий','ый','их','ых','им','ым','ую','юю',
        // UA endings
        'ого','ій','ої','ому','им','ій',
    ];

    private const PARTICIPLE_GROUP1_PRECEDE = ['а','я'];
    private const PARTICIPLE_GROUP1 = ['ємо','єш','є','ємось','ємося'];
    private const PARTICIPLE_GROUP2 = [
        'івши','овши','евши','авши',
        'івш','овш','евш','авш',
    ];

    private const VERB_GROUP1_PRECEDE = ['а','я'];
    private const VERB_GROUP1 = [
        'ала','ало','ана','яла','яло','яна',
        'аєш','аємо','аєте','ають',
        'яєш','яємо','яєте','яють',
        'ала','яла',
        'аючи','яючи',
    ];
    private const VERB_GROUP2 = [
        // UA verb infinitives and conjugation
        'уватися','юватися','оватися','еватися',
        'увати','ювати','овати','евати',
        'ується','юється','ується',
        'ування','ювання',
        'уєш','юєш','уємо','юємо','уєте','юєте','ують','юють',
        // Common endings
        'ати','яти','ити','іти','уть','ють',
        'ила','ило','или','іла','іло','ілі',
        'ишь','ить','ите','ить',
        'ать','ять','уть','ють',
        'ать','ять',
        'ем','ет','ешь','ете',
        'ут','ют','ат','ят',
        'ить',
    ];

    private const NOUN = [
        // UA plural instrumental / locative / genitive
        'іями','ями','ами','еями',
        // UA dative/locative singular
        'ові','еві','єві',
        // UA genitive plural
        'ів','ей','єй',
        // Locative
        'ах','ях','іх',
        // Instrumental
        'ою','ею','єю',
        // Genitive
        'ого','ьго',
        // Dative
        'ому','ьму',
        // Instrumental singular
        'ом','ем','єм',
        // Dative/accusative
        'ам','ям',
        // Nominative/accusative plural (short)
        'ій','ій',
        // Short endings — applied last
        'и','і','а','я','е','є','у','ю',
    ];

    private const DERIVATIONAL = ['ість','ость','ісць'];

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Stem a single word.
     *
     * @param string $word  Input word (any case — will be lowercased)
     * @param string $lang  'auto'|'uk'|'ru'|'en'
     * @return string       Stem (lowercase), never shorter than MIN_STEM chars
     */
    public function stem(string $word, string $lang = 'auto'): string {
        $word = mb_strtolower(trim($word), 'UTF-8');
        $len  = mb_strlen($word, 'UTF-8');

        if ($len <= self::MIN_STEM) {
            return $word;
        }

        if ($lang === 'auto') {
            $lang = $this->detectScript($word);
        }

        return $lang === 'en' ? $this->porter($word) : $this->snowballCyr($word);
    }

    /**
     * Stem each word in a space-separated query.
     * Returns only stems that differ from the originals.
     *
     * @return string[] Unique stems that differ from the input words
     */
    public function stemQuery(string $query, string $lang = 'auto'): array {
        $words  = preg_split('/\s+/u', mb_strtolower(trim($query), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        $stems  = [];
        foreach ($words as $w) {
            $s = $this->stem($w, $lang);
            if ($s !== $w && mb_strlen($s, 'UTF-8') >= self::MIN_STEM) {
                $stems[] = $s;
            }
        }
        return array_values(array_unique($stems));
    }

    // ── Language detection ─────────────────────────────────────────────────

    private function detectScript(string $word): string {
        return preg_match('/[\x{0400}-\x{04FF}]/u', $word) ? 'cyr' : 'en';
    }

    // ══════════════════════════════════════════════════════════════════════
    // Cyrillic (Snowball Russian/Ukrainian)
    // ══════════════════════════════════════════════════════════════════════

    private function snowballCyr(string $word): string {
        // Normalize ё → е (Russian)
        $word = str_replace('ё', 'е', $word);

        $rv = $this->rvStart($word);

        // Step 1 — perfective gerund
        // Group 1: preceded by а/я
        foreach (self::PERFECTIVE_GROUP1 as $s) {
            if ($this->endsWith($word, $s)) {
                $base = $this->chop($word, $s);
                if ($this->endsWithAny($base, self::PERFECTIVE_GROUP1_PRECEDE)) {
                    if ($this->suffixInRV($word, $s, $rv)) {
                        $word = $base;
                        goto after_step1;
                    }
                }
            }
        }
        // Group 2
        foreach (self::PERFECTIVE_GROUP2 as $s) {
            if ($this->removeFromRV($word, $s, $rv)) {
                goto after_step1;
            }
        }

        // Step 2a — reflexive
        $this->removeFromRV($word, 'ся', $rv) || $this->removeFromRV($word, 'сь', $rv);

        // Step 2b — adjective (+ optional participle prefix)
        foreach (self::ADJECTIVE as $s) {
            if ($this->removeFromRV($word, $s, $rv)) {
                // also try to strip participle-style suffix that may precede
                foreach (self::PARTICIPLE_GROUP2 as $ps) {
                    if ($this->removeFromRV($word, $ps, $rv)) {
                        break;
                    }
                }
                goto after_step2;
            }
        }

        // Step 2c — verb group 1 (preceded by а/я)
        foreach (self::VERB_GROUP1 as $s) {
            if ($this->endsWith($word, $s)) {
                $base = $this->chop($word, $s);
                if ($this->endsWithAny($base, self::VERB_GROUP1_PRECEDE)) {
                    if ($this->suffixInRV($word, $s, $rv)) {
                        $word = $base;
                        goto after_step2;
                    }
                }
            }
        }
        // Step 2d — verb group 2
        foreach (self::VERB_GROUP2 as $s) {
            if ($this->removeFromRV($word, $s, $rv)) {
                goto after_step2;
            }
        }

        // Step 2e — noun
        foreach (self::NOUN as $s) {
            if ($this->removeFromRV($word, $s, $rv)) {
                break;
            }
        }

        after_step2:

        after_step1:

        // Step 3 — и/і at end (in RV)
        $this->removeFromRV($word, 'и', $rv) || $this->removeFromRV($word, 'і', $rv);

        // Step 4 — derivational suffix in R2
        $r2 = $this->r2Start($word);
        foreach (self::DERIVATIONAL as $s) {
            if ($this->suffixInRV($word, $s, $r2)) {
                $word = $this->chop($word, $s);
                break;
            }
        }

        // Step 5 — нн → н
        if ($this->endsWith($word, 'нн') && mb_strlen($word, 'UTF-8') > self::MIN_STEM + 1) {
            $word = mb_substr($word, 0, -1, 'UTF-8');
        }

        // Step 6 — soft sign ь (in RV)
        $this->removeFromRV($word, 'ь', $rv);

        // Ensure minimum length
        if (mb_strlen($word, 'UTF-8') < self::MIN_STEM) {
            return $word; // return whatever we have
        }

        return $word;
    }

    // ── Region helpers ─────────────────────────────────────────────────────

    /**
     * RV: position right after the first consonant that follows the first vowel.
     * If not found, returns word length (no RV).
     */
    private function rvStart(string $word): int {
        $len   = mb_strlen($word, 'UTF-8');
        $vowel = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($word, $i, 1, 'UTF-8');
            if ($this->isVowelCyr($ch)) {
                $vowel = true;
            } elseif ($vowel) {
                return $i + 1;
            }
        }
        return $len;
    }

    /**
     * R2: position of second such consonant-after-vowel transition.
     */
    private function r2Start(string $word): int {
        $len    = mb_strlen($word, 'UTF-8');
        $vowel  = false;
        $passes = 0;
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($word, $i, 1, 'UTF-8');
            if ($this->isVowelCyr($ch)) {
                $vowel = true;
            } elseif ($vowel) {
                $vowel = false;
                $passes++;
                if ($passes === 2) {
                    return $i + 1;
                }
            }
        }
        return $len;
    }

    private function isVowelCyr(string $ch): bool {
        return in_array($ch, self::CYR_VOWELS, true);
    }

    // ── Suffix helpers ─────────────────────────────────────────────────────

    private function endsWith(string $word, string $suffix): bool {
        $sl = mb_strlen($suffix, 'UTF-8');
        return mb_strlen($word, 'UTF-8') >= $sl
            && mb_substr($word, -$sl, null, 'UTF-8') === $suffix;
    }

    private function endsWithAny(string $word, array $suffixes): bool {
        foreach ($suffixes as $s) {
            if ($this->endsWith($word, $s)) {
                return true;
            }
        }
        return false;
    }

    private function chop(string $word, string $suffix): string {
        return mb_substr($word, 0, mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8'), 'UTF-8');
    }

    /**
     * Returns true if the suffix starts at or after $rvStart.
     */
    private function suffixInRV(string $word, string $suffix, int $rvStart): bool {
        $wLen = mb_strlen($word, 'UTF-8');
        $sLen = mb_strlen($suffix, 'UTF-8');
        return $this->endsWith($word, $suffix) && ($wLen - $sLen) >= $rvStart;
    }

    /**
     * If the suffix is in RV, removes it and returns true.
     */
    private function removeFromRV(string &$word, string $suffix, int $rvStart): bool {
        if ($this->suffixInRV($word, $suffix, $rvStart)
            && mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8') >= self::MIN_STEM
        ) {
            $word = $this->chop($word, $suffix);
            return true;
        }
        return false;
    }

    // ══════════════════════════════════════════════════════════════════════
    // English — Porter stemmer (simplified, step 1 + 2)
    // ══════════════════════════════════════════════════════════════════════

    private function porter(string $word): string {
        if (mb_strlen($word, 'UTF-8') <= 3) {
            return $word;
        }

        // Step 1a
        if ($this->endsWith($word, 'sses')) {
            $word = $this->chop($word, 'sses') . 'ss';
        } elseif ($this->endsWith($word, 'ies')) {
            $word = $this->chop($word, 'ies') . 'i';
        } elseif ($this->endsWith($word, 'ss')) {
            // no-op
        } elseif ($this->endsWith($word, 's') && mb_strlen($word, 'UTF-8') > 2) {
            $word = $this->chop($word, 's');
        }

        // Step 1b
        if ($this->endsWith($word, 'eed')) {
            if ($this->measureEN($this->chop($word, 'eed')) > 0) {
                $word = $this->chop($word, 'eed') . 'ee';
            }
        } elseif ($this->endsWith($word, 'ed')) {
            $stem = $this->chop($word, 'ed');
            if ($this->containsVowelEN($stem)) {
                $word = $stem;
                $word = $this->step1bFix($word);
            }
        } elseif ($this->endsWith($word, 'ing')) {
            $stem = $this->chop($word, 'ing');
            if ($this->containsVowelEN($stem)) {
                $word = $stem;
                $word = $this->step1bFix($word);
            }
        }

        // Step 1c
        if ($this->endsWith($word, 'y') && $this->containsVowelEN($this->chop($word, 'y'))) {
            $word = $this->chop($word, 'y') . 'i';
        }

        // Step 2
        $step2 = [
            'ational' => 'ate', 'tional' => 'tion', 'enci' => 'ence',
            'anci'    => 'ance', 'izer'  => 'ize',  'abli' => 'able',
            'alli'    => 'al',  'entli' => 'ent',   'eli'  => 'e',
            'ousli'   => 'ous', 'ization' => 'ize', 'ation' => 'ate',
            'ator'    => 'ate', 'alism' => 'al',    'iveness' => 'ive',
            'fulness' => 'ful', 'ousness' => 'ous', 'aliti' => 'al',
            'iviti'   => 'ive', 'biliti' => 'ble',
        ];
        foreach ($step2 as $suf => $rep) {
            if ($this->endsWith($word, $suf)
                && $this->measureEN($this->chop($word, $suf)) > 0
            ) {
                $word = $this->chop($word, $suf) . $rep;
                break;
            }
        }

        // Step 3
        $step3 = [
            'icate' => 'ic', 'ative' => '', 'alize' => 'al',
            'iciti' => 'ic', 'ical'  => 'ic', 'ful' => '', 'ness' => '',
        ];
        foreach ($step3 as $suf => $rep) {
            if ($this->endsWith($word, $suf)
                && $this->measureEN($this->chop($word, $suf)) > 0
            ) {
                $word = $this->chop($word, $suf) . $rep;
                break;
            }
        }

        // Step 4 — remove derivational suffixes when m > 1
        $step4 = [
            'ement','ment','ance','ence','ion','ou','ism','ate',
            'iti','ous','ive','ize','al','er','ic',
        ];
        foreach ($step4 as $suf) {
            if ($this->endsWith($word, $suf)) {
                $stem = $this->chop($word, $suf);
                if ($suf === 'ion' && !$this->endsWithAny($stem, ['s','t'])) {
                    continue;
                }
                if ($this->measureEN($stem) > 1) {
                    $word = $stem;
                    break;
                }
            }
        }

        // Step 5a
        if ($this->endsWith($word, 'e')) {
            $stem = $this->chop($word, 'e');
            $m    = $this->measureEN($stem);
            if ($m > 1 || ($m === 1 && !$this->endsWithCVC($stem))) {
                $word = $stem;
            }
        }

        // Step 5b
        if ($this->endsWith($word, 'll') && $this->measureEN($word) > 1) {
            $word = $this->chop($word, 'l');
        }

        return $word;
    }

    private function step1bFix(string $word): string {
        if ($this->endsWithAny($word, ['at','bl','iz'])) {
            $word .= 'e';
        } elseif ($this->doubleConsonantEN($word) && !$this->endsWithAny($word, ['l','s','z'])) {
            $word = mb_substr($word, 0, -1, 'UTF-8');
        } elseif ($this->measureEN($word) === 1 && $this->endsWithCVC($word)) {
            $word .= 'e';
        }
        return $word;
    }

    private function containsVowelEN(string $word): bool {
        return (bool)preg_match('/[aeiou]/i', $word) ||
               (bool)preg_match('/[^aeiou]y/i', $word);
    }

    private function doubleConsonantEN(string $word): bool {
        $len = strlen($word);
        return $len >= 2 && $word[$len - 1] === $word[$len - 2]
            && !in_array($word[$len - 1], ['a','e','i','o','u'], true);
    }

    private function endsWithCVC(string $word): bool {
        $len = strlen($word);
        if ($len < 3) {
            return false;
        }
        $c = $word[$len - 1];
        $v = $word[$len - 2];
        $c2 = $word[$len - 3];
        return !in_array($c, ['a','e','i','o','u','w','x','y'], true)
            && in_array($v, ['a','e','i','o','u'], true)
            && !in_array($c2, ['a','e','i','o','u'], true);
    }

    /**
     * Porter measure m: number of VC sequences in word.
     */
    private function measureEN(string $word): int {
        $m   = 0;
        $len = strlen($word);
        $i   = 0;
        // skip initial consonants
        while ($i < $len && !in_array($word[$i], ['a','e','i','o','u'], true)) {
            $i++;
        }
        while ($i < $len) {
            // skip vowels
            while ($i < $len && in_array($word[$i], ['a','e','i','o','u'], true)) {
                $i++;
            }
            if ($i < $len) {
                $m++;
                // skip consonants
                while ($i < $len && !in_array($word[$i], ['a','e','i','o','u'], true)) {
                    $i++;
                }
            }
        }
        return $m;
    }
}
