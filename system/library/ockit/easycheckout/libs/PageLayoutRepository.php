<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

require_once __DIR__ . '/ConfigStore.php';
require_once __DIR__ . '/BlockRegistry.php';

/**
 * Розкладка checkout-сторінки у вигляді ієрархії: step → rows → cells → blocks.
 *
 * Дані зберігаються у `oc_kit_easycheckout_settings`
 * (code='page_layout', key='checkout' для дефолтної сторінки).
 *
 * Структура:
 * {
 *   "mode": "single_step" | "multi_step",
 *   "steps": [
 *     {
 *       "id": "step1",
 *       "title": { "uk-ua": "Контакти", ... },
 *       "rows": [
 *         {
 *           "id": "row1",
 *           "columns": 2,                  // 1, 2 або 3 (на mobile/tablet рядок завжди стає 1-кол)
 *           "cells": [
 *             { "id": "c1", "blocks": [ {"id":"customer","type":"customer"}, ... ] },
 *             { "id": "c2", "blocks": [ ... ] }
 *           ]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */
final class PageLayoutRepository
{
    private ConfigStore $store;

    public function __construct(ConfigStore $store)
    {
        $this->store = $store;
    }

    public function get(string $page = 'checkout'): array
    {
        $layout = $this->store->get('page_layout', $page);
        if (!is_array($layout) || !isset($layout['steps'])) {
            return $this->defaultLayout();
        }
        return $this->normalize($layout);
    }

    public function save(string $page, array $layout): void
    {
        $this->store->set('page_layout', $page, $this->normalize($layout));
    }

    /**
     * Нормалізує layout: гарантує валідність кроків, рядків, колонок, комірок,
     * блоків. Migrate з legacy-формату (step.blocks без rows).
     */
    public function normalize(array $layout): array
    {
        $mode = ($layout['mode'] ?? 'single_step');
        if (!in_array($mode, ['single_step', 'multi_step'], true)) {
            $mode = 'single_step';
        }

        $usedBlockIds = [];
        $steps = [];
        foreach (($layout['steps'] ?? []) as $stepIdx => $step) {
            if (!is_array($step)) continue;

            $stepId = preg_replace('~[^a-z0-9_-]~i', '', (string)($step['id'] ?? ''));
            if ($stepId === '') $stepId = 'step' . ($stepIdx + 1);

            $cleanTitle = [];
            foreach (($step['title'] ?? []) as $lang => $text) {
                $cleanTitle[(string)$lang] = trim((string)$text);
            }

            $rows = $this->normalizeRows($step, $usedBlockIds);

            $steps[] = [
                'id'    => $stepId,
                'title' => $cleanTitle,
                'rows'  => $rows,
            ];
        }

        if (!$steps) {
            $steps[] = [
                'id'    => 'step1',
                'title' => [],
                'rows'  => [$this->emptyRow('row1', 1)],
            ];
        }

        return [
            'mode'  => $mode,
            'steps' => $steps,
        ];
    }

