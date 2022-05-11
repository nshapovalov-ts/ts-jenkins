<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin\Catalog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http;
use Retailplace\CatalogSort\Api\Data\ProductSortScoreAttributesInterface;

class Config
{
    const CATALOG_FRONTEND_DISABLE_SORT_BY_PRICE = 'catalog/frontend/disable_sort_by_price';
    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Http
     */
    private $request;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param array $options
     * @return array
     */
    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $catalogConfig, array $options): array
    {
        unset($options['price']);
        if (!$this->isPriceSortingDisabled()) {
            $options['high_to_low'] = __('Highest price');
            $options['low_to_high'] = __('Lowest price');
        }
        $options['newly_added'] = __('Newly added');

        if (strpos($this->request->getFullActionName(), 'catalogsearch_result') !== false) {
            unset($options['position']);
            $options['relevance'] = __('Relevance');
        }

        if ($this->request->getRouteName() === "marketplace") {
            $options[ProductSortScoreAttributesInterface::ATTRIBUTE_CODE] = __(
                ProductSortScoreAttributesInterface::ATTRIBUTE_DEFAULT_LABEL
            );
        }

        return $options;
    }

    /**
     * Disabled Product List Default Sort By Price
     *
     * @param mixed $store
     * @return bool
     */
    public function isPriceSortingDisabled($store = null): bool
    {
        $value = $this->_scopeConfig->getValue(
            self::CATALOG_FRONTEND_DISABLE_SORT_BY_PRICE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return !empty($value);
    }
}
