<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Dto;

/**
 * Immutable result of an authentication attempt — populated once in the
 * constructor; treat as readonly (PHP 7.4 cannot enforce `readonly` keyword).
 */
class AuthResult
{
    /** @readonly */ public bool $success;
    /** @readonly */ public int $customerId;
    /** @readonly */ public string $email;
    /** @readonly */ public string $action;       // 'login' | 'registered' | 'linked' | 'needs_confirmation'
    /** @readonly */ public string $message;

    public function __construct(array $data)
    {
        $this->success    = (bool)($data['success'] ?? false);
        $this->customerId = (int)($data['customer_id'] ?? 0);
        $this->email      = (string)($data['email'] ?? '');
        $this->action     = (string)($data['action'] ?? '');
        $this->message    = (string)($data['message'] ?? '');
    }
}
