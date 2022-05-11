<?php

namespace Magecomp\Webpimages\Model\Wysiwyg\Images;

class Storage extends \Magento\Cms\Model\Wysiwyg\Images\Storage {

    protected $data;

    public function __construct(
    \Magecomp\Webpimages\Helper\Data $helperdata, 
    \Magento\Backend\Model\Session $session,
    \Magento\Backend\Model\UrlInterface $backendUrl, 
    \Magento\Cms\Helper\Wysiwyg\Images $cmsWysiwygImages, 
    \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb, 
    \Magento\Framework\Filesystem $filesystem, 
    \Magento\Framework\Image\AdapterFactory $imageFactory,
    \Magento\Framework\View\Asset\Repository $assetRepo,
    \Magento\Cms\Model\Wysiwyg\Images\Storage\CollectionFactory $storageCollectionFactory,
    \Magento\MediaStorage\Model\File\Storage\FileFactory $storageFileFactory, 
    \Magento\MediaStorage\Model\File\Storage\DatabaseFactory $storageDatabaseFactory,
    \Magento\MediaStorage\Model\File\Storage\Directory\DatabaseFactory $directoryDatabaseFactory, 
    \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
    array $resizeParameters = [],
    array $extensions = [],
    array $dirs = [],
    array $data = [],
    \Magento\Framework\Filesystem\DriverInterface $file = null,
    \Magento\Framework\Filesystem\Io\File $ioFile = null,
    \Psr\Log\LoggerInterface $logger = null
    ) {

        $this->data = $helperdata;
        parent::__construct($session, $backendUrl, $cmsWysiwygImages, $coreFileStorageDb, $filesystem, $imageFactory, $assetRepo, $storageCollectionFactory, $storageFileFactory, $storageDatabaseFactory, $directoryDatabaseFactory, $uploaderFactory, $resizeParameters, $extensions, $dirs, $data);
    }

    public function uploadFile($targetPath, $type = null) {
        if (!$this->isPathAllowed($targetPath, $this->getConditionsForExcludeDirs())) {
            throw new \Magento\Framework\Exception\LocalizedException(
            __('We can\'t upload the file to current folder right now. Please try another folder.')
            );
        }
        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $this->getAllowedExtensions($type);

        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
       
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        if (!$uploader->checkMimeType($this->getAllowedMimeTypes($type))) {
            throw new \Magento\Framework\Exception\LocalizedException(__('File validation failed.'));
        }
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

        if (strtolower($uploader->getFileExtension()) !== 'webp') {
            $this->resizeFile($targetPath . '/' . $uploader->getUploadedFileName(), true);
        }
        return $result;
    }

    public function getAllowedExtensions($type = null) {
        $enabled_webp = $this->data->getGeneralConfig('enable');
        $allowed = $this->getExtensionsList($type);
        
        if ($enabled_webp == 1) {
            $allowed['webp'] = 'image/webp';
        }
        return array_keys(array_filter($allowed));
    }

    private function getAllowedMimeTypes($type = null): array {
        $allowed = $this->getExtensionsList($type);
        return array_values(array_filter($allowed));
    }

    /**
     * Get list of allowed file extensions with mime type in values.
     *
     * @param string|null $type
     * @return array
     */
    private function getExtensionsList($type = null): array {
        $enabled_webp = $this->data->getGeneralConfig('enable');
        if (is_string($type) && array_key_exists("{$type}_allowed", $this->_extensions)) {
            $allowed = $this->_extensions["{$type}_allowed"];
        } else {
            $allowed = $this->_extensions['allowed'];
        }
        
        if ($enabled_webp == 1) {
            $allowed['webp'] = 'image/webp';
        }
        return $allowed;
    }

    private function isPathAllowed($path, array $conditions): bool {
        $isAllowed = true;
        $regExp = $conditions['reg_exp'] ? '~' . implode('|', array_keys($conditions['reg_exp'])) . '~i' : null;
        $storageRoot = $this->_cmsWysiwygImages->getStorageRoot();
        $storageRootLength = strlen($storageRoot);

        $mediaSubPathname = substr($path, $storageRootLength);
        $rootChildParts = explode('/', '/' . ltrim($mediaSubPathname, '/'));

        if (array_key_exists($rootChildParts[1], $conditions['plain']) || ($regExp && preg_match($regExp, $path))) {
            $isAllowed = false;
        }

        return $isAllowed;
    }

}
