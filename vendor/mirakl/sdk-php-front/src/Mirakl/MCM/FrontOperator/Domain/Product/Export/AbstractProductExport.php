<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Export;

use Mirakl\Core\Domain\MiraklObject;

/**
 * @method  array       getAuthorizedSellingShopIds()
 * @method  $this       setAuthorizedSellingShopIds(array $authorizedSellingShopIds)
 * @method  array       getCatalogs()
 * @method  $this       setCatalogs(array $catalogs)
 * @method  \DateTime   getCreationDate()
 * @method  $this       setCreationDate(\DateTime $creationDate)
 * @method  array       getDataProduct()
 * @method  $this       setDataProduct(array $dataProduct)
 * @method  string      getMiraklProductId()
 * @method  $this       setMiraklProductId(string $miraklProductId)
 * @method  string      getProductSku()
 * @method  $this       setProductSku(string $productSku)
 * @method  \DateTime   getUpdateDate()
 * @method  $this       setUpdateDate(\DateTime $updateDate)
 * @method  string      getVariantGroupId() @deprecated
 * @method  $this       setVariantGroupId(string $variantGroupId) @deprecated
 */
abstract class AbstractProductExport extends MiraklObject
{
    /**
     * @var array
     */
    protected static $mapping = [
        'data'                    => 'data_product',
        'mirakl_variant_group_id' => 'variant_group_id', /** @deprecated */
    ];
}