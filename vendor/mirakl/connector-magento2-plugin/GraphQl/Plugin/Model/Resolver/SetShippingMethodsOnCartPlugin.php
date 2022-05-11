<?php
namespace Mirakl\GraphQl\Plugin\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\Resolver\SetShippingMethodsOnCart;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;

class SetShippingMethodsOnCartPlugin
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param   GetCartForUser              $getCartForUser
     * @param   CustomerRepositoryInterface $customerRepository
     * @param   QuoteUpdater                $quoteUpdater
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CustomerRepositoryInterface $customerRepository,
        QuoteUpdater $quoteUpdater
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->customerRepository = $customerRepository;
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @param   SetShippingMethodsOnCart    $subject
     * @param   mixed|Value                 $result
     * @param   Field                       $field
     * @param   ContextInterface            $context
     * @param   ResolveInfo                 $info
     * @param   array|null                  $value
     * @param   array|null                  $args
     * @return  mixed|Value
     */
    public function afterResolve(
        SetShippingMethodsOnCart $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['input']['mp_shipping_methods']) || !count($args['input']['mp_shipping_methods'])) {
            return $result;
        }

        /** @var Quote $cart */
        $cart = $result['cart']['model'];

        $offersShippingTypes = [];
        foreach ($args['input']['mp_shipping_methods'] as $shippingMethod) {
            $offersShippingTypes[$shippingMethod['offer_id']] = $shippingMethod['shipping_type_code'];
        }

        $this->quoteUpdater->updateOffersShippingTypes($offersShippingTypes, $cart, false, true);

        return $result;
    }
}
