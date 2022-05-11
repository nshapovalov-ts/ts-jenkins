<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Helper;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\Core\Model\Shop;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection as AmastyCollection;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var ShopCollectionFactory
     */
    private $shopCollectionFactory;

    /**
     * @var array
     */
    protected $shopIds = null;

    /**
     * @var array
     */
    protected $shopOptionIds = null;

    /**
     * @var array
     */
    protected static $customerAttributesForFpc = [
        'tradesquare',
        'sell_goods',
        'business_type',
        'sell_goods_offline',
        'industry',
    ];

    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param HttpContext $httpContext
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param SellerFilter $sellerFilter
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        HttpContext $httpContext,
        ShopCollectionFactory $shopCollectionFactory,
        SellerFilter $sellerFilter
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->sellerFilter = $sellerFilter;
    }

    /**
     * Add Shop Ids Filter
     *
     * @param ProductCollection $collection
     * @return void
     */
    public function addShopIdsFilter(ProductCollection $collection)
    {
        $shopIdsFlag = 'has_shop_ids_filter';
        if (!$collection->hasFlag($shopIdsFlag)) {
            $shopOptionIds = $this->getAllowedShopOptionIds();

            $filteredShopOptionIds = $this->sellerFilter->getFilteredShopOptionIds();
            if (!empty($filteredShopOptionIds)) {
                $shopOptionIds = array_intersect($filteredShopOptionIds, $shopOptionIds);
            }

            if ($shopOptionIds) {
                if ($collection instanceof AmastyCollection) {
                    $collection->addFieldToFilter('mirakl_shop_ids', ['in' => $shopOptionIds]);
                } else {
                    $shopIdsFilter = [];
                    foreach ($shopOptionIds as $shopOptionId) {
                        $shopIdsFilter[] = ['finset' => [$shopOptionId]];
                    }
                    $collection->addFieldToFilter('mirakl_shop_ids', $shopIdsFilter);
                }
            }
            $collection->setFlag($shopIdsFlag, true);
        }
    }

    /**
     * @return array
     */
    public function getAllowedShopOptionIds()
    {
        if ($this->shopOptionIds === null) {
            $shopIds = $this->getShopIdsForExclusion();
            /** @var ShopCollection $shopCollection */
            $shopCollection = $this->shopCollectionFactory->create();
            $shopCollection->addFieldToFilter('id', ['nin' => $shopIds]);
            $this->shopOptionIds = $shopCollection->getColumnValues('eav_option_id');
        }

        return $this->shopOptionIds;
    }

    /**
     * @return array
     */
    public function getShopIdsForExclusion()
    {
        if ($this->shopIds === null) {
            $shopIds = [];
            /** @var ShopCollection $shopCollection */
            $shopCollection = $this->shopCollectionFactory->create();
            $shopCollection->getSelect()->reset('columns')->columns('id');
            $connection = $shopCollection->getConnection();

            /*//Option 1 - only industry selected
            //--> Dont show products and sellers to these buyers meet
                Q1 Criteria -OR between Q1 values in case of multi-select
            //Option 2- only channel selected
            //--> Dont show products and sellers to these buyers who meet
                    Q2 criteria OR between Q2 values in case of multi-select

            //Option 2- Q1: Industry selected  and  Q2 channel selected and Q3 nothing selected
            //--> Dont show products and sellers to these buyers who meet Q1 criteria Or meet Q2 criteria

            //Option 3- Industry and channel
            //--> Dont show products and sellers to these buyers who meet Q1 criteria Or meet Q2 criteria

            //Option 4- Industry selected  and  Q2 channel selected and Q3 all industries
            //--> Don't show products and sellers to the buyers who meet
                Q1 criteria OR  non Q1 criteria who are having the Q2 criteria

            //Option 5- Industry selected  and  Q2 channel selected and Q3 selected industires
            //--> Don't show products and sellers to the buyers who meet Q1 criteria AND  Q2 criteria*/

            if ($this->customerSession->getCustomer()->getId()) {
                $customerData = $this->customerSession->getCustomer();

                //TradeSquare
                $tradesquare = $customerData->getData('tradesquare');
                $tradesquareAttribute = $customerData->getResource()->getAttribute('tradesquare');
                $tradesquareOptionText = "";
                if ($tradesquareAttribute->usesSource() && $tradesquare) {
                    $tradesquareOptionText = trim($tradesquareAttribute->getSource()->getOptionText($tradesquare));
                }
                $tradeSquareOptions['Retailer - for retailing purposes'] = 'a';
                $tradeSquareOptions['Non retailer - for retailing purposes'] = 'b';
                $tradeSquareOptions['For Business Use'] = 'c';
                $tradeSquareOptions['For Corporate Gifting'] = 'd';
                $tradeSquareOption = $tradeSquareOptions[$tradesquareOptionText] ?? "";

                //sellGoods
                $sellGoods = $customerData->getData('sell_goods');
                $sellGoodsAttribute = $customerData->getResource()->getAttribute('sell_goods');
                $sellGoodsOptionText = "";
                if ($sellGoodsAttribute->usesSource() && $sellGoods) {
                    $sellGoodsOptionText = trim($sellGoodsAttribute->getSource()->getOptionText($sellGoods));
                }
                $sellGoodsOptions['Online only'] = 'a';
                $sellGoodsOptions['Bricks and mortar'] = 'b';
                $sellGoodsOptions['Both  A and B'] = 'c';
                $sellGoodsOption = $sellGoodsOptions[$sellGoodsOptionText] ?? "";

                //businessType
                $businessType = $customerData->getData('business_type');
                $businessTypeAttribute = $customerData->getResource()->getAttribute('business_type');
                $businessTypeOptionText = [];
                if ($businessTypeAttribute->usesSource() && $businessType) {
                    $businessTypeOptionText = $businessTypeAttribute->getSource()->getOptionText($businessType);
                }
                if (!is_array($businessTypeOptionText)) {
                    $businessTypeOptionText = [$businessTypeOptionText];
                }
                //sellGoodsOffline
                $sellGoodsOffline = $customerData->getData('sell_goods_offline');
                $sellGoodsOfflineAttribute = $customerData->getResource()->getAttribute('sell_goods_offline');
                $sellGoodsOfflineOptionText = "";
                if ($sellGoodsOfflineAttribute->usesSource() && $sellGoodsOffline) {
                    $sellGoodsOfflineOptionText = trim(
                        $sellGoodsOfflineAttribute
                            ->getSource()
                            ->getOptionText($sellGoodsOffline)
                    );
                }

                $channelExclussion = [];
                $industries = $customerData->getData('industry');
                if ($tradeSquareOption == 'a' || $tradeSquareOption == 'b') {
                    if ($sellGoodsOption == 'a') {
                        $channelExclussion[] = 'retail-online-only';
                    }
                    if ($sellGoodsOption == 'c') {
                        $channelExclussion[] = 'retail-online-plus-offline';
                        if ($sellGoodsOfflineOptionText == "Markers and fairs") {
                            $channelExclussion[] = 'markets-fairs';
                        }
                    }
                    if ($sellGoodsOption == 'b') {
                        $channelExclussion[] = 'retail-bricks-and-mortar';
                        if ($sellGoodsOfflineOptionText == "Markers and fairs") {
                            $channelExclussion[] = 'markets-fairs';
                        }
                    }
                }
                if ($tradeSquareOption == 'c') {
                    $channelExclussion[] = 'business-use';
                }

                if ($tradeSquareOption == 'd') {
                    $channelExclussion[] = 'corporate-gifting';
                }
                if ($industries) {
                    $industries = explode(",", $industries);
                } else {
                    $industries = [];
                }

                if ($businessTypeOptionText && in_array('Wholesale', $businessTypeOptionText)) {
                    $channelExclussion[] = 'wholesale';
                }
                if ($businessTypeOptionText && in_array('Retail', $businessTypeOptionText)) {
                    $channelExclussion[] = 'retail';
                }

                if ($businessTypeOptionText && in_array('Non-Profit', $businessTypeOptionText)) {
                    $industries[] = 'non-for-profit';
                }
                if ($businessTypeOptionText && in_array('Government', $businessTypeOptionText)) {
                    $industries[] = 'government-organisations';
                }

                $industryOrConditions = [];
                $channelOrConditions = [];
                foreach ($industries as $industry) {
                    $industryOrConditions[] = "FIND_IN_SET('$industry', `industry-exclusions`)";
                }
                foreach ($channelExclussion as $channel) {
                    $channelOrConditions[] = "FIND_IN_SET('$channel', `channel-exclusions`)";
                }

                if ($industryOrConditions || $channelOrConditions) {
                    $shopCollectionWithoutExclusionLogic = clone $shopCollection;
                    $shopCollectionWithoutExclusionLogic->getSelect()
                        ->where("`exclusions-logic` is null")
                        ->where(implode(" OR ", array_merge($industryOrConditions, $channelOrConditions)));

                    $shopIds = array_merge(
                        $shopIds,
                        $connection->fetchCol($shopCollectionWithoutExclusionLogic->getSelect())
                    );
                }

                if ($channelOrConditions) {
                    $shopCollectionWithAllIndustries = clone $shopCollection;
                    $shopCollectionWithAllIndustries->getSelect()
                        ->where("`exclusions-logic` = 'all-industries'")
                        ->where(implode(" OR ", $channelOrConditions));
                    $shopIds = array_merge($shopIds, $connection->fetchCol($shopCollectionWithAllIndustries->getSelect()));
                }

                if ($industryOrConditions && $channelOrConditions) {
                    $shopCollectionWithSelectedIndustries = clone $shopCollection;
                    $shopCollectionWithSelectedIndustries->getSelect()
                        ->where("`exclusions-logic` = 'the-selected-industries'");
                    $shopCollectionWithSelectedIndustries->getSelect()->where(implode(" OR ", $industryOrConditions));
                    $shopCollectionWithSelectedIndustries->getSelect()->where(implode(" OR ", $channelOrConditions));
                    $shopIds = array_merge(
                        $shopIds,
                        $connection->fetchCol($shopCollectionWithSelectedIndustries->getSelect())
                    );
                }
            }
            $shopCollectionWithSuspended = clone $shopCollection;
            $shopCollectionWithSuspended->addFieldToFilter('state', ['neq' => Shop::STATE_OPEN]);
            $shopIds = array_merge($shopIds, $connection->fetchCol($shopCollectionWithSuspended->getSelect()));
            $shopIds = array_unique($shopIds);
            $this->shopIds = $shopIds;
        }

        return $this->shopIds;
    }

    /**
     * @param Customer $customer
     * @return void
     */
    public function saveCustomerAttributesToSession($customer)
    {
        foreach (self::$customerAttributesForFpc as $attribute) {
            $this->customerSession->setData('customer_' . $attribute, $customer->getData($attribute));
        }
    }

    /**
     * @return void
     */
    public function clearCustomerAttributesFromSession()
    {
        foreach (self::$customerAttributesForFpc as $attribute) {
            $this->customerSession->setData('customer_' . $attribute, null);
        }
    }

    /**
     * @return void
     */
    public function updateHttpContext()
    {
        foreach (self::$customerAttributesForFpc as $attribute) {
            $this->httpContext->setValue(
                'customer_' . $attribute,
                $this->customerSession->getData('customer_' . $attribute),
                ''
            );
        }
    }

    /**
     * Get Customer Group Id
     *
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customerSession->getCustomer();
    }
}
