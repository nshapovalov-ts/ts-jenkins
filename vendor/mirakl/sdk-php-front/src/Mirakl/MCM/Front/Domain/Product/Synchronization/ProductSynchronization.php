<?php
namespace Mirakl\MCM\Front\Domain\Product\Synchronization;

use Mirakl\MCM\Front\Domain\Collection\Product\ProductIntegrationErrorCollection;
use Mirakl\MCM\Front\Domain\Collection\Product\ProductSynchronizationErrorCollection;
use Mirakl\MCM\Front\Domain\Collection\Product\ProductUrlCollection;
use Mirakl\MCM\Front\Domain\Product\Export\ProductAcceptanceStatus;
use Mirakl\MCM\FrontOperator\Domain\Product\Synchronization\AbstractProductSynchronization;

/**
 * @method  ProductAcceptance                   getAcceptance()
 * @method  $this                               setAcceptance(ProductAcceptance $acceptance)
 * @method  ProductIntegrationErrorCollection   getIntegrationErrors()
 * @method  $this                               setIntegrationErrors(ProductIntegrationErrorCollection $integrationErrorCollection)
 * @method  ProductUrlCollection                getProductUrls()
 * @method  $this                               setProductUrls(ProductUrlCollection $productUrlCollection)
 */
class ProductSynchronization extends AbstractProductSynchronization
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'acceptance'             => [ProductAcceptance::class, 'create'],
        'integration_errors'     => [ProductIntegrationErrorCollection::class, 'create'],
        'product_url'            => [ProductUrlCollection::class, 'create'],
        'synchronization_errors' => [ProductSynchronizationErrorCollection::class, 'create']
    ];

    /**
     * Accept a product
     */
    public function acceptProduct()
    {
        $this->setAcceptance(new ProductAcceptance(['status' => ProductAcceptanceStatus::STATUS_ACCEPTED]));
    }

    /**
     * Reject a product with an optional reason code and message
     *
     * @param   string|null $reasonCode
     * @param   string|null $message
     */
    public function rejectProduct($reasonCode = null, $message = null)
    {
        $rejectedProduct = new ProductAcceptance(['status' => ProductAcceptanceStatus::STATUS_REJECTED]);
        if (!empty($reasonCode)) {
            $rejectedProduct->setReasonCode($reasonCode);
        }
        if (!empty($message)) {
            $rejectedProduct->setMessage($message);
        }
        $this->setAcceptance($rejectedProduct);
    }
}