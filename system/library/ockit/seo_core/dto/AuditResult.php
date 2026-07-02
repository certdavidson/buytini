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

namespace OcKit\SeoCore\Dto;

/**
 * Immutable representation of a single audit issue row.
 */
final class AuditResult
{
    public int    $resultId;
    public int    $storeId;
    public int    $languageId;
    public string $entityType;
    public int    $entityId;
    public string $entityName;
    public string $issueType;
    public string $severity;   // error | warning | info
    public string $status;     // new | in_progress | fixed | ignored
    public string $detail;
    public string $createdAt;

    public function __construct(array $row)
    {
        $this->resultId   = (int)($row['result_id']   ?? 0);
        $this->storeId    = (int)($row['store_id']    ?? 0);
        $this->languageId = (int)($row['language_id'] ?? 0);
        $this->entityType = (string)($row['entity_type'] ?? '');
        $this->entityId   = (int)($row['entity_id']   ?? 0);
        $this->entityName = (string)($row['entity_name'] ?? '');
        $this->issueType  = (string)($row['issue_type']  ?? '');
        $this->severity   = (string)($row['severity']    ?? 'info');
        $this->status     = (string)($row['status']      ?? 'new');
        $this->detail     = (string)($row['detail']      ?? '');
        $this->createdAt  = (string)($row['created_at']  ?? '');
    }

    public function toArray(): array
    {
        return [
            'result_id'   => $this->resultId,
            'store_id'    => $this->storeId,
            'language_id' => $this->languageId,
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'entity_name' => $this->entityName,
            'issue_type'  => $this->issueType,
            'severity'    => $this->severity,
            'status'      => $this->status,
            'detail'      => $this->detail,
            'created_at'  => $this->createdAt,
        ];
    }
}
