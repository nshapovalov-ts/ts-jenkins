<?php
namespace Mirakl\Mcm\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Mirakl\Connector\Helper\Config;
use Mirakl\Core\Controller\Adminhtml\RawMessagesTrait;
use Mirakl\Mcm\Model\Product\Import\Handler\Csv as McmHandler;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Psr\Log\LoggerInterface;

class Products extends Action
{
    use RawMessagesTrait;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   Context                 $context
     * @param   Config                  $config
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   LoggerInterface         $logger
     */
    public function __construct(
        Context $context,
        Config $config,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->logger = $logger;
    }

    /**
     * Import MCM products into Magento
     */
    public function execute()
    {
        try {
            $process = $this->processFactory->create()
                ->setType(Process::TYPE_ADMIN)
                ->setName('MCM products import')
                ->setHelper(McmHandler::class)
                ->setParams([$this->config->getSyncDate('mcm_products_import')])
                ->setMethod('run');

            $this->processResourceFactory->create()->save($process);

            $this->messageManager->addSuccessMessage(
                __('MCM products will be downloaded and imported asynchronously.')
            );
            $this->addRawSuccessMessage(
                __('Click <a href="%1">here</a> to view process output.', $process->getUrl())
            );

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while importing MCM products (%1).', $e->getMessage())
            );
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}