<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Batch;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\GraphQl\Model\Mapper\Shop as ShopMapper;

class Shop implements BatchResolverInterface
{
    /**
     * @var ShopCollectionFactory
     */
    protected $shopCollectionFactory;

    /**
     * @var ShopMapper
     */
    protected $shopMapper;

    /**
     * @param  ShopCollectionFactory  $shopCollectionFactory
     * @param  ShopMapper             $shopMapper
     */
    public function __construct(ShopCollectionFactory $shopCollectionFactory, ShopMapper $shopMapper)
    {
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->shopMapper = $shopMapper;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $response = new BatchResponse();
        $shopIds = [];
        /** @var BatchRequestItemInterface $request */
        foreach ($requests as $request) {
            if (empty($request->getValue()['shop_id'])) {
                throw new LocalizedException(__('"%1" value should be specified', 'shop_id'));
            }
            $shopIds[] = $request->getValue()['shop_id'];

        }


        if (!count($shopIds)) {
            return $response;
        }

        $shops = [];
        $shopCollection = $this->shopCollectionFactory->create()->addFieldToFilter('id', ['in' => $shopIds]);
        foreach ($shopCollection as $shop) {
            $shops[$shop->getId()] = $shop;
        }

        // Matching requests with responses
        foreach ($requests as $request) {
            $shopId = $request->getValue()['shop_id'];
            if (isset($shops[$shopId])) {
                $response->addResponse($request, $this->shopMapper->toGraphQlArray($shops[$shopId]));
            }
        }

        return $response;
    }

}
