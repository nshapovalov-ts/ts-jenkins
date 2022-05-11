<?php
namespace Mirakl\FrontendDemo\Controller\Message;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Registry;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Framework\View\Result\PageFactory;
use Mirakl\Api\Helper\Message as MessageApi;
use Mirakl\FrontendDemo\Helper\Message as MessageHelper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Psr\Log\LoggerInterface;

abstract class AbstractMessage extends Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var MessageHelper
     */
    protected $messageHelper;

    /**
     * @var GenericSession
     */
    protected $session;

    /**
     * @param   Context             $context
     * @param   Session             $customerSession
     * @param   LoggerInterface     $logger
     * @param   FormKeyValidator    $formKeyValidator
     * @param   PageFactory         $resultPageFactory
     * @param   Registry            $registry
     * @param   MessageHelper       $messageHelper
     * @param   MessageApi          $messageApi
     * @param   GenericSession      $session
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        PageFactory $resultPageFactory,
        Registry $registry,
        LoggerInterface $logger,
        MessageHelper $messageHelper,
        MessageApi $messageApi,
        GenericSession $session
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->messageHelper = $messageHelper;
        $this->messageApi = $messageApi;
        $this->session = $session;
    }

    /**
     * @return  ThreadDetails|null
     */
    protected function getThread()
    {
        $id = $this->getRequest()->getParam('thread');

        if (!$id || !($customerId = $this->customerSession->getCustomerId())) {
            return null;
        }

        try {
            $thread = $this->messageApi->getThreadDetails($id, $customerId);
        } catch (\Exception $e) {
            return null;
        }

        return $thread;
    }

    /**
     * @return  bool
     */
    protected function validateFormKey()
    {
        return $this->formKeyValidator->validate($this->getRequest());
    }
}
