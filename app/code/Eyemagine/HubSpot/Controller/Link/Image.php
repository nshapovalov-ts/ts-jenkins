<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller\Link;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Eyemagine\HubSpot\Helper\Sync as SyncHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ProductFactory as ProductModel;
use Exception;

class Image extends Action
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $syncHelper;

    /**
     *
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context            
     * @param \Eyemagine\HubSpot\Helper\Sync $syncHelper            
     * @param \Magento\Catalog\Helper\Image $productImage            
     */
    public function __construct(
        Context $context,
        SyncHelper $syncHelper,
        ImageHelper $imageHelper,
        ProductModel $productModel
    ) {
        parent::__construct($context);
        
        $this->syncHelper = $syncHelper;
        $this->imageHelper = $imageHelper;
        $this->productModel = $productModel;
    }

    /**
     * Resamples the product image and returns the contents as raw data
     */
    public function execute()
    {
        try {
            $size = (int) ($this->getRequest()->getParam('size'));
            $size = ($size > 0) ? min(max(50, $size), 640) : 100;
            
            // render the thumbnail and get the server path
            $product = $this->syncHelper->initProduct() ?  : $this->productModel->create();
            
            $url = $this->imageHelper->init($product, 'product_thumbnail_image')
                ->resize($size ? min(max(50, $size), 640) : 100)
                ->getUrl();

            $file = null;
            if ($url) {
                $serverPath = str_replace($this->syncHelper->getBaseUrl(), '', (string)$url);
                $file = realpath($serverPath);
                $pathinfo = pathinfo($url);

                // add the mime-type header
                switch ($pathinfo['extension']) {
                    case 'jpg':
                        $content_type = 'image/jpeg';
                        break;
                    case 'png':
                        $content_type = 'image/png';
                        break;
                    case 'gif':
                        $content_type = 'image/gif';
                        break;
                    case 'xbm':
                        $content_type = 'image/x-xbitmap';
                        break;
                    case 'wbpm':
                        $content_type = 'image/vnd.wap.wbmp';
                        break;
                    default:
                        $content_type = 'image/jpeg';
                        break;
                }
            }
            
            // add the size header, and output
            if ($file && file_exists($file)) {
                header('Content-Type: ' . $content_type);
                header('Content-Length: ' . filesize($file));
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                readfile($file);
            } else {
                
                if ($url && $content_type) {
                    
                    $this->getResponse()
                        ->setHeader('Content-Type', $content_type)
                        ->setBody(file_get_contents((string) $url));
                }
            }
        } catch (Exception $e) {
            
            if ($url && $content_type) {
                
                $this->getResponse()
                    ->setHeader('Content-Type', $content_type)
                    ->setBody(file_get_contents((string) $url));
            }
        }
    }
}
