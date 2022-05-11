<?php

namespace Retailplace\MiraklSeller\Block\Product;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Url\Helper\Data;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Catalog\Block\Product\Context as ProductContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;

/**
 * ListProduct block class
 */
class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{

    /**
     * @var Layer
     */
    protected $_catalogLayer;

    /**
     * @var PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var Data
     */
    protected $urlHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /** @var PriceCurrencyInterface $priceCurrency */
    protected $priceCurrency;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * ListProduct constructor.
     * @param ProductContext $context
     * @param PostHelper $postDataHelper
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $urlHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param HttpContext $httpContext
     * @param TimezoneInterface $localeDate
     * @param array $data
     */
    public function __construct(
        ProductContext $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        PriceCurrencyInterface $priceCurrency,
        HttpContext $httpContext,
        TimezoneInterface $localeDate,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $this->priceCurrency = $priceCurrency;
        $this->httpContext = $httpContext;
        $this->localeDate = $localeDate;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    public function checkIsCustomerLoggedIn()
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH);
    }

    public function isProductNew($product)
    {
        $newsFromDate = $product->getNewsFromDate();
        $newsToDate = $product->getNewsToDate();
        if (!$newsFromDate && !$newsToDate) {
            return false;
        }

        return $this->localeDate->isScopeDateInInterval(
            $product->getStore(),
            $newsFromDate,
            $newsToDate
        );
    }
}
