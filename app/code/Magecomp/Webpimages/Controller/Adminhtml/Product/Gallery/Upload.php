<?php

namespace Magecomp\Webpimages\Controller\Adminhtml\Product\Gallery;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;

class Upload extends \Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload {

    protected $data;
    private $adapterFactory;
    private $filesystem;
    private $productMediaConfig;
    protected $resultRawFactory;
    private $allowedMimeTypes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/png',
        'png' => 'image/gif',
    ];

    public function __construct(
    \Magecomp\Webpimages\Helper\Data $helperdata, 
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, 
    \Magento\Framework\Image\AdapterFactory $adapterFactory = null, 
    \Magento\Framework\Filesystem $filesystem = null, 
    \Magento\Catalog\Model\Product\Media\Config $productMediaConfig = null) {
        
        $this->data = $helperdata;
        parent::__construct($context, $resultRawFactory, $adapterFactory, $filesystem, $productMediaConfig);
        $this->resultRawFactory = $resultRawFactory;
        $this->adapterFactory = $adapterFactory ?: ObjectManager::getInstance()
                        ->get(\Magento\Framework\Image\AdapterFactory::class);
        $this->filesystem = $filesystem ?: ObjectManager::getInstance()
                        ->get(\Magento\Framework\Filesystem::class);
        $this->productMediaConfig = $productMediaConfig ?: ObjectManager::getInstance()
                        ->get(\Magento\Catalog\Model\Product\Media\Config::class);
    }

    public function execute() {
        try {
            $uploader = $this->_objectManager->create(
                    \Magento\MediaStorage\Model\File\Uploader::class, ['fileId' => 'image']
            );
            $uploader->setAllowedExtensions($this->getAllowedExtensions());
            $imageAdapter = $this->adapterFactory->create();
            $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            $result = $uploader->save(
                    $mediaDirectory->getAbsolutePath($this->productMediaConfig->getBaseTmpMediaPath())
            );


            $this->_eventManager->dispatch(
                    'catalog_product_gallery_upload_image_after', ['result' => $result, 'action' => $this]
            );

            unset($result['tmp_name']);
            unset($result['path']);

            $result['url'] = $this->productMediaConfig->getTmpMediaUrl($result['file']);
            $result['file'] = $result['file'] . '.tmp';
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }

    private function getAllowedExtensions() {
        $enabled_webp = $this->data->getGeneralConfig('enable');
        $allowed = $this->allowedMimeTypes;
        if ($enabled_webp == 1) {
            $allowed['webp'] = 'image/webp';
        }
        return array_keys($allowed);
    }

}
