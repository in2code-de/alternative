<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Repository\Llm;

use In2code\Alternative\Exception\ApiException;
use In2code\Alternative\Exception\ConfigurationException;
use In2code\Alternative\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;

class GeminiRepository extends AbstractRepository implements RepositoryInterface
{
    private string $apiKey = '';
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1/models/';
    private string $model = 'gemini-2.5-flash:generateContent';

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {
        parent::__construct($requestFactory);
        $this->apiKey = getenv('GOOGLE_API_KEY') ?: ConfigurationUtility::getConfigurationByKey('apiKey');
    }

    public function checkApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('Google API key not configured', 1764254037);
        }
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl . $this->model . '?key=' . $this->apiKey;
    }

    public function analyzeImage(File $file, string $languageCode): array
    {
        $this->checkApiKey();
        $this->setLanguageCode($languageCode);
        $imageData = base64_encode($file->getContents());
        return $this->generateMetadataWithGemini($imageData, $file->getMimeType());
    }

    /**
     * Example return value:
     *  [
     *      'title' => 'foo',
     *      'description' => 'bar',
     *      'alternativeText' => 'baz',
     *  ]
     *
     * @param string $imageData
     * @param string $mimeType
     * @return array
     * @throws ApiException
     */
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
        $response = $this->requestFactory->request($this->getApiUrl(), $this->requestMethod, $additionalOptions);
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
}
