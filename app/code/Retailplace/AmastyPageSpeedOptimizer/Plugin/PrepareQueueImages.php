<?php

/**
 * Retailplace_AmastyPageSpeedOptimizer
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AmastyPageSpeedOptimizer\Plugin;

use Amasty\PageSpeedOptimizer\Model\Image\GenerateQueue;
use Amasty\PageSpeedOptimizer\Model\Queue\Queue;
use Amasty\PageSpeedOptimizer\Plugin\Image\AbstractImage;

/**
 * Class PrepareQueueImages
 */
class PrepareQueueImages
{
    /**
     * @var GenerateQueue
     */
    private $generateQueue;

    /**
     * @param GenerateQueue $generateQueue
     */
    public function __construct(
        GenerateQueue $generateQueue
    ) {
        $this->generateQueue = $generateQueue;
    }

    /**
     * When saving an image in wysiwyg, check the allowed paths in Image Folder Settings.
     * If the path is available, change the IsUseWebP status to true
     *
     * @param AbstractImage $subject
     * @param $result
     *
     * @return Queue|mixed
     */
    public function afterPrepareFile(AbstractImage $subject, $result)
    {
        if ($result instanceof Queue) {
            $folders = $this->generateQueue->prepareFolders(null);
            $useWebp = false;
            foreach ($folders as $folder) {
                if (!$folder->getIsCreateWebp() || $useWebp) {
                    continue;
                }
                foreach ($folder->getFolders() as $item) {
                    if (strpos($result->getFilename(), $item) === 0) {
                        $result->setIsUseWebP(true);
                        $useWebp = true;
                    }
                }
            }
        }

        return $result;
    }
}
