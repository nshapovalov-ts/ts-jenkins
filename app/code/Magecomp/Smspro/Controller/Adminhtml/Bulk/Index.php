<?php
namespace Magecomp\Smspro\Controller\Adminhtml\Bulk;

class Index extends \Magento\Backend\App\Action
{
	protected $resultPageFactory;
	protected $_template = 'bulk/bulksms.phtml';
	
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
    }
	
	public function execute()
    {
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend(__('Send Bulk SMS'));
		return $resultPage;
    }
	
    protected function _isAllowed()
    {
        return true;
    }
}