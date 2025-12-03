<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Service;

use In2code\Alternative\Utility\FileUtility;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class AlternativeQueueService
{
    protected string $combindedIdentifier = '';

    public function __construct(
        readonly private ResourceFactory $resourceFactory,
        readonly private AlternativeService $alternativeService,
    ) {
    }

    public function set(string $combindedIdentifier, bool $enforce, OutputInterface $output): int
    {
        $this->combindedIdentifier = $combindedIdentifier;
        $images = $this->getImages();
        $progress = new ProgressBar($output, count($images));
        $progress->start();
        /** @var File $image */
        foreach ($images as $image) {
            $this->alternativeService->setImageMetadata($image, $enforce);
            $progress->advance();
        }
        $output->writeln('');
        return count($images);
    }

    protected function getImages(): array
    {
        $images = [];
        $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->combindedIdentifier);
        foreach ($folder->getFiles(recursive: true) as $file) {
            if (is_a($file, ProcessedFile::class) === false) {
                if (FileUtility::isImage($file)) {
                    $images[] = $file;
                }
            }
        }
        return $images;
    }
}
