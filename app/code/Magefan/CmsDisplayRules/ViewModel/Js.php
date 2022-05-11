<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magefan\CmsDisplayRules\Model\Config;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;

/**
 * Class AddJs view model
 */
class Js implements ArgumentInterface
{

    const CATALOG_PRODUCT_ACTION ='catalog_product_view';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ProductRepositoryInterface
     */
    protected $product;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * IsEnabled constructor.
     * @param ProductRepositoryInterface $product
     * @param Config $config
     * @param Http $request
     * @param Registry $registry
     */
    public function __construct(
        ProductRepositoryInterface $product,
        Config $config,
        Http $request,
        Registry $registry
    ) {
        $this->product = $product;
        $this->config = $config;
        $this->request = $request;
        $this->registry = $registry;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {

        $product = $this->registry->registry('product');
        if (!$product) {
            try {
                $productId = $this->request->getParam('product_id');
                if ($productId) {
                    $product = $this->productRepository->getById($productId);
                } else {
                    $product = false;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $product = false;
            }
        }

        return $product;
    }
}
