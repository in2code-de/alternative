<?php

declare(strict_types=1);

namespace In2code\Alternative\Controller;

use In2code\Alternative\Domain\Service\AlternativeService;
use In2code\Alternative\Utility\FileUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ModuleController extends ActionController
{
    public function __construct(
        readonly protected AlternativeService $alternativeService,
        readonly protected FileRepository $fileRepository,
        readonly protected FlashMessageService $flashMessageService,
        readonly protected UriBuilder $uriBuilderCore,
        readonly protected ResourceFactory $resourceFactory,
    ) {
    }

    public function addMetadataAction(int $file): ResponseInterface
    {
        $file = $this->fileRepository->findByUid($file);
        $result = $this->alternativeService->setImageMetadata($file, false);
        if ($result) {
            $this->addMessage(LocalizationUtility::translate(
                'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:action.finished'
            ));
        } else {
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:action.notfinished'
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
        return $this->openFileList($file);
    }
    public function addMetadataFromFolderAction(string $folderName): ResponseInterface
    {
        $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderName);
        $this->addMetaDataToFolder($folder);

        return $this->redirectToUri($this->uriBuilderCore->buildUriFromRoute('media_management', ['id' => $folder->getParentFolder()->getCombinedIdentifier()]));
    }

    protected function openFileList(File $file): ResponseInterface
    {
        $arguments = ['id' => $file->getParentFolder()->getCombinedIdentifier()];
        $uri = $this->uriBuilderCore->buildUriFromRoute('media_management', $arguments);
        return $this->redirectToUri($uri);
    }

    protected function addMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $message = GeneralUtility::makeInstance(FlashMessage::class, $message, 'Metadata', $severity, true);
        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }

    protected function addMetaDataToFolder(Folder $folder): void
    {
        $errors = 0;
        $successes = 0;
        foreach ($folder->getFiles() as $file) {
            if (FileUtility::isImage($file)) {
                if ($this->alternativeService->setImageMetadata($file, false)) {
                    $successes++;
                } else {
                    $errors++;
                }
            }
        }

        if ($successes) {
            $singular = $successes === 1 ? '_singular' : '';
            $this->addMessage(LocalizationUtility::translate(
                'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:bulk_action' . $singular . '.finished', 'alternative',  [$successes]
            ));
        }
        if ($errors) {
            $singular = $errors === 1 ? '_singular' : '';
            $this->addMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:bulk_action' . $singular . '.notfinished', 'alternative',  [$errors]
                ),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }
}
