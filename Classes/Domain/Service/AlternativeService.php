<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Service;

use In2code\Alternative\Domain\Repository\LlmRepository;
use In2code\Alternative\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AlternativeService
{
    const TABLE = 'sys_file_metadata';
    protected bool $enforce = false;

    public function __construct(
        readonly private LlmRepository $llmRepository,
        readonly private SiteFinder $siteFinder,
    ) {
    }

    public function setImageMetadata(File $image, bool $enforce): bool
    {
        $changed = false;
        $this->enforce = $enforce;
        $metadata = $image->getMetaData();
        $properties = $metadata->get();

        foreach ($this->getAllLanguages() as $languageId => $languageCode) {
            if ($languageId === 0) {
                if ($this->shouldUpdate($properties)) {
                    $labels = $this->llmRepository->analyzeImage($image, $languageCode);
                    $this->updateMetadata($properties['uid'], $labels);
                    $changed = true;
                }
            } else {
                $translation = $this->getOrCreateTranslation($properties['uid'], $languageId, $image->getUid());
                if ($this->shouldUpdate($translation)) {
                    $labels = $this->llmRepository->analyzeImage($image, $languageCode);
                    $this->updateMetadata($translation['uid'], $labels);
                    $changed = true;
                }
            }
        }
        return $changed;
    }

    protected function updateMetadata(int $uid, array $labels): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $connection->update(
            self::TABLE,
            [
                'title' => $labels['title'],
                'description' => $labels['description'],
                'alternative' => $labels['alternativeText'],
            ],
            [
                'uid' => $uid,
            ]
        );
    }

    protected function getOrCreateTranslation(int $metadataUid, int $languageUid, int $fileUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $translation = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($metadataUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        if ($translation !== false) {
            return $translation;
        }
        return $this->createTranslation($metadataUid, $languageUid, $fileUid);
    }

    protected function createTranslation(int $metadataUid, int $languageUid, int $fileUid): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $data = [
            'file' => $fileUid,
            'sys_language_uid' => $languageUid,
            'l10n_parent' => $metadataUid,
            'pid' => 0,
            'crdate' => time(),
            'tstamp' => time(),
        ];
        $connection->insert(self::TABLE, $data);
        $data['uid'] = (int)$connection->lastInsertId();
        return $data;
    }

    /**
     *  [
     *      0 => 'en',
     *      12 => 'de',
     *      15 => 'es',
     *  ]
     *
     * @return array
     */
    protected function getAllLanguages(): array
    {
        $allowed = $this->getAllowedLanguageIds();
        $languages = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getLanguages() as $language) {
                $languageId = $language->getLanguageId();
                if (
                    array_key_exists($languageId, $languages) === false &&
                    ($allowed === [] || in_array($languageId, $allowed, true))
                ) {
                    $languages[$languageId] = $language->getLocale()->getLanguageCode();
                }
            }
        }
        return $languages;
    }

    protected function getAllowedLanguageIds(): array
    {
        $allowed = [];
        $languageString = ConfigurationUtility::getConfigurationByKey('limitToLanguages');
        if ($languageString !== '') {
            $allowed = GeneralUtility::intExplode(',', $languageString);
        }
        return $allowed;
    }

    protected function shouldUpdate(array $properties): bool
    {
        if ($this->enforce) {
            return true;
        }
        return empty($properties['title']) && empty($properties['description']) && empty($properties['alternative']);
    }
}
