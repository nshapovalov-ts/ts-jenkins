<?php
namespace Mirakl\Mcm\Helper;

use Magento\Store\Api\Data\StoreInterface;
use Mirakl\Connector\Helper\Config as ConnectorConfig;

class Config extends ConnectorConfig
{
    const XML_PATH_ENABLE_MCM                         = 'mirakl_mcm/import_product/enable_mcm';
    const XML_PATH_MCM_ENABLE_PRODUCT_IMPORT          = 'mirakl_mcm/import_product/enable_product_import';
    const XML_PATH_MCM_DEFAULT_VISIBILITY             = 'mirakl_mcm/import_product/default_visibility';
    const XML_PATH_MCM_DEFAULT_TAX_CLASS              = 'mirakl_mcm/import_product/default_tax_class';
    const XML_PATH_MCM_AUTO_ENABLE_PRODUCT            = 'mirakl_mcm/import_product/auto_enable_product';

    const XML_PATH_ENABLE_SYNC_MCM_PRODUCTS           = 'mirakl_sync/mcm_products/enable_mcm_products';

    const XML_PATH_MCM_ENABLE_INDEXING_IMPORT         = 'mirakl_mcm/product_import_indexing/enable_indexing_import';
    const XML_PATH_MCM_ENABLE_INDEXING_IMPORT_AFTER   = 'mirakl_mcm/product_import_indexing/enable_indexing_import_after';

    /**
     * Returns default tax class for product import
     *
     * @return  int
     */
    public function getDefaultTaxClass()
    {
        return (int) $this->getValue(self::XML_PATH_MCM_DEFAULT_TAX_CLASS);
    }

    /**
     * Returns default visibility for product import
     *
     * @return  int
     */
    public function getDefaultVisibility()
    {
        return (int) $this->getValue(self::XML_PATH_MCM_DEFAULT_VISIBILITY);
    }

    /**
     * Returns stores that allow product import
     *
     * @return  StoreInterface[]
     */
    public function getStoresUsedForProductImport()
    {
        $stores = [];
        foreach ($this->storeManager->getStores(true) as $store) {
            if ($this->isProductImportEnabled($store)) {
                $stores[] = $store;
            }
        }

        return $stores;
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function isProductImportEnabled($store = null)
    {
        return $this->getFlag(self::XML_PATH_MCM_ENABLE_PRODUCT_IMPORT, $store);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeduplicationAttributes()
    {
        return [Data::CSV_MIRAKL_PRODUCT_ID];
    }

    /**
     * @return  bool
     */
    public function isAutoEnableProduct()
    {
        return $this->getFlag(self::XML_PATH_MCM_AUTO_ENABLE_PRODUCT);
    }

    /**
     * @return  bool
     */
    public function isEnabledIndexingImport()
    {
        return $this->getFlag(self::XML_PATH_MCM_ENABLE_INDEXING_IMPORT);
    }

    /**
     * @return  bool
     */
    public function isEnabledIndexingImportAfter()
    {
        return $this->getFlag(self::XML_PATH_MCM_ENABLE_INDEXING_IMPORT_AFTER);
    }

    /**
     * Returns true if MCM is enabled, false otherwise
     *
     * @return  bool
     */
    public function isMcmEnabled()
    {
        return $this->getFlag(self::XML_PATH_ENABLE_MCM);
    }

    /**
     * @return  bool
     */
    public function isSyncMcmProducts()
    {
        return $this->getFlag(self::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS);
    }
}
