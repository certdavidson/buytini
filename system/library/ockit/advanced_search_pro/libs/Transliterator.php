<?php
/**
 * Advanced Search Pro — Transliterator
 *
 * Bidirectional transliteration between Cyrillic (UA/RU) and Latin.
 * Generates search variants so "Сімпаріка" finds "Simparica" and vice versa.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class Transliterator {

    // Cyrillic → Latin (search-optimised, not official DSTU)
    // Multi-char sequences first to avoid partial matches
    private static $cyrToLat = [
        // Ukrainian specifics
        'щ' => 'shch', 'Щ' => 'Shch',
        'ж' => 'zh',   'Ж' => 'Zh',
        'ш' => 'sh',   'Ш' => 'Sh',
        'ч' => 'ch',   'Ч' => 'Ch',
        'ц' => 'ts',   'Ц' => 'Ts',
        'х' => 'kh',   'Х' => 'Kh',
        'є' => 'ye',   'Є' => 'Ye',
        'ї' => 'yi',   'Ї' => 'Yi',
        'ю' => 'yu',   'Ю' => 'Yu',
        'я' => 'ya',   'Я' => 'Ya',
        // Russian specifics
        'ё' => 'yo',   'Ё' => 'Yo',
        'э' => 'e',    'Э' => 'E',
        'ъ' => '',     'Ъ' => '',
        // Common
        'а' => 'a',  'А' => 'A',
        'б' => 'b',  'Б' => 'B',
        'в' => 'v',  'В' => 'V',
        'г' => 'h',  'Г' => 'H',
        'ґ' => 'g',  'Ґ' => 'G',
        'д' => 'd',  'Д' => 'D',
        'е' => 'e',  'Е' => 'E',
        'з' => 'z',  'З' => 'Z',
        'и' => 'y',  'И' => 'Y',
        'і' => 'i',  'І' => 'I',
        'й' => 'y',  'Й' => 'Y',
        'к' => 'k',  'К' => 'K',
        'л' => 'l',  'Л' => 'L',
        'м' => 'm',  'М' => 'M',
        'н' => 'n',  'Н' => 'N',
        'о' => 'o',  'О' => 'O',
        'п' => 'p',  'П' => 'P',
        'р' => 'r',  'Р' => 'R',
        'с' => 's',  'С' => 'S',
        'т' => 't',  'Т' => 'T',
        'у' => 'u',  'У' => 'U',
        'ф' => 'f',  'Ф' => 'F',
        'ц' => 'ts', 'Ц' => 'Ts',
        'ь' => '',   'Ь' => '',
    ];

    // Latin → Cyrillic (multi-char sequences first)
    private static $latToCyr = [
        'shch' => 'щ', 'Shch' => 'Щ', 'SHCH' => 'Щ',
        'zh'   => 'ж', 'Zh'   => 'Ж', 'ZH'   => 'Ж',
        'sh'   => 'ш', 'Sh'   => 'Ш', 'SH'   => 'Ш',
        'ch'   => 'ч', 'Ch'   => 'Ч', 'CH'   => 'Ч',
        'ts'   => 'ц', 'Ts'   => 'Ц', 'TS'   => 'Ц',
        'kh'   => 'х', 'Kh'   => 'Х', 'KH'   => 'Х',
        'ye'   => 'є', 'Ye'   => 'Є', 'YE'   => 'Є',
        'yi'   => 'ї', 'Yi'   => 'Ї', 'YI'   => 'Ї',
        'yu'   => 'ю', 'Yu'   => 'Ю', 'YU'   => 'Ю',
        'ya'   => 'я', 'Ya'   => 'Я', 'YA'   => 'Я',
        'yo'   => 'ё', 'Yo'   => 'Ё', 'YO'   => 'Ё',
        'a'    => 'а', 'A'    => 'А',
        'b'    => 'б', 'B'    => 'Б',
        'v'    => 'в', 'V'    => 'В',
        'h'    => 'г', 'H'    => 'Г',
        'g'    => 'ґ', 'G'    => 'Ґ',
        'd'    => 'д', 'D'    => 'Д',
        'e'    => 'е', 'E'    => 'Е',
        'z'    => 'з', 'Z'    => 'З',
        'y'    => 'и', 'Y'    => 'И',
        'i'    => 'і', 'I'    => 'І',
        'k'    => 'к', 'K'    => 'К',
        'l'    => 'л', 'L'    => 'Л',
        'm'    => 'м', 'M'    => 'М',
        'n'    => 'н', 'N'    => 'Н',
        'o'    => 'о', 'O'    => 'О',
        'p'    => 'п', 'P'    => 'П',
        'r'    => 'р', 'R'    => 'Р',
        's'    => 'с', 'S'    => 'С',
        't'    => 'т', 'T'    => 'Т',
        'u'    => 'у', 'U'    => 'У',
        'f'    => 'ф', 'F'    => 'Ф',
    ];

    /**
     * Generate transliteration variants for a query.
     *
     * Returns array of alternative queries (may be empty).
     * - Cyrillic input → Latin variant
     * - Latin input → Cyrillic variant
     * Does NOT return the original query.
     */
    public function getVariants(string $query): array {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $cyrCount = preg_match_all('/[\x{0400}-\x{04FF}]/u', $query, $m);
        $latCount = preg_match_all('/[a-zA-Z]/u', $query, $m2);

        $variants = [];

        if ($cyrCount > 0) {
            $lat = $this->cyrillicToLatin($query);
            if ($lat !== '' && $lat !== $query) {
                $variants[] = $lat;
            }
        }

        if ($latCount > 0) {
            $cyr = $this->latinToCyrillic($query);
            if ($cyr !== '' && $cyr !== $query) {
                $variants[] = $cyr;
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * Convert Cyrillic text to Latin.
     */
    public function cyrillicToLatin(string $text): string {
        return strtr($text, self::$cyrToLat);
    }

    /**
     * Convert Latin text to Cyrillic.
     * Multi-char digraphs (sh, zh, ch…) are processed first via regex,
     * then single characters via strtr.
     */
    public function latinToCyrillic(string $text): string {
        // Process digraphs in correct order (longest first)
        $digraphs = ['shch', 'Shch', 'SHCH', 'zh', 'Zh', 'ZH', 'sh', 'Sh', 'SH',
                     'ch', 'Ch', 'CH', 'ts', 'Ts', 'TS', 'kh', 'Kh', 'KH',
                     'ye', 'Ye', 'YE', 'yi', 'Yi', 'YI', 'yu', 'Yu', 'YU',
                     'ya', 'Ya', 'YA', 'yo', 'Yo', 'YO'];

        foreach ($digraphs as $d) {
            if (isset(self::$latToCyr[$d])) {
                $text = str_replace($d, self::$latToCyr[$d], $text);
            }
        }

        // Single chars
        $singles = ['a' => 'а', 'A' => 'А', 'b' => 'б', 'B' => 'Б',
                    'v' => 'в', 'V' => 'В', 'd' => 'д', 'D' => 'Д',
                    'e' => 'е', 'E' => 'Е', 'z' => 'з', 'Z' => 'З',
                    'i' => 'і', 'I' => 'І', 'k' => 'к', 'K' => 'К',
                    'l' => 'л', 'L' => 'Л', 'm' => 'м', 'M' => 'М',
                    'n' => 'н', 'N' => 'Н', 'o' => 'о', 'O' => 'О',
                    'p' => 'п', 'P' => 'П', 'r' => 'р', 'R' => 'Р',
                    's' => 'с', 'S' => 'С', 't' => 'т', 'T' => 'Т',
                    'u' => 'у', 'U' => 'У', 'f' => 'ф', 'F' => 'Ф',
                    'g' => 'ґ', 'G' => 'Ґ', 'h' => 'г', 'H' => 'Г',
                    'y' => 'и', 'Y' => 'И'];

        return strtr($text, $singles);
    }
}
