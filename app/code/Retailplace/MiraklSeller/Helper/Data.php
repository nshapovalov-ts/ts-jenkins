<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Retailplace\CustomerAccount\Model\ApprovalContext;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Data extends AbstractHelper
{
    /**
     * @var Session
     */
    private $_session;

    /**
     * @var ResourceConnection
     */
    private $_resource;

    /**
     * @var AdapterInterface
     */
    private $_connection;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Data constructor.
     * @param Context $context
     * @param Session $session
     * @param ResourceConnection $resource
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        Context $context,
        Session $session,
        ResourceConnection $resource,
        \Magento\Framework\App\Http\Context $httpContext,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $localeDate
    ) {
        parent::__construct($context);
        $this->_session = $session;
        $this->_resource = $resource;
        $this->_connection = $resource->getConnection();
        $this->httpContext = $httpContext;
        $this->priceCurrency = $priceCurrency;
        $this->localeDate = $localeDate;
    }

    public function isMinOrderAmountSellerExist($showText = false, $quote = null)
    {
        if (!$quote) {
            $quote = $this->_session->getQuote();
        }
        if (!$quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)) {
            $itemCollections = $quote->getItemsCollection();
            $cartData = $itemCollections->getData();
            if ($cartData) {
                $miraklshopIds = array_column($cartData, 'mirakl_shop_id');
                if ($miraklshopIds) {
                    $miraklshopDataSelect = $this->_connection->select()
                        ->from(['mo' => 'mirakl_shop'], ['id', 'min-order-amount', 'name'])
                        ->where('id in (?)', $miraklshopIds);
                    $miraklshopData = $this->_connection->fetchAssoc($miraklshopDataSelect);

                    $selleritemtotal = [];
                    foreach ($cartData as $item) {
                        $mirakl_shop_id = $item['mirakl_shop_id'] ?? "";
                        if ($mirakl_shop_id) {
                            if (isset($selleritemtotal[$mirakl_shop_id])) {
                                $selleritemtotal[$mirakl_shop_id] += $item['row_total_incl_tax'];
                            } else {
                                $selleritemtotal[$mirakl_shop_id] = $item['row_total_incl_tax'];
                            }
                        }
                    }
                    if ($selleritemtotal) {
                        foreach ($miraklshopData as $mirakl_shop_id => $miraklshop) {
                            $totalPrice = $selleritemtotal[$mirakl_shop_id] ?? "";
                            $minOrderAmount = $miraklshop['min-order-amount'];
                            $miraklShopName = $miraklshop['name'];
                            if ($totalPrice < $minOrderAmount) {
                                $remainingAmount = $minOrderAmount - $totalPrice;
                                $url = "<a target='_blank' href='{$this->_getUrl('marketplace/shop/view', ['id' => $mirakl_shop_id])}' title='$miraklShopName'>Seller Showroom</a>";
                                $textMessage = __(
                                    "%1 has a minimum order amount of %2.\nPlease add %3 by purchasing more products from %4.",
                                    $miraklShopName,
                                    $this->formatPrice($minOrderAmount),
                                    $this->formatPrice($remainingAmount),
                                    $url
                                );
                                return $showText ? strval($textMessage) : true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->priceCurrency->convertAndFormat($price, true, 0);
    }

    public function checkIsCustomerLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    public function checkIsApproval()
    {
        return $this->httpContext->getValue(ApprovalContext::APPROVAL_CONTEXT);
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
    public function getSideMenuConfig($type)
    {
        return $this->scopeConfig->getValue(
            "mirakl_seller_core/sidemenu_configuration/disable_{$type}_sidemenu",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get based on configs the max date to mark a shop a new
     *
     * @param DateTime|null $date
     *
     * @return string
     */
    public function getIsNewFromDate(DateTime $date = null): string
    {
        if (!$date) {
            $date = $this->localeDate->date();
        }
        $daysCount = $this->getNewLabelDaysCount();
        $dateFrom = $date->modify('-' . $daysCount . ' days');

        return $dateFrom->format(StdlibDateTime::DATE_PHP_FORMAT);
    }

    /**
     * Get days limit to label shops as new
     *
     * @return mixed
     */
    public function getNewLabelDaysCount()
    {
        return $this->scopeConfig->getValue(ShopInterface::XML_PATH_NB_DAYS_TO_LABEL_NEW);
    }
}
