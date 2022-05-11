<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Controller\Index;

use Eyemagine\HubSpot\Helper\Sync;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Retailplace\CustomerAccount\Helper\Data;
use Retailplace\CustomerAccount\Model\ApprovalStatus;

class EmailConfirmation extends Action
{
    /**
    * @var PageFactory
    */
    protected $resultPageFactory;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Sync
     */
    protected $eyeImagineSyncHelper;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    /**
     * @var UrlInterface
     */
    protected $urlModel;

    /**
     * Result constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Data $_helper
     * @param Sync $eyeImagineSyncHelper
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        Context $context,
        PageFactory $pageFactory,
        Data $_helper,
        Sync $eyeImagineSyncHelper,
        UrlInterface $urlModel
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->resultPageFactory = $pageFactory;
        $this->_helper = $_helper;
        $this->eyeImagineSyncHelper = $eyeImagineSyncHelper;
        $this->urlModel = $urlModel;
        parent::__construct($context);
    }

    /**
     * @return false|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $firstname = $this->getRequest()->getPost('firstname');
        $email = $this->getRequest()->getPost('email');
        $phone_number = $this->getRequest()->getPost('phone_number');
        $currentUrl = $this->getRequest()->getPost('current_url');
        if (!$email) {
            return false;
        }
        $resultFactory = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if ($customer = $this->_helper->getCustomerByEmail($email)) {
            $message = __('That email is already taken, try another one');
            $isPending = $this->approvalStatus->isPending($customer);
            if ($isPending) {
                $message = __('Your email already exists, to complete the application please login and go to <a href="%1">account information</a>', $this->urlModel->getUrl('customer/account/login', ['redirect' => 'edit']));
            }

            $response = $resultFactory->setData([
                'status'  => "ok",
                'email'  => $email,
                'show_popup'  => 'no',
                'message' => $message
            ]);
            return $response;
        }

        $customerEmail = $email;
        $dataHubspot =  [
            'eyemagine_activity_email' => $customerEmail,
            'email' => $customerEmail,
            'firstname' => $firstname,
            'phone' => $phone_number,
        ];
        $customerWithProperties = [];
        foreach ($dataHubspot as $property => $value) {
            $customerWithProperties['properties'][] = ["property" =>  strtolower($property) , "value" =>  $value];
        }
        $vid = $this->eyeImagineSyncHelper->getVidByCustomerEmail($customerEmail);
        if ($vid) {
            $result = $this->eyeImagineSyncHelper->updateBulkPropertyByVid($vid, $customerWithProperties);
        } else {
            $result = $this->eyeImagineSyncHelper->createNewCustomerInHubSpot($customerWithProperties);
        }
        //$emailSent     = $this->_helper->sendMail($email);
        $email = json_encode(['email' => $email,'firstname' => $firstname,'phone_number' => $phone_number]);
        $emailSent     = $this->_helper->getEmailLink($email, $currentUrl);
        $response = $resultFactory->setData([
            'status'  => "ok",
            'email'  => $email,
            'email_link'  => $emailSent,
        ]);
        return $response;
    }
}
