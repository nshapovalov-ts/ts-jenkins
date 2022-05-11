<?php
namespace Mirakl\MCM\FrontOperator\Request\Catalog\Product;

use Mirakl\Core\Request\AbstractRequest;

abstract class AbstractProductSynchronizationRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * @var string
     */
    protected $endpoint = '/mcm/products/synchronization';

    /**
     * @var array
     */
    public $bodyParams = ['products'];

    /**
     * @inheritdoc
     */
    public function getBodyParams()
    {
        return $this->getProducts()->toArray();
    }
}