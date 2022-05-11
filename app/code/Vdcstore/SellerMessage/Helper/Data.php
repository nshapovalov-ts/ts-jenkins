<?php

declare(strict_types=1);

namespace Vdcstore\SellerMessage\Helper;

use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Phrase;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Offer as ApiOfferHelper;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\MMP\Common\Domain\Message\MessageCustomerFactory;
use Mirakl\MMP\Front\Domain\Offer\Message\CreateOfferMessageFactory;
use Retailplace\MiraklFrontendDemo\Api\MessagesStatsRepositoryInterface;
use Retailplace\MiraklFrontendDemo\Api\MessagesRepositoryInterface;

class Data extends AbstractHelper
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var CreateOfferMessageFactory
     */
    private $offerMessageFactory;
    /**
     * @var MessageCustomerFactory
     */
    private $messageCustomerFactory;
    /**
     * @var StoreInterface
     */
    private $store;
    /**
     * @var ApiOfferHelper
     */
    private $apiOfferHelper;

    /**
     * @var MessagesStatsRepositoryInterface
     */
    private $messagesStatsRepository;
    /**
     * @var MessagesRepositoryInterface
     */
    private $messagesRepository;

    /**
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $state
     * @param CreateOfferMessageFactory $offerMessageFactory
     * @param MessageCustomerFactory $messageCustomerFactory
     * @param OfferHelper $offerHelper
     * @param ApiOfferHelper $apiOfferHelper
     * @param StoreInterface $store
     * @param MessagesStatsRepositoryInterface $messagesStatsRepository
     * @param MessagesRepositoryInterface $messagesRepository
     */
    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        CreateOfferMessageFactory $offerMessageFactory,
        MessageCustomerFactory $messageCustomerFactory,
        OfferHelper $offerHelper,
        ApiOfferHelper $apiOfferHelper,
        StoreInterface $store,
        MessagesStatsRepositoryInterface $messagesStatsRepository,
        MessagesRepositoryInterface $messagesRepository
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->offerMessageFactory = $offerMessageFactory;
        $this->messageCustomerFactory = $messageCustomerFactory;
        $this->offerHelper = $offerHelper;
        $this->apiOfferHelper = $apiOfferHelper;
        $this->store = $store;
        $this->messagesStatsRepository = $messagesStatsRepository;
        $this->messagesRepository = $messagesRepository;
        parent::__construct($context);
    }

    public function sendEmail($templateVars)
    {
        $templateId = $this->scopeConfig->getValue(
            'seller_message/general/email_template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $toEmail = $this->scopeConfig->getValue(
            'seller_message/general/receiver_email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $fromEmail = $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $fromName = $this->scopeConfig->getValue(
            'trans_email/ident_sales/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        try {
            $storeId = $this->storeManager->getStore()->getId();

            $from = ['email' => $fromEmail, 'name' => $fromName];
            $this->inlineTranslation->suspend();

            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * Send Message To Seller
     *
     * @param Customer $customer
     * @param Product $product
     * @param string $subject
     * @param string $body
     * @param false $visible
     * @return false
     */
    public function sendMessageToSeller(Customer $customer, Product $product, string $subject, string $body, bool $visible = false): bool
    {
        $offer = $this->offerHelper->getBestOffer($product);
        if (empty($offer)) {
            $this->_logger->info("Offer not found");
            new LocalizedException(new Phrase("Offer not found"));
        }

        $offerId = $offer->getOfferId();

        $message = $this->offerMessageFactory->create();

        $message->setSubject($subject);
        $message->setBody($body);

        if ($visible) {
            $message->setVisible($visible);
        }

        $messageCustomer = $this->messageCustomerFactory->create();
        $messageCustomer->setCustomerId((string)$customer->getId());
        $messageCustomer->setEmail($customer->getEmail());
        $messageCustomer->setFirstname($customer->getFirstname());
        $messageCustomer->setLastname($customer->getLastname());

        $locale = $this->store->getLocaleCode();
        if ($locale) {
            $messageCustomer->setLocale($locale);
        }

        $genderId = $customer->getGender();
        if (is_numeric($genderId)) {
            $gender = $customer->getAttribute('gender')->getSource()->getOptionText($genderId);
            $messageCustomer->setCivility($gender);
        }

        $message->setCustomer($messageCustomer);

        $messageCreated = null;
        try {
            $messageCreated = $this->apiOfferHelper->createOfferMessage($offerId, $message);
            $this->messagesStatsRepository->getAllMySentMessages($customer->getId(), $offer);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
            new LocalizedException(new Phrase($e->getMessage()), null, $e->getCode());
        }

        if (!empty($messageCreated)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }
}
