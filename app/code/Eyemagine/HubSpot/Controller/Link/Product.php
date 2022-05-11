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
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\ResourceModel\Product\Link as ProductLink;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableProduct;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;
use Magento\Search\Helper\Data as SearchHelper;
use Eyemagine\HubSpot\Helper\Sync as SyncHelper;
use Exception;

/**
 * Class Product
 *
 * @package Eyemagine\HubSpot\Controller\Link
 */
class Product extends Action
{

    const TYPE_REDIRECT = \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT;
    
    const SCOPE_STORE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
    /**
     *
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $syncHelper;

    /**
     *
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

    /**
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link
     */
    protected $productLink;

    /**
     *
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurableProduct;

    /**
     *
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    protected $groupedProduct;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Eyemagine\HubSpot\Helper\Sync $syncHelper
     * @param \Magento\Search\Helper\Data $searchHelper
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link $productLink
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProduct
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedProduct
     */
    public function __construct(
        Context $context,
        SyncHelper $syncHelper,
        SearchHelper $searchHelper,
        ProductHelper $productHelper,
        ProductLink $productLink,
        ConfigurableProduct $configurableProduct,
        GroupedProduct $groupedProduct
    ) {
        parent::__construct($context);
        
        $this->resultFactory = $context->getResultFactory();
        $this->syncHelper = $syncHelper;
        $this->searchHelper = $searchHelper;
        $this->productHelper = $productHelper;
        $this->productLink = $productLink;
        $this->configurableProduct = $configurableProduct;
        $this->groupedProduct = $groupedProduct;
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Get product page
     *
     * @return Magento\Framework\Controller\Result
     */
    public function execute()
    {
        try {
            $searchQuery = $this->getRequest()->getParam('q');
            $product = $this->syncHelper->initProduct();
            $url = null;
            $permanent = false;
            // use the loaded product if it exists
            if ($product) {
                $productId = $product->getId();
                
                // if the product is visible, use it's URL, otherwise load the parent's URL
                if ($this->productHelper->canShow($product)) {
                    $url = $this->productHelper->getProductUrl($product);
                    $permanent = true;
                } elseif ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                    // find the simple product's first parent in grouped product types
                    $parentIds = $this->productLink->getParentIdsByChild($productId, $product->getTypeId());
                    
                    $parentIds = $this->groupedProduct->getParentIdsByChild($product->getId());
                    
                    // if no grouped parents were found, find configurable parent IDs
                    if (! $parentIds) {
                        $parentIds = $this->configurableProduct->getParentIdsByChild($productId);
                    }
                    
                    // if a parent ID is found, load it and use its URL
                    if (isset($parentIds[0])) {
                        $this->getRequest()->setParam('id', $parentIds[0]);
                        $parent = $this->syncHelper->initProduct();
                        $url = $this->productHelper->getProductUrl($parent);
                    }
                }
            }
            
            // fallback to search query if product and product url is not available
            if (empty($url)) {
                if (strlen($searchQuery)) {
                    // use the provided search query string (based on product name)
                    $url = $this->searchHelper->getResultUrl($searchQuery);
                } elseif ($product && strlen($product->getName())) {
                    // product exists but is disabled or invisible
                    $url = $this->searchHelper->getResultUrl($product->getName());
                } else {
                    // final fallback to home page
                    
                    $url = $this->syncHelper->getBaseUrl();
                }
                // adds message that the product is unavailable
                
                $this->messageManager->addNotice($this->syncHelper->getConfig()
                    ->getValue('eyehubspot/settings/unavailable_msg', self::SCOPE_STORE));
            }
            
            return $this->resultFactory->create(self::TYPE_REDIRECT)->setUrl($url);
        } catch (Exception $e) {
            $message = "An Error Occured While Processing the Request.";
            
            $this->getResponse()
                ->setHeader('Content-Type', 'text/plain')
                ->setBody($message);
        }
    }
}
