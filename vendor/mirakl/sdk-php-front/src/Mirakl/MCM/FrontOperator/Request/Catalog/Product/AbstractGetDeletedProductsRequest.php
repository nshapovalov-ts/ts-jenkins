<?php
namespace Mirakl\MCM\FrontOperator\Request\Catalog\Product;

use Mirakl\Core\Request\AbstractRequest;

/**
 * (CM61) Export deleted products
 *
 * Delta export of the MCM deleted products in CSV or JSON format.
 * This API only exports products deleted since less than 1 year.
 * To export MCM deleted products, use the following headers:
 *
 *     accept: text/csv for a CSV format export
 *     accept: application/json, by default export format is JSON.
 *
 * @method \DateTime getDeletedFrom()
 * @method $this     setDeletedFrom(\DateTime $deletedFrom)
 * @method \DateTime getDeletedTo()
 * @method $this     setDeletedTo(\DateTime $deletedTo)
 * @method array     getProductIds()
 * @method $this     setProductIds(array $productIds)
 */
abstract class AbstractGetDeletedProductsRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $endpoint = '/mcm/products/deleted/export';

    /**
     * @var array
     */
    public $queryParams = [
        'deleted_from',
        'deleted_to',
        'product_ids' => 'product_id',
    ];

    /**
     * @param   string  $productId
     * @return  $this
     */
    public function addProductId($productId)
    {
        if (!$this->getProductIds()) {
            return $this->setProductIds([$productId]);
        }

        $productIds = (array) $this->getProductIds();
        $productIds[] = $productId;

        return $this->setProductIds($productIds);
    }
}