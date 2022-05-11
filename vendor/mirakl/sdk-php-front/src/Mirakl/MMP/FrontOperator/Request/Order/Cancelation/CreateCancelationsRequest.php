<?php
namespace Mirakl\MMP\FrontOperator\Request\Order\Cancelation;

use Mirakl\MMP\Common\Request\Order\Cancelation\AbstractCreateCancelationsRequest;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\CreateCancelationCollection;
use Mirakl\MMP\FrontOperator\Domain\Order\Cancelation\CancelationsCreated;

/**
 * (OR30) Demand cancelations on order lines
 *
 * @method  CreateCancelationCollection   getCancelations()
 * @method  $this                         setCancelations(array|CreateCancelationCollection $cancelations)
 *
 * Example:
 *
 * <code>
 * use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
 * use Mirakl\MMP\Front\Client\FrontApiClient;
 * use Mirakl\MMP\FrontOperator\Domain\Order\Cancelation\CreateCancelation;
 * use Mirakl\MMP\FrontOperator\Request\Order\Cancelation\CreateCancelationsRequest;
 *
 * $api = new FrontApiClient('API_URL', 'API_KEY');
 *
 * $createCancelation = new CreateCancelation();
 * $createCancelation->setAmount(10.00);
 * $createCancelation->setReasonCode('18');
 * $createCancelation->setOrderLineId('OR01-A-1');
 * $createCancelation->setQuantity(1);
 * $createCancelation->setShippingAmount(1.00);
 * $createCancelation->setCurrencyIsoCode('EUR');
 * $createCancelation->setShippingTaxes([new OrderTaxAmount(1.53, 'VAT')]);
 * $createCancelation->setTaxes([new OrderTaxAmount(0.98, 'GST')]);
 *
 * $request = new CreateCancelationsRequest([$createCancelation]);
 *
 * $result = $api->cancelOrderLines($request);
 * // $result => @see \Mirakl\MMP\FrontOperator\Domain\Order\Cancelation\CancelationsCreated
 * </code>
 */
class CreateCancelationsRequest extends AbstractCreateCancelationsRequest
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'cancelations' => [CreateCancelationCollection::class, 'create'],
    ];

    /**
     * @param   array|CreateCancelationCollection   $cancelations
     */
    public function __construct($cancelations)
    {
        parent::__construct();
        $this->setCancelations($cancelations);
    }

    /**
     * @inheritdoc
     */
    public function getResponseDecorator()
    {
        return CancelationsCreated::decorator();
    }
}