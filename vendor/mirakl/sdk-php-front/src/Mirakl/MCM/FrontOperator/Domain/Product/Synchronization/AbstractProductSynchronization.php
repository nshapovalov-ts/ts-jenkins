<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Synchronization;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MCM\FrontOperator\Domain\Collection\Product\AbstractProductIntegrationErrorCollection;

/**
 * @method  array                                     getAuthorizedSellingShopIds()
 * @method  $this                                     setAuthorizedSellingShopIds(array $authorizedSellingShopIds)
 * @method  array                                     getCatalogs()
 * @method  $this                                     setCatalogs(array $catalogs)
 * @method  AbstractProductIntegrationErrorCollection getIntegrationErrors()
 * @method  $this                                     setIntegrationErrors(AbstractProductIntegrationErrorCollection $integrationErrorCollection)
 * @method  string                                    getMiraklProductId()
 * @method  $this                                     setMiraklProductId(string $miraklProductId)
 * @method  string                                    getOperation()
 * @method  $this                                     setOperation(string $operation)
 * @method  string                                    getProductSku()
 * @method  $this                                     setProductSku(string $productSku)
 */
abstract class AbstractProductSynchronization extends MiraklObject
{
    /**
     * @var array
     */
    protected static $mapping = [
        'data' => 'data_product',
    ];

    /**
     * Confirm product
     *
     * @param   string  $miraklProductId
     * @param   string  $sku
     */
    public function confirmProduct($miraklProductId, $sku)
    {
        $this->unsetData();
        $this->setMiraklProductId($miraklProductId);
        $this->setProductSku($sku);
    }

    /**
     * Report integration errors for a $miraklProductId product
     *
     * @param   string                                    $miraklProductId
     * @param   AbstractProductIntegrationErrorCollection $integrationErrorCollection
     */
    public function reportIntegrationErrorProduct($miraklProductId, AbstractProductIntegrationErrorCollection $integrationErrorCollection)
    {
        $this->unsetData();
        $this->setMiraklProductId($miraklProductId);
        $this->setIntegrationErrors($integrationErrorCollection);
    }

    /**
     * Create a new product
     *
     * @param   string  $sku
     * @param   array   $dataProduct
     */
    public function createProduct($sku, array $dataProduct)
    {
        $this->unsetData();
        $this->setProductSku($sku);
        $this->setDataProduct($dataProduct);
    }

    /**
     * Update a product
     *
     * @param   string  $miraklProductId
     * @param   string  $sku
     * @param   array   $dataProduct
     */
    public function updateProduct($miraklProductId, $sku, array $dataProduct)
    {
        $this->unsetData();
        $this->setMiraklProductId($miraklProductId);
        $this->setProductSku($sku);
        $this->setDataProduct($dataProduct);
    }

    /**
     * @param   array   $dataProduct
     */
    public function setDataProduct($dataProduct)
    {
        $this->setData('data', $dataProduct);
    }

    /**
     * @return array
     */
    public function getDataProduct()
    {
        return $this->getData('data');
    }
}