<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Repository\Llm;

use In2code\Alternative\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;

abstract class AbstractRepository
{
    protected string $requestMethod = 'POST';
    protected array $fields = [
        'title' => 'tile (max 75 characters)',
        'description' => 'description (max 255 characters)',
        'alternativeText' => 'alternative text (max 100 characters)',
    ];
    protected string $languageCode = ''; // e.g. "en"

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {
    }

    protected function getPrompt(): string
    {
        $prompt = 'Analyze this image and provide ' . $this->getFieldValues() . '. ';
        $prompt .= 'Return as JSON with keys: ' . $this->getFieldKeys() . ' ';
        $prompt .= 'Answer in language ' . $this->getLanguageCode() . ' (ISO 639) only!';
        return $prompt;
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
        $fields = $this->fields;
        if (ConfigurationUtility::getConfigurationByKey('setAlternative') === '0') {
            unset($fields['alternativeText']);
        }
        if (ConfigurationUtility::getConfigurationByKey('setTitle') === '0') {
            unset($fields['title']);
        }
        if (ConfigurationUtility::getConfigurationByKey('setDescription') === '0') {
            unset($fields['description']);
        }
        return $fields;
    }

    protected function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    protected function setLanguageCode(string $languageCode): void
    {
        $this->languageCode = $languageCode;
    }
}
