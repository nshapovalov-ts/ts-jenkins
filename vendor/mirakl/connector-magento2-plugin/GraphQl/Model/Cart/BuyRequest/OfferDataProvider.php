<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Cart\BuyRequest;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\QuoteGraphQl\Model\Cart\BuyRequest\BuyRequestDataProviderInterface;

/**
 * Associates a Mirakl offer to a product when adding to cart
 */
class OfferDataProvider implements BuyRequestDataProviderInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(ArrayManager $arrayManager)
    {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $cartItemData): array
    {
        $offerId = $this->arrayManager->get('data/offer_id', $cartItemData);

        return ['offer_id' => $offerId];
    }
}
