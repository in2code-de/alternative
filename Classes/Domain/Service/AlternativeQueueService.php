<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Service;

use In2code\Alternative\Utility\FileUtility;
use Psr\Log\LoggerInterface;
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
        readonly private LoggerInterface $logger,
    ) {
    }

    public function set(string $combindedIdentifier, bool $enforce, bool $continueOnError, OutputInterface $output): int
    {
        $this->combindedIdentifier = $combindedIdentifier;
        $images = $this->getImages();
        $progress = new ProgressBar($output, count($images));
        $progress->start();
        $errorCount = 0;
        /** @var File $image */
        foreach ($images as $image) {
            try {
                $this->alternativeService->setImageMetadata($image, $enforce);
            } catch (\Throwable $exception) {
                if ($continueOnError) {
                    $this->logger->error(
                        'Failed to process image: ' . $image->getIdentifier(),
                        ['exception' => $exception->getMessage()]
                    );
                    $errorCount++;
                } else {
                    throw $exception;
                }
            }
            $progress->advance();
        }
        $output->writeln('');
        if ($errorCount > 0) {
            $output->writeln('<comment>Skipped ' . $errorCount . ' images due to errors. Check var/log/typo3_*.log for details.</comment>');
        }
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
