<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Repository;

use In2code\Alternative\Exception\ApiException;
use In2code\Alternative\Exception\ConfigurationException;
use In2code\Alternative\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;

class LlmRepository
{
    private string $apiKey = '';
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1/models/';
    private string $model = 'gemini-2.5-flash:generateContent';
    private array $fields = [
        'title' => 'tile (max 255 characters)',
        'description' => 'description (max 1024 characters)',
        'alternativeText' => 'alternative text (max 255 characters)',
    ];
    private string $languageCode = ''; // e.g. "en"

    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {
        $this->apiKey = getenv('GOOGLE_API_KEY') ?: ConfigurationUtility::getConfigurationByKey('apiKey');
    }

    public function analyzeImage(File $file, string $languageCode): array
    {
        $this->checkApiKey();
        $this->languageCode = $languageCode;
        $imageData = base64_encode($file->getContents());
        return $this->generateMetadataWithGemini($imageData, $file->getMimeType());
    }

    protected function generateMetadataWithGemini(string $imageData, string $mimeType): array
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->getPrompt(),
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $additionalOptions = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ];
        $response = $this->requestFactory->request($this->getApiUrl(), 'POST', $additionalOptions);
        if ($response->getStatusCode() !== 200) {
            throw new ApiException('Failed to analyze image: ' . $response->getBody()->getContents(), 1764248501);
        }
        $responseData = json_decode($response->getBody()->getContents(), true);
        if (isset($responseData['candidates'][0]['content']['parts']) === false) {
            throw new ApiException('Invalid response from Gemini API: ' . json_encode($responseData), 1764248502);
        }
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = trim($text, '` ');
        $text = preg_replace('/^json\s*/i', '', $text);
        $data = json_decode($text, true);
        if ($data === null) {
            throw new ApiException('Failed to parse JSON response: ' . $text, 1764248503);
        }
        return [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'alternativeText' => $data['alternativeText'] ?? '',
        ];
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

    protected function getApiUrl(): string
    {
        return $this->apiUrl . $this->model . '?key=' . $this->apiKey;
    }

    protected function checkApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('Google API key not configured', 1764254037);
        }
    }
}
