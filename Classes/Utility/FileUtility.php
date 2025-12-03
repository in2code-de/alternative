<?php

declare(strict_types=1);

namespace In2code\Alternative\Utility;

use TYPO3\CMS\Core\Resource\File;

class FileUtility
{
    protected static array $imageExtensions = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    public static function isImage(File $file): bool
    {
        return in_array($file->getExtension(), self::$imageExtensions);
    }
}
