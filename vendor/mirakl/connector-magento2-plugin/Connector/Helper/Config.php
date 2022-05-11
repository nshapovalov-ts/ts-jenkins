<?php
namespace Mirakl\Connector\Helper;

use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends \Mirakl\Core\Helper\Config
{
    const XML_PATH_TRANSLATION_STORE                  = 'mirakl_connector/general/translation_store';
    const XML_PATH_LOCALE_CODES_FOR_LABEL_TRANSLATION = 'mirakl_connector/general/locale_codes_for_labels_translation';

    const XML_PATH_TAX_OFFER_PRICES                   = 'mirakl_connector/tax/offer_prices';
    const XML_PATH_TAX_SHIPPING_PRICES                = 'mirakl_connector/tax/shipping_prices';
    const XML_PATH_TAX_CALCULATE_US_TAXES             = 'mirakl_connector/tax/calculate_us_taxes';

    const XML_PATH_AUTO_REMOVE_OFFERS                 = 'mirakl_connector/shopping_cart/auto_remove_offers';
    const XML_PATH_AUTO_UPDATE_OFFERS                 = 'mirakl_connector/shopping_cart/auto_update_offers';

    const XML_PATH_AUTO_CREATE_ORDER                  = 'mirakl_connector/order_workflow/auto_create_order';
    const XML_PATH_AUTO_CREATE_ORDER_STATUSES         = 'mirakl_connector/order_workflow/auto_create_order_statuses';
    const XML_PATH_AUTO_SCORE_ORDER                   = 'mirakl_connector/order_workflow/auto_score_order';
    const XML_PATH_PAYMENT_WORKFLOW                   = 'mirakl_connector/order_workflow/payment_workflow';
    const XML_PATH_ENABLE_MULTIPLE_SHIPMENTS          = 'mirakl_connector/order_workflow/enable_multiple_shipments';

    const XML_PATH_OFFERS_IMPORT_CLEAR_CACHE          = 'mirakl_connector/indexes_caches/offers_import/clear_cache';

    const XML_PATH_OFFERS_USE_DIRECT_DATABASE_IMPORT  = 'mirakl_sync/offers/use_direct_database_import';

    /**
     * @var StoreInterface[]
     */
    protected $storesForLabelTranslation;

    /**
     * Returns store to use for catalog integration
     *
     * @return  StoreInterface
     */
    public function getCatalogIntegrationStore()
    {
        $storeId = $this->getValue(self::XML_PATH_TRANSLATION_STORE);
        if ($storeId) {
            return $this->storeManager->getStore($storeId);
        }

        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * @param   mixed   $store
     * @return  array
     */
    public function getCreateOrderStatuses($store = null)
    {
        $statuses = $this->getValue(self::XML_PATH_AUTO_CREATE_ORDER_STATUSES, $store);

        return explode(',', $statuses);
    }

    /**
     * Returns default country code
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getDefaultCountry($store = null)
    {
        return $this->getValue(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY, $store);
    }

    /**
     * Whether offer prices from Mirakl include tax
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function getOffersIncludeTax($store = null)
    {
        return $this->getFlag(self::XML_PATH_TAX_OFFER_PRICES, $store);
    }

    /**
     * @return  string
     */
    public function getPaymentWorkflow()
    {
        return $this->getValue(self::XML_PATH_PAYMENT_WORKFLOW);
    }

    /**
     * Whether shipping prices from Mirakl include tax
     *
     * @param   mixed   $store
     * @param   Quote   $quote
     * @return  bool
     */
    public function getShippingPricesIncludeTax($store = null, $quote = null)
    {
        if ($quote && $quote->getMiraklComputeTaxes()) {
            return true; // Return true if Mirakl has computed taxes in quote in order to ignore Magento taxes
        }

        return $this->getFlag(self::XML_PATH_TAX_SHIPPING_PRICES, $store);
    }

    /**
     * Returns stores to use for label translation
     *
     * @return  array
     */
    public function getStoresForLabelTranslation()
    {
        if ($this->storesForLabelTranslation === null) {
            $storeIds = $this->getValue(self::XML_PATH_LOCALE_CODES_FOR_LABEL_TRANSLATION);

            $this->storesForLabelTranslation = [];
            if (strlen($storeIds)) {
                $storeIds = explode(',', $storeIds);

                $integrationStoreId = $this->getValue(self::XML_PATH_TRANSLATION_STORE);
                foreach ($this->storeManager->getStores(true) as $store) {
                    if ($store->getId() != $integrationStoreId && in_array($store->getId(), $storeIds)) {
                        $this->storesForLabelTranslation[] = $store;
                    }
                }
            }
        }

        return $this->storesForLabelTranslation;
    }

    /**
     * @param   string  $entity
     * @param   mixed   $store
     * @return  \DateTime|null
     */
    public function getSyncDate($entity, $store = null)
    {
        $path = "mirakl_sync/$entity/last_sync_$entity";

        if (null === $store) {
            $date = $this->getRawValue($path);
        } else {
            $scopeId = $this->storeManager->getStore($store)->getId();
            $date = $this->getRawValue($path, ScopeInterface::SCOPE_STORES, $scopeId);
        }

        return !empty($date) ? new \DateTime($date) : null;
    }

    /**
     * @return  $this
     */
    protected function resetConfig()
    {
        $this->storeManager->getStore()->resetConfig();

        return $this;
    }

    /**
     * @param   string  $entity
     * @return  $this
     */
    public function resetSyncDate($entity)
    {
        $this->setValue("mirakl_sync/$entity/last_sync_$entity", null);

        return $this->resetConfig();
    }

    /**
     * @param   string  $entity
     * @param   string  $time
     * @return  $this
     */
    public function setSyncDate($entity, $time = 'now')
    {
        $datetime = new \DateTime($time);
        $this->setValue("mirakl_sync/$entity/last_sync_$entity", $datetime->format(\DateTime::ISO8601));

        return $this->resetConfig();
    }

    /**
     * Should we remove Mirakl offers automatically when processing shopping cart?
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function isAutoRemoveOffers($store = null)
    {
        return $this->getFlag(self::XML_PATH_AUTO_REMOVE_OFFERS, $store);
    }

    /**
     * Should we update Mirakl offers automatically when processing shopping cart?
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function isAutoUpdateOffers($store = null)
    {
        return $this->getFlag(self::XML_PATH_AUTO_UPDATE_OFFERS, $store);
    }

    /**
     * Returns true if Mirakl order has to be created automatically when a Magento order is placed
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function isAutoCreateOrder($store = null)
    {
        return $this->getFlag(self::XML_PATH_AUTO_CREATE_ORDER, $store);
    }

    /**
     * Returns true if Mirakl order has to be scored automatically during creation (OR01)
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function isAutoScoreOrder($store = null)
    {
        return $this->getFlag(self::XML_PATH_AUTO_SCORE_ORDER, $store);
    }

    /**
     * Should we let Mirakl calculate taxes for US shipments?
     *
     * @param   mixed   $store
     * @return  bool
     */
    public function isCalculateUSTaxes($store = null)
    {
        return $this->getFlag(self::XML_PATH_TAX_CALCULATE_US_TAXES, $store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function isEnableMultiShipments($store = null)
    {
        return $this->getFlag(self::XML_PATH_ENABLE_MULTIPLE_SHIPMENTS, $store);
    }

    /**
     * @param   StoreInterface  $store
     * @return  bool
     */
    public function isStoreInLabelTranslation(StoreInterface $store)
    {
        foreach ($this->getStoresForLabelTranslation() as $_store) {
            if ($_store->getId() == $store->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Should we clean cache after offers import?
     *
     * @return  bool
     */
    public function isOffersImportClearCache()
    {
        return $this->getFlag(self::XML_PATH_OFFERS_IMPORT_CLEAR_CACHE);
    }

    /**
     * Should we use fast MySQL import (LOAD DATA INFILE) for offers or a regular PHP script?
     *
     * @return  bool
     */
    public function isOffersUseDirectDatabaseImport()
    {
        return $this->getFlag(self::XML_PATH_OFFERS_USE_DIRECT_DATABASE_IMPORT);
    }
}
