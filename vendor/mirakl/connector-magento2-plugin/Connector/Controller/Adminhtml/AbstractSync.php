<?php
namespace Mirakl\Connector\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Controller\Adminhtml\RawMessagesTrait;
use Mirakl\Core\Controller\Adminhtml\RedirectRefererTrait;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractSync extends Action
{
    use RedirectRefererTrait;
    use RawMessagesTrait;

    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Config::sync';

    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   Context                 $context
     * @param   ApiConfig               $apiConfig
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   LoggerInterface         $logger
     * @param   ConnectorConfig         $connectorConfig
     */
    public function __construct(
        Context $context,
        ApiConfig $apiConfig,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        LoggerInterface $logger,
        ConnectorConfig $connectorConfig
    ) {
        parent::__construct($context);
        $this->apiConfig = $apiConfig;
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->connectorConfig = $connectorConfig;
        $this->logger = $logger;
    }

    /**
     * Will redirect with an error if Mirakl Connector is disabled in config
     *
     * @return  bool
     */
    protected function checkConnectorEnabled()
    {
        if (!$this->apiConfig->isEnabled()) {
            $this->messageManager->addErrorMessage(__('Mirakl Connector is currently disabled.'));
            return false;
        }

        return true;
    }
}
