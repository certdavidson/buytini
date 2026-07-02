<?php
/**
 * TurboSMS PHP Library
 *
 * @package   OcKit\TurboSms
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Комерційна ліцензія — див. LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\TurboSms;

/**
 * Value object — відповідь API після відправки повідомлення.
 *
 * Один TurboSmsResponse відповідає одному отримувачу з масиву response_result.
 */
class TurboSmsResponse
{
    /** @var string  Номер телефону у форматі 380XXXXXXXXX */
    public $phone;

    /** @var string  Унікальний ID повідомлення для трекінгу */
    public $messageId;

    /** @var string  Статус прийому: 'accepted', 'rejected', тощо */
    public $status;

    /** @var bool  true, якщо повідомлення прийнято оператором */
    public $accepted;

    public function __construct(array $row)
    {
        $this->phone     = (string)($row['phone']      ?? '');
        $this->messageId = (string)($row['message_id'] ?? '');
        $this->status    = (string)($row['status']     ?? '');
        $this->accepted  = ($this->status === 'accepted');
    }
}
