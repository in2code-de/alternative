<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Repository\Llm;

use In2code\Alternative\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractRepository
{
    protected const DEFAULT_MAX_LENGTH_TITLE = 50;
    protected const DEFAULT_MAX_LENGTH_ALTERNATIVE = 125;
    protected const DEFAULT_MAX_LENGTH_DESCRIPTION = 255;

    protected string $requestMethod = 'POST';

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {
    }

    protected function getPrompt(array $languageCodes): string
    {
        $prompt = $this->getPromptPrefix();
        $prompt .= 'Analyze this image and provide ' . $this->getFieldValues() . '. ';
        $prompt .= 'Return as JSON object with language codes as keys. ';
        $prompt .= 'Required languages: ' . implode(', ', $languageCodes) . '. ';
        $prompt .= 'Each language must have keys: ' . $this->getFieldKeys() . '. ';
        $prompt .= 'Example: {"en": {"title": "...", "description": "...", "alternativeText": "..."}, "de": {...}}';
        return $prompt;
    }

    protected function getPromptPrefix(): string
    {
        $content = '';
        $filePath = ConfigurationUtility::getConfigurationByKey('promptPrefixFile');
        if ($filePath !== '') {
            $absolutePath = GeneralUtility::getFileAbsFileName($filePath);
            if ($absolutePath !== '' && is_file($absolutePath) && is_readable($absolutePath)) {
                $contentFile = file_get_contents($absolutePath);
                if ($contentFile !== false && $contentFile !== '') {
                    $content = trim($contentFile) . PHP_EOL . PHP_EOL;
                }
            }
        }
        return $content;
    }

    protected function getFieldValues(): string
    {
        return implode(', ', array_values($this->getFields()));
    }

    protected function getFieldKeys(): string
    {
        return implode(', ', array_keys($this->getFields()));
    }

    protected function getFields(): array
    {
        $fields = [];
        if (ConfigurationUtility::getConfigurationByKey('setTitle') !== '0') {
            $maxLength = (int)(ConfigurationUtility::getConfigurationByKey('maxLengthTitle') ?: self::DEFAULT_MAX_LENGTH_TITLE);
            $fields['title'] = 'title (max ' . $maxLength . ' characters)';
        }
        if (ConfigurationUtility::getConfigurationByKey('setDescription') !== '0') {
            $maxLength = (int)(ConfigurationUtility::getConfigurationByKey('maxLengthDescription') ?: self::DEFAULT_MAX_LENGTH_DESCRIPTION);
            $fields['description'] = 'description (max ' . $maxLength . ' characters)';
        }
        if (ConfigurationUtility::getConfigurationByKey('setAlternative') !== '0') {
            $maxLength = (int)(ConfigurationUtility::getConfigurationByKey('maxLengthAlternative') ?: self::DEFAULT_MAX_LENGTH_ALTERNATIVE);
            $fields['alternativeText'] = 'alternative text (max ' . $maxLength . ' characters)';
        }
        return $fields;
    }
}
