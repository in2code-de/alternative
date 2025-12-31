<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Repository\Llm;

use TYPO3\CMS\Core\Resource\File;

interface RepositoryInterface
{
    public function checkApiKey(): void;
    public function getApiUrl(): string;
    public function analyzeImage(File $file, string $languageCode): array;
}
