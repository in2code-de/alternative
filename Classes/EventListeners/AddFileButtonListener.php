<?php

declare(strict_types=1);

namespace In2code\Alternative\EventListeners;

use In2code\Alternative\Utility\BackendUtility;
use In2code\Alternative\Utility\ConfigurationUtility;
use In2code\Alternative\Utility\FileUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
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
            $this->addButton($event);
            $this->addButtonLegacy($event);
        }
    }

    protected function addButton(ProcessFileListActionsEvent $event): void
    {
        // Todo: If condition can be always true (removed) once TYPO3 13 support is dropped
        if ((new Typo3Version())->getMajorVersion() > 13) {
            $componentGroup = $event->getActionGroup(ActionGroup::primary);
            $componentGroup->add('metadata2', $this->getButton($event, $componentGroup->get('metadata')));
        }
    }

    protected function getButton(ProcessFileListActionsEvent $event, LinkButton $button): LinkButton
    {
        $buttonNew = clone $button;
        return $buttonNew
            ->setTitle(LocalizationUtility::translate(
                'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:button.translate')
            )
            ->setIcon($this->iconFactory->getIcon('actions-translate', IconSize::SMALL))
            ->setHref(
                (string)$this->uriBuilder->buildUriFromRoute(
                    'alternative_Module.Module_addMetadata',
                    ['file' => $event->getResource()->getUid()]
                )
            );
    }

    /**
     * Todo: Can be removed once TYPO3 13 support is dropped
     *
     * @param ProcessFileListActionsEvent $event
     * @return void
     */
    protected function addButtonLegacy(ProcessFileListActionsEvent $event): void
    {
        if ((new Typo3Version())->getMajorVersion() === 13) {
            $actionItems = $event->getActionItems();
            $actionItems = $this->insertArrayIntoArray(
                $actionItems,
                ['metadata2' => $this->getButton($event, $actionItems['metadata'])]
            );
            $event->setActionItems($actionItems);
        }
    }

    /**
     * Todo: Can be removed once TYPO3 13 support is dropped
     *
     * @param array $existingArray
     * @param array $addArray
     * @param string $after
     * @return array
     */
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
