<?php

declare(strict_types=1);

namespace In2code\Alternative\Controller;

use In2code\Alternative\Domain\Service\AlternativeService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
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
}
