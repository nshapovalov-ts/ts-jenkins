<?php
namespace Mirakl\Mci\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Core\Controller\Adminhtml\RawMessagesTrait;
use Mirakl\Core\Controller\Adminhtml\RedirectRefererTrait;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Process\Model\ProcessFactory;
use Psr\Log\LoggerInterface;

abstract class Sync extends Action
{
    use RedirectRefererTrait;
    use RawMessagesTrait;

    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   Context         $context
     * @param   ApiConfig       $apiConfig
     * @param   MciConfig       $mciConfig
     * @param   ProcessFactory  $processFactory
     * @param   LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ApiConfig $apiConfig,
        MciConfig $mciConfig,
        ProcessFactory $processFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->apiConfig = $apiConfig;
        $this->mciConfig = $mciConfig;
        $this->processFactory = $processFactory;
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
