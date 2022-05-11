<?php

namespace Retailplace\Theme\Block\Product\View\Tab;

use Magento\Catalog\Block\Product\Context;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Mirakl\Connector\Helper\StockQty;
use Mirakl\FrontendDemo\Block\Product\View\Tab\Offers as MiraklOfferBlock;
use Mirakl\FrontendDemo\Helper\Config;
use Mirakl\FrontendDemo\Helper\Offer;
use Retailplace\CustomerAccount\Model\ApprovalContext;

class Offers extends MiraklOfferBlock
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param Offer $offerHelper
     * @param Config $configHelper
     * @param EncoderInterface $jsonEncoder
     * @param PriceCurrencyInterface $priceCurrency
     * @param StockStateInterface $stockState
     * @param StockRegistryInterface $stockRegistry
     * @param StockQty $stockQtyHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        Offer $offerHelper,
        Config $configHelper,
        EncoderInterface $jsonEncoder,
        PriceCurrencyInterface $priceCurrency,
        StockStateInterface $stockState,
        StockRegistryInterface $stockRegistry,
        StockQty $stockQtyHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        $data = []
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $offerHelper,
            $configHelper,
            $jsonEncoder,
            $priceCurrency,
            $stockState,
            $stockRegistry,
            $stockQtyHelper,
            $data
        );
        $this->httpContext = $httpContext;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    public function checkIsApproval()
    {
        return $this->httpContext->getValue(ApprovalContext::APPROVAL_CONTEXT);
    }
}
