<?php

declare(strict_types = 1);

namespace In2code\Alternative\EventListeners;

use In2code\Alternative\Utility\BackendUtility;
use In2code\Alternative\Utility\ConfigurationUtility;
use In2code\Alternative\Utility\FileUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

#[AsEventListener(
    identifier: 'alternative/filelist-button',
    event: ProcessFileListActionsEvent::class,
)]
class AddFileButtonListener
{
    private const BUTTON_IDENTIFIER = 'metadata2';
    private const CORE_METADATA_IDENTIFIER = 'metadata';
    private const CORE_DELETE_IDENTIFIER = 'delete';
    private const ICON_IDENTIFIER = 'extension-alternative-icon-magic';
    private const LABEL = 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:button.translate';
    private const ROUTE_FILE = 'alternative_Module.Module_addMetadata';
    private const ROUTE_FOLDER = 'alternative_Module.Module_addMetadataFromFolder';
    private const MODULE_IDENTIFIER = 'alternative_Module';

    public function __construct(
        readonly private IconFactory $iconFactory,
        readonly private UriBuilder $uriBuilder,
    ) {
    }

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if ($this->isActivated($event->getResource())) {
            // Todo: Version check and addButtonLegacy() can be removed once TYPO3 13 support is dropped
            if ((new Typo3Version())->getMajorVersion() > 13) {
                $this->addButton($event);
            } else {
                $this->addButtonLegacy($event);
            }
        }
    }

    protected function addButton(ProcessFileListActionsEvent $event): void
    {
        $button = GeneralUtility::makeInstance(ComponentFactory::class)->createLinkButton();
        $event->setAction(
            $this->configureButton($button, $event->getResource()),
            self::BUTTON_IDENTIFIER,
            ActionGroup::primary,
            after: $this->getPositionAfter($event->getResource())
        );
    }

    /**
     * Files: Show button after "edit metadata", Folders: Show button after "delete"
     *
     * @param ResourceInterface $resource
     * @return string
     */
    protected function getPositionAfter(ResourceInterface $resource): string
    {
        return $resource instanceof Folder ? self::CORE_DELETE_IDENTIFIER : self::CORE_METADATA_IDENTIFIER;
    }

    /**
     * Todo: Can be removed once TYPO3 13 support is dropped
     *
     * @param ProcessFileListActionsEvent $event
     * @return void
     */
    protected function addButtonLegacy(ProcessFileListActionsEvent $event): void
    {
        $actionItems = $event->getActionItems();
        $metadataButton = $actionItems[self::CORE_METADATA_IDENTIFIER] ?? null;
        $button = $metadataButton instanceof LinkButton
            ? clone $metadataButton
            : GeneralUtility::makeInstance(LinkButton::class);
        $actionItems = $this->insertArrayIntoArray(
            $actionItems,
            [self::BUTTON_IDENTIFIER => $this->configureButton($button, $event->getResource())],
            $this->getPositionAfter($event->getResource())
        );
        $event->setActionItems($actionItems);
    }

    protected function configureButton(LinkButton $button, ResourceInterface $resource): LinkButton
    {
        return $button
            ->setTitle(LocalizationUtility::translate(self::LABEL) ?? '')
            ->setIcon($this->iconFactory->getIcon(self::ICON_IDENTIFIER, IconSize::SMALL))
            ->setHref($this->getUri($resource));
    }

    /**
     * @param File|Folder $resource
     * @return string
     */
    protected function getUri(ResourceInterface $resource): string
    {
        if ($resource instanceof Folder) {
            $uri = $this->uriBuilder->buildUriFromRoute(
                self::ROUTE_FOLDER,
                ['folderName' => $resource->getCombinedIdentifier()]
            );
        } else {
            $uri = $this->uriBuilder->buildUriFromRoute(self::ROUTE_FILE, ['file' => $resource->getUid()]);
        }
        return (string)$uri;
    }

    /**
     * Insert $addArray after key $after or append it if $after does not exist
     *
     * Todo: Can be removed once TYPO3 13 support is dropped
     *
     * @param array $existingArray
     * @param array $addArray
     * @param string $after
     * @return array
     */
    protected function insertArrayIntoArray(array $existingArray, array $addArray, string $after): array
    {
        $position = count($existingArray);
        if (array_key_exists($after, $existingArray)) {
            $position = array_search($after, array_keys($existingArray), true) + 1;
        }
        $firstPart = array_slice($existingArray, 0, $position, true);
        $lastPart = array_slice($existingArray, $position, null, true);
        return array_merge($firstPart, $addArray, $lastPart);
    }

    protected function isActivated(ResourceInterface $resource): bool
    {
        return (($resource instanceof File && FileUtility::isImage($resource)) || $resource instanceof Folder)
            && $this->hasAccessToModule()
            && ConfigurationUtility::getConfigurationByKey('showButtonInFileList') === '1';
    }

    protected function hasAccessToModule(): bool
    {
        $backendUser = BackendUtility::getBackendUserAuthentication();
        return $backendUser !== null
            && ($backendUser->isAdmin() || $backendUser->check('modules', self::MODULE_IDENTIFIER));
    }
}
