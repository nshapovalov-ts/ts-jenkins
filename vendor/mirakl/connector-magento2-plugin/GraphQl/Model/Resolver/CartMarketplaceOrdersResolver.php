<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;

class CartMarketplaceOrdersResolver implements ResolverInterface
{
    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteSynchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @param   QuoteHelper         $quoteHelper
     * @param   QuoteSynchronizer   $quoteSynchronizer
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        QuoteSynchronizer $quoteSynchronizer
    ) {
        $this->quoteHelper = $quoteHelper;
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

        /** @var Quote $quote */
        $quote = $value['model'];

        $orders = [];
        if ($this->quoteHelper->isMiraklQuote($quote)) {
            $shippingFees = $this->quoteSynchronizer->getShippingFees($quote);
            $orders = $shippingFees->toArray();
        }

        return $orders;
    }
}
