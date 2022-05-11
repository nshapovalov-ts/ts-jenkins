<?php

/**
 * Retailplace_AmastyPageSpeedOptimizer
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AmastyPageSpeedOptimizer\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem;
use Amasty\PageSpeedOptimizer\Model\Image\Process;
use Amasty\PageSpeedOptimizer\Model\Queue\QueueFactory;

/**
 * Class DeleteWebpCopy
 */
class DeleteWebpCopy
{
    /**
     * @var File
     */
    private $file;
    /**
     * @var Filesystem
     */
    private $mediaDirectory;
    /**
     * @var QueueFactory
     */
    private $queueFactory;

    public function __construct(
        File $file,
        Filesystem $filesystem,
        QueueFactory $queueFactory
    ) {
        $this->file = $file;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->queueFactory = $queueFactory;
    }

    /**
     * Deleting from folders amasty/amoptimizer_dump and amasty/webp
     * @param Process $subject
     * @param string $target
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function beforeRemoveDumpImage(Process $subject, string $target): string
    {
        $pathInfo = $this->file->getPathInfo($target);
        $absolutePath = $this->mediaDirectory->getAbsolutePath();

        $dumpImagePath = str_replace(
            $absolutePath,
            $absolutePath . Process::DUMP_DIRECTORY,
            $target
        );

        $queue = $this->queueFactory->create();
        $queue->setFilename(str_replace($absolutePath, '', $target));
        $queue->setExtension($pathInfo['extension'] ?? '');
        $webpFileName = $subject->getWebpFileName($target, $queue);

        if ($this->mediaDirectory->isExist($dumpImagePath)) {
            $this->mediaDirectory->delete($dumpImagePath);
        }

        if ($this->mediaDirectory->isExist($webpFileName)) {
            $this->mediaDirectory->delete($webpFileName);
        }

        return $target;
    }
}
