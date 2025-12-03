<?php

declare(strict_types=1);

namespace In2code\Alternative\EventListeners;

use In2code\Alternative\Utility\BackendUtility;
use In2code\Alternative\Utility\ConfigurationUtility;
use In2code\Alternative\Utility\FileUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

#[AsEventListener(
    identifier: 'alternative/filelist-button',
    event: ProcessFileListActionsEvent::class,
)]
class AddFileButtonListener
{
    public function __construct(
        readonly private IconFactory $iconFactory,
        readonly private UriBuilder $uriBuilder,
    ) {
    }

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if ($this->isActivated($event->getResource())) {
            $actionItems = $event->getActionItems();
            $actionItems = $this->insertArrayIntoArray(
                $actionItems,
                ['metadata2' => $this->getButton($event->getResource(), $actionItems['metadata'])]
            );
            $event->setActionItems($actionItems);
        }
    }

    protected function getButton(ResourceInterface $resource, LinkButton $button): LinkButton
    {
        $buttonNew = clone $button;
        return $buttonNew
            ->setTitle(LocalizationUtility::translate('LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:button.translate'))
            ->setIcon($this->iconFactory->getIcon('actions-translate', IconSize::SMALL))
            ->setHref($this->uriBuilder->buildUriFromRoute('alternative_Module.Module_addMetadata', ['file' => $resource->getUid()]));
    }

    protected function insertArrayIntoArray(array $existingArray, array $addArray, string $after = 'metadata'): array
    {
        if (array_key_exists($after, $existingArray) === false) {
            return $existingArray;
        }
        $position = array_search($after, array_keys($existingArray)) + 1;
        $firstPart = array_slice($existingArray, 0, $position, true);
        $lastPart = array_slice($existingArray, $position, null, true);
        return array_merge($firstPart, $addArray, $lastPart);
    }

    protected function isActivated(ResourceInterface $file): bool
    {
        return is_a($file, File::class, true)
            && FileUtility::isImage($file)
            && $this->hasAccessToModule()
            && ConfigurationUtility::getConfigurationByKey('showButtonInFileList') === '1';
    }

    protected function hasAccessToModule(): bool
    {
        $backendUser = BackendUtility::getBackendUserAuthentication();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($backendUser !== null) {
            return $backendUser->check('modules', 'alternative_Module');
        }
        return false;
    }
}
