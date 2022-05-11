<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Plugin\Customer\Model;

class FileProcessor
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * FileProcessor constructor.
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
    }

    /**
     * Change real file name to file name after save to get correct access by url
     * Also fix notice after Magento unset(result['path'])
     *
     * @param \Magento\Customer\Model\FileProcessor $subject
     * @param array $result
     * @return array
     */
    public function afterSaveTemporaryFile($subject, $result)
    {
        if (!isset($result['path'])) {
            $result['path'] = $this->mediaDirectory->getAbsolutePath(
                'customer/' . \Magento\Customer\Model\FileProcessor::TMP_DIR
            );
        }
        $result['tmp_real_name'] = $result['name'];
        $result['name'] = $result['file'];
        return $result;
    }
}
