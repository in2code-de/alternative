<?php

declare(strict_types=1);

namespace In2code\Alternative\Utility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class BackendUtility
{
    /**
     * @return BackendUserAuthentication|null
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getBackendUserAuthentication(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
