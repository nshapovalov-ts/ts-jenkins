<?php
namespace Mirakl\GraphQl\Plugin\Model\Cart;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class GetCartForUserPlugin
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param   Session                         $checkoutSession
     * @param   CustomerRepositoryInterface     $customerRepository
     */
    public function __construct(
        Session $checkoutSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param   GetCartForUser  $subject
     * @param   Quote           $result
     * @return  Quote
     */
    public function afterExecute(GetCartForUser $subject, Quote $result)
    {
        $this->checkoutSession->setQuoteId($result->getId());

        if ($result->getCustomerId()) {
            try {
                $user = $this->customerRepository->getById($result->getCustomerId());
                $this->checkoutSession->setCustomerData($user);
            } catch (LocalizedException $e) {
                // The error has be handled in original method
            }
        }

        return $result;
    }
}