    private function normalizeRows(array $step, array &$usedBlockIds): array
    {
        // Backward compat: якщо step.blocks (старий формат), пакуємо в один 1-кол рядок.
        if (!isset($step['rows']) && isset($step['blocks']) && is_array($step['blocks'])) {
            $blocks = $this->normalizeBlocks($step['blocks'], $usedBlockIds);
            return [[
                'id'      => 'row_' . $this->shortHash(),
                'columns' => ['desktop' => 1, 'tablet' => 1],
                'cells'   => [[
                    'id'           => 'cell_' . $this->shortHash(),
                    'blocks'       => $blocks,
                    'span'         => ['desktop' => 12, 'tablet' => 12, 'mobile' => 12],
                    'order_tablet' => null,
                    'order_mobile' => null,
                ]],
            ]];
        }

        $rows = [];
        $usedRowIds  = [];
        $usedCellIds = [];
        foreach (($step['rows'] ?? []) as $rIdx => $row) {
            if (!is_array($row)) continue;

            // columns: {desktop, tablet} — int 1-3 для кожного. Mobile завжди 1.
            $columns = $this->normalizeColumns($row['columns'] ?? null);

            $rowId = preg_replace('~[^a-z0-9_-]~i', '', (string)($row['id'] ?? ''));
            if ($rowId === '' || in_array($rowId, $usedRowIds, true)) {
                $rowId = 'row_' . $this->shortHash();
            }
            $usedRowIds[] = $rowId;

            // Cells: рівно desktop колонок. Якщо менше — добиваємо порожніми, більше — обрізаємо
            // (блоки з обрізаних додаємо до останньої цільової комірки, щоб не загубити).
            $rawCells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $cells = [];
            // Cells прив'язані до desktop — це structural source.
            $cellsCount = $columns['desktop'];
            for ($c = 0; $c < $cellsCount; $c++) {
                $src = $rawCells[$c] ?? [];
                $cellId = preg_replace('~[^a-z0-9_-]~i', '', (string)($src['id'] ?? ''));
                if ($cellId === '' || in_array($cellId, $usedCellIds, true)) {
                    $cellId = 'cell_' . $this->shortHash();
                }
                $usedCellIds[] = $cellId;

                $blocks = $this->normalizeBlocks($src['blocks'] ?? [], $usedBlockIds);
                $blockIdsSet = array_map(function ($b) { return $b['id']; }, $blocks);

                $cells[] = [
                    'id'           => $cellId,
                    'blocks'       => $blocks,
                    'span'         => $this->normalizeSpan($src['span'] ?? null, $columns),
                    'order_tablet' => $this->normalizeOrder($src['order_tablet'] ?? null, $blockIdsSet),
                    'order_mobile' => $this->normalizeOrder($src['order_mobile'] ?? null, $blockIdsSet),
                ];
            }

            // Якщо було більше cells ніж desktop — мерджимо хвіст у останню комірку
            if (count($rawCells) > $cellsCount) {
                $tailBlocks = [];
                for ($c = $cellsCount; $c < count($rawCells); $c++) {
                    $extra = $this->normalizeBlocks($rawCells[$c]['blocks'] ?? [], $usedBlockIds);
                    foreach ($extra as $b) $tailBlocks[] = $b;
                }
                $lastIdx = count($cells) - 1;
                $cells[$lastIdx]['blocks'] = array_merge($cells[$lastIdx]['blocks'], $tailBlocks);
            }

            $rows[] = [
                'id'      => $rowId,
                'columns' => $columns,
                'cells'   => $cells,
            ];
        }

        if (!$rows) {
            $rows[] = $this->emptyRow('row_' . $this->shortHash(), 1);
        }

        return $rows;
    }

    private function normalizeBlocks(array $rawBlocks, array &$usedBlockIds): array
    {
        $out = [];
        foreach ($rawBlocks as $b) {
            if (!is_array($b)) continue;
            $type = (string)($b['type'] ?? '');
            if (!BlockRegistry::exists($type)) continue;

            $id = (string)($b['id'] ?? '');
            if ($id === '' || in_array($id, $usedBlockIds, true)) {
                $id = BlockRegistry::generateBlockId($type, $usedBlockIds);
            }
            $usedBlockIds[] = $id;

            $out[] = [
                'id'       => $id,
                'type'     => $type,
                'settings' => $this->normalizeBlockSettings($b['settings'] ?? null),
            ];
        }
        return $out;
    }

