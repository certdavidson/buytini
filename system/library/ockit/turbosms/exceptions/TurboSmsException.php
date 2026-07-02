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

namespace OcKit\TurboSms\Exceptions;

/**
 * Базовий виняток TurboSMS-бібліотеки.
 *
 * $code відповідає response_code з API (0 = успіх, інше — помилка).
 */
class TurboSmsException extends \RuntimeException
{
}
