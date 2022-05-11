<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class View extends AbstractProcessAction
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(Context $context, Registry $coreRegistry)
    {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        $this->coreRegistry->register('process', $process);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Process #%1', $process->getId()));
        $this->_view->renderLayout();
    }
}
