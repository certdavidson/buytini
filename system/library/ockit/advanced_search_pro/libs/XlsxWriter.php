<?php
/**
 * Advanced Search Pro — minimal multi-sheet XLSX writer (OOXML).
 *
 * No external dependencies beyond ext-zip. Builds a valid .xlsx with one
 * worksheet per addSheet() call, a bold header row and inline strings
 * (no shared-strings table needed). Numbers are written as numeric cells,
 * everything else as text.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class XlsxWriter {
    /** @var array<int,array{name:string,headers:array,rows:array}> */
    private $sheets = [];

    /** Add a worksheet. $headers = column titles; $rows = array of flat arrays. */
    public function addSheet(string $name, array $headers, array $rows): self {
        $this->sheets[] = [
            'name'    => $this->sanitizeName($name),
            'headers' => array_values($headers),
            'rows'    => $rows,
        ];
        return $this;
    }

    /** Build the .xlsx and return it as a binary string. */
    public function build(): string {
        if (!class_exists('\ZipArchive')) {
            throw new \RuntimeException('ext-zip is required to build XLSX files');
        }
        if (!$this->sheets) {
            $this->addSheet('Sheet1', [], []);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'aspxlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet));
        }
        $zip->close();

        $data = (string)file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    private function contentTypes(): string {
        $overrides = '';
        foreach ($this->sheets as $i => $s) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1)
                . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRels(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string {
        $sheetsXml = '';
        foreach ($this->sheets as $i => $s) {
            $sheetsXml .= '<sheet name="' . $this->esc($s['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets></workbook>';
    }

    private function workbookRels(): string {
        $rels = '';
        foreach ($this->sheets as $i => $s) {
            $rels .= '<Relationship Id="rId' . ($i + 1)
                . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                . 'Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels . '</Relationships>';
    }

    /** Two cell styles: 0 = normal, 1 = bold (header). */
    private function styles(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private function sheetXml(array $sheet): string {
        $rowsXml = '';
        $r = 1;
        if ($sheet['headers']) {
            $rowsXml .= $this->rowXml($r++, $sheet['headers'], 1);
        }
        foreach ($sheet['rows'] as $row) {
            $rowsXml .= $this->rowXml($r++, array_values((array)$row), 0);
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData></worksheet>';
    }

    private function rowXml(int $r, array $cells, int $style): string {
        $c = '';
        $col = 0;
        foreach ($cells as $val) {
            $ref = $this->colLetter($col++) . $r;
            $sAttr = $style ? ' s="1"' : '';
            // Numeric cell only for clean integers/decimals — not for ids with
            // leading zeros or phone-like strings (keep those as text).
            if (is_int($val) || is_float($val) || (is_string($val) && $val !== '' && preg_match('/^-?\d+(\.\d+)?$/', $val) && !preg_match('/^0\d/', $val))) {
                $c .= '<c r="' . $ref . '"' . $sAttr . '><v>' . $val . '</v></c>';
            } else {
                $c .= '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t xml:space="preserve">' . $this->esc((string)$val) . '</t></is></c>';
            }
        }
        return '<row r="' . $r . '">' . $c . '</row>';
    }

    /** 0-based column index → A, B, …, Z, AA, AB … */
    private function colLetter(int $i): string {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = (int)(($i - $m) / 26);
        }
        return $s;
    }

    private function esc($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Excel worksheet names: max 31 chars, none of [ ] : * ? / \ */
    private function sanitizeName($name): string {
        $name = preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', (string)$name);
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = function_exists('mb_substr') ? mb_substr($name, 0, 31, 'UTF-8') : substr($name, 0, 31);
        return $name !== '' ? $name : 'Sheet';
    }
}
