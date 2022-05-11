<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;

class CartItems implements ResolverInterface
{
    /**
     * @var QuoteSynchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @param QuoteSynchronizer $quoteSynchronizer
     */
    public function __construct(QuoteSynchronizer $quoteSynchronizer)
    {
        $this->quoteSynchronizer = $quoteSynchronizer;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $cart = $value['model'];

        $itemsData = [];

        /** @var QuoteItem $cartItem */
        foreach ($this->quoteSynchronizer->getGroupedItems($cart) as $cartItem) {
            $productData = $cartItem->getProduct()->getData();
            $productData['model'] = $cartItem->getProduct();

            $itemsData[] = [
                'id'       => $cartItem->getItemId(),
                'quantity' => $cartItem->getQty(),
                'product'  => $productData,
                'model'    => $cartItem,
            ];
        }

        return $itemsData;
    }
}
