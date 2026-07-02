<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

use OcKit\TranslaterPro\Exceptions\ApiException;

/**
 * DeepSeek uses OpenAI-compatible API, so we extend/reuse the same logic.
 */
class DeepSeekClient extends OpenAiClient
{
    public function __construct(string $apiKey, string $model = 'deepseek-chat')
    {
        parent::__construct($apiKey, $model, 'https://api.deepseek.com/v1');
    }
}
