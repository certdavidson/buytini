<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

require_once __DIR__ . '/CustomMethodsRepository.php';

/**
 * Catalog-сторона кастомних методів: фільтрує enabled-методи за умовами,
 * рахує вартість доставки, повертає у форматі сумісному з checkout-блоками.
 * Без залежності від $this->currency — конвертацію валюти робить контролер.
 */
final class CustomMethodsCatalog
{
    /** @var \DB */
    private $db;
    private CustomMethodsRepository $repo;

    public function __construct($db)
    {
        $this->db   = $db;
        $this->repo = new CustomMethodsRepository($db);
    }

    /**
     * @param array $ctx ['language_id'=>int, 'state'=>array, 'total'=>float, 'weight'=>float]
     *   state — асоц. масив значень для умов (country_id, zone_id, total, weight, shipping_method, ...)
     * @return array<int,array> у форматі buildShippingMethods() записів
     */
    public function getShipping(array $ctx): array
    {
        $langId = (int)($ctx['language_id'] ?? 0);
        $out = [];
        foreach ($this->repo->listMethods(CustomMethodsRepository::TYPE_SHIPPING) as $m) {
            if (!$m['status']) continue;
            $available = $this->passesConditions($m['conditions'], $m['condition_expr'], (array)($ctx['state'] ?? []));
            // smart placeholder: показуємо навіть коли недоступний
            if (!$available && !$m['placeholder_always'] && !$m['placeholder_unavailable']) continue;

            $cost = $this->computeShippingCost($m, $ctx, $available);
            if ($cost === null && !$m['placeholder_always'] && !$m['placeholder_unavailable']) continue;

            $d     = $m['descriptions'][$langId] ?? (reset($m['descriptions']) ?: []);
            $name  = (string)($d['name'] ?? $m['code']);
            $isPlaceholder = !$available || $cost === null;
            $costVal = $cost === null ? 0.0 : (float)$cost;

            $optCode = $m['code'] . '.' . $m['code'];   // resolveShipping вимагає крапку
            $text = $costVal <= 0 && !empty($d['zero_cost_text'])
                ? (string)$d['zero_cost_text']
                : '';   // контролер відформатує ціну якщо text порожній

            $out[] = [
                'code'    => $m['code'],
                'title'   => $name,
                'options' => [[
                    'code'         => $optCode,
                    'title'        => $name,
                    'cost'         => $costVal,
                    'tax_class_id' => (int)$m['tax_class_id'],
                    'text'         => $text,
                    'currency'     => (string)$m['currency_code'],
                    'placeholder'  => $isPlaceholder,
                    'description'  => (string)($d['description'] ?? ''),
                    'icon'         => (string)($m['params']['icon'] ?? ''),
                ]],
                'error'   => '',
                'sort'    => (int)$m['sort_order'],
                'custom'  => true,
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array> у форматі buildPaymentMethods() записів
     */
    public function getPayment(array $ctx): array
    {
        $langId = (int)($ctx['language_id'] ?? 0);
        $out = [];
        foreach ($this->repo->listMethods(CustomMethodsRepository::TYPE_PAYMENT) as $m) {
            if (!$m['status']) continue;
            $available = $this->passesConditions($m['conditions'], $m['condition_expr'], (array)($ctx['state'] ?? []));
            if (!$available && !$m['placeholder_always'] && !$m['placeholder_unavailable']) continue;

            $d    = $m['descriptions'][$langId] ?? (reset($m['descriptions']) ?: []);
            $name = (string)($d['name'] ?? $m['code']);

            $out[] = [
                'code'            => $m['code'],
                'title'           => $name,
                'terms'           => '',
                'sort'            => (int)$m['sort_order'],
                'custom'          => true,
                'order_status_id' => (int)$m['order_status_id'],
                'description'     => (string)($d['description'] ?? ''),
                'icon'            => (string)($m['params']['icon'] ?? ''),
                'form_heading'    => (string)($d['payment_form_heading'] ?? ''),
                'info_form'       => (string)($d['payment_info_form'] ?? ''),
                'info_mail'       => (string)($d['payment_info_mail'] ?? ''),
                'placeholder'     => !$available,
            ];
        }
        return $out;
    }

    /**
     * Рядки підсумку («Облік у замовленні»).
     * rules: { value: "-1.3%"|"50", round: 0|1, conditions: {match, rules[]} }
     * Показується коли умови проходять (умови можуть включати payment_method,
     * shipping_method тощо — звідси прив'язка до обраного варіанта).
     * @param array $ctx ['language_id', 'state', 'sub_total']
     * @return array<int,array{title:string,value:float,sort_order:int}>
     */
    public function getSubtotalRows(array $ctx): array
    {
        $langId = (int)($ctx['language_id'] ?? 0);
        $subTot = (float)($ctx['sub_total'] ?? 0);
        $state  = (array)($ctx['state'] ?? []);
        $out = [];
        foreach ($this->repo->listSubtotals() as $s) {
            if (!$s['status']) continue;
            $rules = is_array($s['rules']) ? $s['rules'] : [];
            if (!$this->passesConditions($rules['conditions'] ?? null, '', $state)) continue;

            $value = $this->parseSubtotalValue((string)($rules['value'] ?? ''), $subTot);
            if (!empty($rules['round'])) $value = round($value);
            if ($value == 0.0) continue;

            $d = $s['descriptions'][$langId] ?? (reset($s['descriptions']) ?: []);
            $out[] = [
                'title'      => (string)($d['name'] ?? ''),
                'value'      => $value,
                'sort_order' => (int)$s['sort_order'],
            ];
        }
        return $out;
    }

    /** "-1.3%" → відсоток від суми; "50"/"-50" → фікс. */
    private function parseSubtotalValue(string $raw, float $base): float
    {
        $raw = trim($raw);
        if ($raw === '') return 0.0;
        if (substr($raw, -1) === '%') {
            $pct = (float)str_replace('%', '', $raw);
            return $base * $pct / 100.0;
        }
        return (float)$raw;
    }

    /** Повертає method-row за code (для confirm). */
    public function findByCode(string $type, string $code): ?array
    {
        foreach ($this->repo->listMethods($type) as $m) {
            if ($m['code'] === $code) return $m;
        }
        return null;
    }

    // ─── cost ───────────────────────────────────────────────────────────────

    /** @return float|null null = недоступний (немає правила під метрику) */
    private function computeShippingCost(array $m, array $ctx, bool $available): ?float
    {
        if (!$available) return null;
        switch ($m['cost_type']) {
            case CustomMethodsRepository::COST_FIXED:
                return (float)$m['cost_value'];
            case CustomMethodsRepository::COST_WEIGHT:
                return $this->tierCost($m['cost_rules'], (float)($ctx['weight'] ?? 0));
            case CustomMethodsRepository::COST_SUM:
            case CustomMethodsRepository::COST_SUM_TOTALS:
                return $this->tierCost($m['cost_rules'], (float)($ctx['total'] ?? 0));
            case CustomMethodsRepository::COST_API:
                // Розрахунок через зовнішній API — наступне оновлення; поки 0
                return 0.0;
        }
        return (float)$m['cost_value'];
    }

    /** rules: [{from,to,cost}], повертає cost першого діапазону що містить $metric. */
    private function tierCost($rules, float $metric): ?float
    {
        if (!is_array($rules)) return null;
        foreach ($rules as $r) {
            $from = isset($r['from']) ? (float)$r['from'] : 0.0;
            $to   = isset($r['to']) && $r['to'] !== '' ? (float)$r['to'] : INF;
            if ($metric >= $from && $metric <= $to) return (float)($r['cost'] ?? 0);
        }
        return null;
    }

    // ─── conditions (формат {match, rules[]}) ────────────────────────────────

    private function passesConditions($conditions, string $expr, array $state): bool
    {
        if (!is_array($conditions) || empty($conditions['rules'])) return true;
        $match   = ($conditions['match'] ?? 'all') === 'any' ? 'any' : 'all';
        $results = [];
        foreach ($conditions['rules'] as $rule) {
            $src = (string)($rule['source_code'] ?? '');
            if ($src === '') { $results[] = true; continue; }
            $results[] = $this->evalRule(
                (string)($state[$src] ?? ''),
                (string)($rule['operator'] ?? '=='),
                (string)($rule['value'] ?? '')
            );
        }
        return $match === 'any'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    private function evalRule(string $sourceVal, string $op, string $expected): bool
    {
        switch ($op) {
            case '==':        return $sourceVal === $expected;
            case '!=':        return $sourceVal !== $expected;
            case 'not_empty': return $sourceVal !== '';
            case 'empty':     return $sourceVal === '';
            case 'in':        return in_array($sourceVal, array_map('trim', explode(',', $expected)), true);
        }
        return true;
    }
}
