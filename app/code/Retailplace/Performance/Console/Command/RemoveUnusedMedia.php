<?php

/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Console\Command;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Zend_Db_Select;

/**
 * Class RemoveUnusedMedia
 */
class RemoveUnusedMedia extends Command
{
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Framework\Filesystem */
    protected $filesystem;

    /** @var \Magento\Framework\Filesystem\Driver\File */
    private $file;

    /**
     * RemoveUnusedMedia constructor
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param string|null $name
     */
    public function __construct(
        Filesystem $filesystem,
        ResourceConnection $resourceConnection,
        File $file,
        string $name = null
    ) {
        parent::__construct($name);
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
        $this->file = $file;
    }

    /**
     * Init command
     */
    protected function configure()
    {
        $this
            ->setName('retailplace_performance:media:remove-unused')
            ->setDescription('Remove unused product images')
            ->addOption('remove');
    }

    /**
     * execute method
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $fileSize = 0;
        $countFiles = 0;
        $remove = $input->getOption('remove');

        $imageDir = $this->getImageDir();
        $connection = $this->resourceConnection->getConnection();
        $mediaGalleryTable = $connection->getTableName(
            Gallery::GALLERY_TABLE
        );

        /** @var \Magento\Framework\Db\Select $select */
        $select = $connection->select()
            ->from(
            $mediaGalleryTable
        );
        $select->reset(Zend_Db_Select::COLUMNS)->columns('value');
        $imagesToKeep = $connection->fetchCol($select);

        foreach ($this->file->readDirectoryRecursively($imageDir) as $file) {
            $filePath = str_replace($imageDir, "", $file);
            if (
                $this->isInCachePath($file)
                || $this->isInPlaceholderPath($file)
                || is_dir($file)
                || empty($filePath)
            ) {
                continue;
            }

            if (in_array($filePath, $imagesToKeep)) {
                continue;
            }

            $fileSize += filesize($file);
            $countFiles++;
            if ($remove) {
                unlink($file);
                $output->writeln('## REMOVING: ' . $filePath . ' ##');
            } else {
                $output->writeln('## WOULD REMOVE: ' . $filePath . ' ##');
            }
        }

        $this->printResult($output, $remove, $countFiles, $fileSize);
    }

    /**
     * Show command result
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param mixed $remove
     * @param int $countFiles
     * @param int $filesize
     */
    private function printResult(OutputInterface $output, $remove, int $countFiles, int $filesize): void
    {
        $actionName = 'Deleted';
        if (!$remove) {
            $actionName = 'Would delete';
        }
        $fileSizeInMB = number_format($filesize / 1024 / 1024, 2);

        $output->writeln("<info>{$actionName} {$countFiles} unused images. {$fileSizeInMB} MB</info>");
    }

    /**
     * Get product image direcory
     *
     * @return string
     */
    private function getImageDir(): string
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        return $directory->getAbsolutePath() . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
    }

    /**
     * Check that image in Cache directory
     *
     * @param string|null $file
     * @return bool
     */
    private function isInCachePath(?string $file): bool
    {
        return strpos($file, "/cache") !== false;
    }

    /**
     * Check that image in Placeholder directory
     *
     * @param string|null $file
     * @return bool
     */
    private function isInPlaceholderPath(?string $file): bool
    {
        return strpos($file, "/placeholder") !== false;
    }
}
