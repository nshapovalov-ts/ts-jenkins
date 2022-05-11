<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */
namespace Amasty\CustomerAttributes\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    const TEMP_FOLDER = true;

    /**
    * @var \Magento\MediaStorage\Model\File\UploaderFactory
    */
    private $_uploaderFactory;

    /**
     * Filesystem facade
     *
     * @var \Magento\Framework\Filesystem
     */
    private $_filesystem;

    /**
     * File Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $_fileUploaderFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * File check
     *
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $_ioFile;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_filesystem = $filesystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
    }

    public function getIconUrl($optionId)
    {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            'amasty/amcustomerattr/'
        );

        $name = $optionId . '.jpg';
        if ($this->_ioFile->fileExists($path . $name)) {
            $path = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            return $path . 'amasty/amcustomerattr/'. $name;
        } else {
            return "";
        }
    }

    public function delete($optionId, $tempFolder = false)
    {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            'amasty/amcustomerattr/'
        );

        if ($tempFolder === true) {
            $path .= 'temp/';
        }

        $path .= $optionId . '.jpg';
        if ($this->_ioFile->fileExists($path)) {
            $this->_ioFile->rm($path);
        }
    }

    public function uploadImage($optionId, $saveOptionId)
    {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            'amasty/amcustomerattr/temp/'
        );
        $this->_ioFile->checkAndCreateFolder($path);

        if ($this->_ioFile->fileExists($path . $optionId . '.jpg')) {
            $this->delete($optionId, self::TEMP_FOLDER);
        }
        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_fileUploaderFactory->create(
                ['fileId' => 'amcustomerattr_icon[' . $optionId . ']']
            );
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->save($path, $saveOptionId . '.jpg');
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                $this->_logger->critical($e);
            }
        }

        return ['path' => $path, 'name' => $saveOptionId];
    }

    public function saveImage($optionId, $saveOptionId, $path)
    {
        $newPath = substr($path, 0, -5);
        if ($this->_ioFile->fileExists($path . $optionId . '.jpg')) {
            $this->delete($saveOptionId);
            $this->_ioFile->mv($path . $optionId . '.jpg', $newPath . $saveOptionId . '.jpg');
        }
    }
}
