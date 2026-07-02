<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';

use OcKit\EasyLogin\EasyLogin;

class ModelExtensionModuleOcKitEasyLogin extends Model
{
    private ?EasyLogin $lib = null;

    private function getLib(): EasyLogin
    {
        if ($this->lib === null) {
            $this->lib = new EasyLogin($this->registry);
        }
        return $this->lib;
    }

    public function install(): void
    {
        $this->getLib()->install();
    }

    public function uninstall(): void
    {
        $this->getLib()->uninstall();
    }

    public function getLogEntries(array $filter): array
    {
        return $this->getLib()->getLogger()->getEntries($filter);
    }

    public function getLogTotal(array $filter): int
    {
        return $this->getLib()->getLogger()->getTotal($filter);
    }

    public function getLogStats(): array
    {
        return $this->getLib()->getLogger()->getStats();
    }

    public function clearLog(): int
    {
        return $this->getLib()->getLogger()->clearAll();
    }

    public function clearOldLog(int $retentionDays): int
    {
        return $this->getLib()->getLogger()->clearOld($retentionDays);
    }
}
