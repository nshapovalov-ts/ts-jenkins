<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Controller\ProductList;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Action\Action;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Retailplace\Insider\Block\InsiderProductList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\ResponseInterface;

/**
 * Get class
 */
class Get extends Action
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /**
     * Get constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;

        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $skus = $this->getRequest()->getParam('sku');
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter(ProductInterface::SKU, $skus, 'in')->create();
        $productList = $this->productRepository->getList($searchCriteria)->getItems();
        $layout = $this->_view->getLayout();
        $result = $this->resultJsonFactory->create();
        $block = $layout->createBlock(InsiderProductList::class)
            ->setData("products", $productList)
            ->setData("products_sku", $skus)
            ->setTemplate('Retailplace_Insider::default_ajax.phtml');
        $block->_setConfig(
            [
                "type_show"         => "slider",
                "type_listing"      => "all",
                "title"             => "Recommended products",
                "display_countdown" => 0,
                "under_price"       => 5,
                "products"          => $productList
            ]
        );
        $result->setData($block->toHtml());

        return $result;
    }
}
