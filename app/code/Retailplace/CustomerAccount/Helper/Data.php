<?php
namespace Retailplace\CustomerAccount\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    const EMAIL_TEMPLATE = 'email_section/sendmail/email_template';

    const EMAIL_SERVICE_ENABLE = 'email_section/sendmail/enabled';

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $_urlInterface;

    /**
     * @var \Magento\Customer\Model\Customer
    */
    protected $_customerModel;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Customer $customerModel,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_urlInterface = $urlInterface;
        $this->_customerModel = $customerModel;
        $this->logger = $logger;
        parent::__construct($context);
    }
    public function base64url_encode($data)
    {
        // First of all you should encode $data to Base64 string
        $b64 = base64_encode($data);

        // Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
        if ($b64 === false) {
            return false;
        }

        // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
        $url = strtr($b64, '+/', '-_');

        // Remove padding character from the end of line and return the Base64URL result
        return rtrim($url, '=');
    }

    /**
     * Decode data from Base64URL
     * @param string $data
     * @param boolean $strict
     * @return boolean|string
     */
    public function base64url_decode($data, $strict = false)
    {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $b64 = strtr($data, '-_', '+/');

        // Decode Base64 string and return the original data
        return base64_decode($b64, $strict);
    }
    public function getEmailLink($email, $currentUrl = "")
    {
        return $this->_urlInterface->getUrl('customer/account/create', ['_current' => true,'_use_rewrite' => true, '_query' => ['email' => $this->base64url_encode($email),'referer' => $this->base64url_encode($currentUrl)]]);
    }
    /**
     * Send Mail
     *
     * @return $this
     *
     * @throws LocalizedException
     * @throws MailException
     */
    public function sendMail($email)
    {
        //$email = 'receiver@example.com'; //set receiver mail
        $this->inlineTranslation->suspend();
        $storeId = $this->getStoreId();

        /* email template */
        $template = $this->scopeConfig->getValue(
            self::EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $vars = [
            'email_verify' =>$this->_urlInterface->getUrl('customer/account/create', ['_current' => true,'_use_rewrite' => true, '_query' => ['email' => base64_encode($email)]]),
            'store' => $this->getStore()
        ];

        // set from email
        $sender = $this->scopeConfig->getValue(
            'email_section/sendmail/sender',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        $transport = $this->transportBuilder->setTemplateIdentifier(
            $template
        )->setTemplateOptions(
            [
                'area' => Area::AREA_FRONTEND,
                'store' => $this->getStoreId()
            ]
        )->setTemplateVars(
            $vars
        )->setFromByScope(
            $sender
        )->addTo(
            $email
        )->getTransport();

        try {
            $transport->sendMessage();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
        $this->inlineTranslation->resume();

        return $this;
    }

    public function checkEmailExist($email)
    {
        if (!$email) {
            return false;
        }
        $customerData = $this->_customerModel->getCollection()
                   ->addFieldToFilter('email', $email);
        if ($customerData->getData()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $email
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomerByEmail($email)
    {
        if (!$email) {
            return null;
        }
        try {
            return $this->customerRepository->get($email);
        } catch (NoSuchEntityException | LocalizedException $e) {
        }

        return null;
    }

    /*
     * get Current store id
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /*
     * get Current store Info
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }
}
