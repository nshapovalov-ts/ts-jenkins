<?php
namespace Retailplace\CustomerAccount\Rewrite\Model\Account;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Url\HostChecker;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Retailplace\CustomerAccount\Model\ApprovalStatus;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Redirect extends \Magento\Customer\Model\Account\Redirect
{
    /** URL to redirect user on successful login or registration */
    const LOGIN_REDIRECT_URL = 'login_redirect';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var HostChecker
     */
    private $hostChecker;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    /**
     * @param RequestInterface $request
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param DecoderInterface $urlDecoder
     * @param CustomerUrl $customerUrl
     * @param ResultFactory $resultFactory
     * @param HostChecker|null $hostChecker
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        RequestInterface $request,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        DecoderInterface $urlDecoder,
        CustomerUrl $customerUrl,
        ResultFactory $resultFactory,
        HostChecker $hostChecker = null
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->request = $request;
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->urlDecoder = $urlDecoder;
        $this->customerUrl = $customerUrl;
        $this->resultFactory = $resultFactory;
        $this->hostChecker = $hostChecker ?: ObjectManager::getInstance()->get(HostChecker::class);
        parent::__construct(
            $request,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $url,
            $urlDecoder,
            $customerUrl,
            $resultFactory,
            $hostChecker
        );
    }
    public function getRedirect()
    {
        $this->updateLastCustomerId();
        $this->prepareRedirectUrl();

        /** @var ResultRedirect|ResultForward $result */
        if ($this->session->getBeforeRequestParams()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->setParams($this->session->getBeforeRequestParams())
                ->setModule($this->session->getBeforeModuleName())
                ->setController($this->session->getBeforeControllerName())
                ->forward($this->session->getBeforeAction());
        } else {
            $redirectUrl = $this->session->getBeforeAuthUrl();

            if ($this->session->isLoggedIn() && (strpos($redirectUrl, 'customer/account') !== false || strpos($redirectUrl, 'cms/noroute') !== false || strpos($redirectUrl, 'sign-up-page') !== false)) {
                $this->session->setBeforeAuthUrl($this->url->getBaseUrl());
            }

            //Customer not complete the application needs to redirect to customer edit page
            if ($this->session->isLoggedIn()) {
                $isPending = $this->approvalStatus->isPending($this->session->getCustomerData());
                $login = $this->request->getPost('login');
                if ($isPending
                    && !empty($login['redirect'])
                    && $login['redirect'] == 'edit'
                ) {
                    $this->session->setBeforeAuthUrl($this->url->getUrl('customer/account/edit'));
                }
            }

            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl($this->session->getBeforeAuthUrl(true));
        }
        return $result;
    }
}