    /**
     * Нормалізує block.settings — JSON-об'єкт з налаштуваннями блоку.
     * Ми не валідуємо schema жорстко на цьому рівні (це робить frontend);
     * лише гарантуємо що повертається об'єкт (не array з integer keys),
     * і відсіюємо нескалярні значення верхнього рівня.
     */
    private function normalizeBlockSettings($raw): array
    {
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $k => $v) {
            $key = (string)$k;
            if ($key === '') continue;
            if ($v === null) continue;

            // Спеціально обробляємо `fields` — масив підвʼязок до глобального реєстру.
            if ($key === 'fields') {
                $out['fields'] = $this->normalizeBlockFields($v);
                continue;
            }

            // Conditional visibility для всього блоку (та сама схема що для fields)
            if ($key === 'condition') {
                $out['condition'] = $this->normalizeCondition($v);
                continue;
            }

            $out[$key] = $v;
        }
        return $out;
    }

    /**
     * Нормалізує condition-об'єкт для блоків / полів.
     * Operators: ==, !=, not_empty, empty, in. Source_code обов'язковий.
     * Повертає null якщо невалідний.
     */
    private function normalizeCondition($raw): ?array
    {
        if (!is_array($raw)) return null;
        $sourceCode = trim((string)($raw['source_code'] ?? ''));
        $operator   = (string)($raw['operator'] ?? '==');
        $allowedOps = ['==', '!=', 'not_empty', 'empty', 'in'];
        if ($sourceCode === '' || !in_array($operator, $allowedOps, true)) return null;
        return [
            'source_code' => $sourceCode,
            'operator'    => $operator,
            'value'       => (string)($raw['value'] ?? ''),
        ];
    }

    /**
     * Нормалізує settings.fields: масив об'єктів {field_id, visibility, required, reload_on_change}.
     */
    private function normalizeBlockFields($raw): array
    {
        if (!is_array($raw)) return [];
        $out = [];
        $seen = [];
        foreach ($raw as $f) {
            if (!is_array($f)) continue;
            $fid = (int)($f['field_id'] ?? 0);
            if ($fid === 0 || in_array($fid, $seen, true)) continue;
            $seen[] = $fid;

            $visibility = (string)($f['visibility'] ?? 'always');
            if (!in_array($visibility, ['always', 'guests', 'logged_in'], true)) {
                $visibility = 'always';
            }
            $width = (string)($f['width'] ?? 'full');
            if (!in_array($width, ['full', 'half', 'third', 'two_thirds'], true)) {
                $width = 'full';
            }
            // Conditional visibility — { source_code, operator, value }
            // Operators: ==, !=, not_empty, empty, in (CSV value)
            $condition = null;
            if (is_array($f['condition'] ?? null)) {
                $sourceCode = trim((string)($f['condition']['source_code'] ?? ''));
                $operator   = (string)($f['condition']['operator'] ?? '==');
                $allowedOps = ['==', '!=', 'not_empty', 'empty', 'in'];
                if ($sourceCode !== '' && in_array($operator, $allowedOps, true)) {
                    $condition = [
                        'source_code' => $sourceCode,
                        'operator'    => $operator,
                        'value'       => (string)($f['condition']['value'] ?? ''),
                    ];
                }
            }

            $out[] = [
                'field_id'         => $fid,
                'visibility'       => $visibility,
                'width'            => $width,
                'required'         => !empty($f['required']),
                'reload_on_change' => !empty($f['reload_on_change']),
                'condition'        => $condition,
            ];
        }
        return $out;
    }

    private function emptyRow(string $id, int $columns): array
    {
        $d = max(1, min(3, $columns));
        $span = max(1, min(12, (int)round(12 / $d)));
        $cells = [];
        for ($i = 0; $i < $columns; $i++) {
            $cells[] = [
                'id'           => 'cell_' . $this->shortHash(),
                'blocks'       => [],
                'span'         => ['desktop' => $span, 'tablet' => $span, 'mobile' => 12],
                'order_tablet' => null,
                'order_mobile' => null,
            ];
        }
        return [
            'id'      => $id,
            'columns' => ['desktop' => $d, 'tablet' => $d],
            'cells'   => $cells,
        ];
    }

    private function shortHash(): string
    {
        return substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /**
     * Ширина комірки у 12-колонковій сітці на breakpoint. desktop/tablet за
     * замовчуванням — рівний поділ (12/кількість колонок); mobile — 12 (стак).
     * @return array{desktop:int,tablet:int,mobile:int} (1..12)
     */
    private function normalizeSpan($raw, array $columns): array
    {
        $defD = max(1, min(12, (int)round(12 / max(1, (int)$columns['desktop']))));
        $defT = max(1, min(12, (int)round(12 / max(1, (int)$columns['tablet']))));
        if (!is_array($raw)) $raw = [];
        return [
            'desktop' => isset($raw['desktop']) ? max(1, min(12, (int)$raw['desktop'])) : $defD,
            'tablet'  => isset($raw['tablet'])  ? max(1, min(12, (int)$raw['tablet']))  : $defT,
            'mobile'  => isset($raw['mobile'])  ? max(1, min(12, (int)$raw['mobile']))  : 12,
        ];
    }

    /**
     * Нормалізує row.columns у {desktop, tablet}. Mobile завжди 1 (поза цим об'єктом).
     * Приймає int (legacy) або об'єкт.
     */
    private function normalizeColumns($raw): array
    {
        if (is_numeric($raw)) {
            $d = max(1, min(3, (int)$raw));
            return ['desktop' => $d, 'tablet' => $d];
        }
        if (!is_array($raw)) $raw = [];
        $d = max(1, min(3, (int)($raw['desktop'] ?? 1)));
        $t = max(1, min(3, (int)($raw['tablet']  ?? $d)));
        return ['desktop' => $d, 'tablet' => $t];
    }

    /**
     * Нормалізує `order_tablet`/`order_mobile` cell-у — масив block-ID у потрібному порядку.
     * Залишає тільки ті ID, що реально існують у $blockIds. Якщо в blocks є нові ID,
     * відсутні в overrideі — вони НЕ додаються (рендер fallback-ить на canonical порядок).
     * Повертає null якщо input не масив або повністю порожній.
     */
    private function normalizeOrder($raw, array $blockIds): ?array
    {
        if (!is_array($raw)) return null;
        $valid = array_values(array_filter(array_map('strval', $raw), function ($id) use ($blockIds) {
            return in_array($id, $blockIds, true);
        }));
        return $valid ? $valid : null;
    }

    /**
     * Початкова розкладка: 1 крок з 4 рядками різної структури —
     * приклад комбінації 1-кол / 2-кол / 1-кол.
     */
    public function defaultLayout(): array
    {
        $rh = function () { return 'row_' . substr(bin2hex(random_bytes(3)), 0, 6); };
        $ch = function () { return 'cell_' . substr(bin2hex(random_bytes(3)), 0, 6); };

        // Хелпер: native-поле з дефолтами visibility/required/reload_on_change.
        $f = function (int $id, bool $required = false, bool $reload = false, string $vis = 'always'): array {
            return [
                'field_id'         => $id,
                'visibility'       => $vis,
                'required'         => $required,
                'reload_on_change' => $reload,
            ];
        };

        return [
            'mode'  => 'single_step',
            'steps' => [
                [
                    'id'    => 'step1',
                    'title' => [],
                    'rows'  => [
                        // Рядок 1: Покупець (full-width) — email + firstname + lastname + telephone
                        [
                            'id' => $rh(), 'columns' => ['desktop' => 1, 'tablet' => 1],
                            'cells' => [
                                ['id' => $ch(), 'blocks' => [
                                    ['id' => 'customer', 'type' => 'customer', 'settings' => [
                                        'registration_mode' => 'optional',
                                        'show_login_link'   => true,
                                        'fields' => [
                                            $f(-1,  true),  // email
                                            $f(-2,  true),  // firstname
                                            $f(-3,  true),  // lastname
                                            $f(-4,  true),  // telephone
                                        ],
                                    ]],
                                ]],
                            ],
                        ],
                        // Рядок 2: 2 колонки
                        [
                            'id' => $rh(), 'columns' => ['desktop' => 2, 'tablet' => 1],
                            'cells' => [
                                ['id' => $ch(), 'blocks' => [
                                    ['id' => 'shipping_address', 'type' => 'shipping_address', 'settings' => [
                                        'show_company' => true,
                                        'fields' => [
                                            $f(-24, true,  true),  // address_1 (reload — для НП)
                                            $f(-25),               // address_2
                                            $f(-26, true),         // city
                                            $f(-27, true),         // postcode
                                            $f(-28, true,  true),  // country (reload zones)
                                            $f(-29, true),         // zone
                                        ],
                                    ]],
                                    ['id' => 'shipping',         'type' => 'shipping', 'settings' => [
                                        'display_mode'      => 'radio',
                                        'auto_select_first' => true,
                                        'show_description'  => true,
                                        'fields'            => [],
                                    ]],
                                    ['id' => 'payment',          'type' => 'payment', 'settings' => [
                                        'display_mode'      => 'radio',
                                        'auto_select_first' => true,
                                        'show_description'  => true,
                                        'fields'            => [],
                                    ]],
                                    ['id' => 'comment',          'type' => 'comment'],
                                ]],
                                ['id' => $ch(), 'blocks' => [
                                    ['id' => 'cart', 'type' => 'cart', 'settings' => [
                                        'show_image'             => true,
                                        'show_model'             => false,
                                        'show_quantity_controls' => true,
                                        'show_remove_btn'        => true,
                                        'show_subtotal'          => true,
                                    ]],
                                ]],
                            ],
                        ],
                        // Рядок 3: agreement (full-width)
                        [
                            'id' => $rh(), 'columns' => ['desktop' => 1, 'tablet' => 1],
                            'cells' => [
                                ['id' => $ch(), 'blocks' => [
                                    ['id' => 'agreement', 'type' => 'agreement', 'settings' => ['required' => true]],
                                ]],
                            ],
                        ],
                        // Рядок 4: buttons (full-width)
                        [
                            'id' => $rh(), 'columns' => ['desktop' => 1, 'tablet' => 1],
                            'cells' => [
                                ['id' => $ch(), 'blocks' => [['id' => 'buttons__1', 'type' => 'buttons']]],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lint layout: повертає попередження про broken-refs та структурні проблеми.
     * - Поля/заголовки, що посилаються на видалені id
     * - Умови (condition.source) що посилаються на неіснуючий field code
     * - Порожні cells / порожні rows / порожні steps
     * - Дубльовані field_id всередині одного шаблону (один field у кількох блоках)
     *
     * @param array  $layout       Нормалізований layout
     * @param int[]  $fieldIds     Існуючі field_id (з FieldsRepository)
     * @param array<string,int> $fieldCodes Map code → field_id існуючих полів
     * @param int[]  $headingIds   Існуючі heading_id (з HeadingsRepository)
     * @return array<int,array{type:string,message:string,where:string}>
     */
    public function lint(array $layout, array $fieldIds, array $fieldCodes, array $headingIds): array
    {
        $fieldIdSet   = array_flip(array_map('intval', $fieldIds));
        $headingIdSet = array_flip(array_map('intval', $headingIds));
        $warnings     = [];
        $usedFieldIds = [];

        foreach (($layout['steps'] ?? []) as $stepIdx => $step) {
            $stepLabel = 'step #' . ($stepIdx + 1);
            $stepNo    = $stepIdx + 1;
            $rowsCount = 0;
            foreach (($step['rows'] ?? []) as $rowIdx => $row) {
                $rowsCount++;
                $cellsCount = 0;
                foreach (($row['cells'] ?? []) as $cellIdx => $cell) {
                    $cellsCount++;
                    $blocks = $cell['blocks'] ?? [];
                    if (!$blocks) {
                        $warnings[] = [
                            'type'    => 'empty_cell',
                            'message' => 'Empty cell — has no blocks',
                            'where'   => "{$stepLabel} → row #" . ($rowIdx + 1) . " → cell #" . ($cellIdx + 1),
                            'loc'     => ['step' => $stepNo, 'row' => $rowIdx + 1, 'cell' => $cellIdx + 1],
                            'extra'   => [],
                        ];
                        continue;
                    }
                    foreach ($blocks as $block) {
                        $where     = "{$stepLabel} → " . ($block['type'] ?? 'block') . ' [' . ($block['id'] ?? '?') . ']';
                        $blockType = (string)($block['type'] ?? 'block');
                        $blockLoc  = ['step' => $stepNo, 'block_type' => $blockType, 'block_id' => (string)($block['id'] ?? '?')];

                        // Перевіряємо condition блоку
                        $bc = $block['condition'] ?? null;
                        if (is_array($bc) && !empty($bc['source_code']) && !isset($fieldCodes[$bc['source_code']])) {
                            $warnings[] = [
                                'type'    => 'block_condition_broken',
                                'message' => "Block visibility condition references deleted field code: " . $bc['source_code'],
                                'where'   => $where,
                                'loc'     => $blockLoc,
                                'extra'   => ['source_code' => (string)$bc['source_code']],
                            ];
                        }

                        // Перевіряємо fields всередині блоку
                        foreach (($block['fields'] ?? []) as $bf) {
                            $fid = (int)($bf['field_id'] ?? 0);
                            if (!$fid) continue;
                            if (!isset($fieldIdSet[$fid])) {
                                $warnings[] = [
                                    'type'    => 'field_missing',
                                    'message' => "Block references deleted field_id={$fid}",
                                    'where'   => $where,
                                    'loc'     => $blockLoc,
                                    'extra'   => ['field_id' => $fid],
                                ];
                                continue;
                            }
                            // Per-field condition
                            $fc = $bf['condition'] ?? null;
                            if (is_array($fc) && !empty($fc['source_code']) && !isset($fieldCodes[$fc['source_code']])) {
                                $warnings[] = [
                                    'type'    => 'field_condition_broken',
                                    'message' => "Field condition references deleted field code: " . $fc['source_code'],
                                    'where'   => $where . " (field_id={$fid})",
                                    'loc'     => $blockLoc,
                                    'extra'   => ['source_code' => (string)$fc['source_code'], 'field_id' => $fid],
                                ];
                            }
                            $usedFieldIds[$fid] = ($usedFieldIds[$fid] ?? 0) + 1;
                        }

                        // Heading-блок або heading_id у settings
                        $hid = (int)($block['settings']['heading_id'] ?? 0);
                        if ($hid && !isset($headingIdSet[$hid])) {
                            $warnings[] = [
                                'type'    => 'heading_missing',
                                'message' => "Block references deleted heading_id={$hid}",
                                'where'   => $where,
                                'loc'     => $blockLoc,
                                'extra'   => ['heading_id' => $hid],
                            ];
                        }
                    }
                }
                if ($cellsCount === 0) {
                    $warnings[] = [
                        'type'    => 'empty_row',
                        'message' => 'Empty row — has no cells',
                        'where'   => "{$stepLabel} → row #" . ($rowIdx + 1),
                        'loc'     => ['step' => $stepNo, 'row' => $rowIdx + 1],
                        'extra'   => [],
                    ];
                }
            }
            if ($rowsCount === 0) {
                $warnings[] = [
                    'type'    => 'empty_step',
                    'message' => 'Empty step — has no rows',
                    'where'   => $stepLabel,
                    'loc'     => ['step' => $stepNo],
                    'extra'   => [],
                ];
            }
        }

        // Duplicate field_id (одне поле в кількох місцях — частий джерело bugs)
        foreach ($usedFieldIds as $fid => $count) {
            if ($count > 1) {
                $warnings[] = [
                    'type'    => 'field_duplicate',
                    'message' => "Field_id={$fid} appears in {$count} blocks — likely unintended duplicate",
                    'where'   => 'multiple blocks',
                    'loc'     => [],
                    'extra'   => ['field_id' => $fid, 'count' => $count],
                ];
            }
        }

        return $warnings;
    }
}
