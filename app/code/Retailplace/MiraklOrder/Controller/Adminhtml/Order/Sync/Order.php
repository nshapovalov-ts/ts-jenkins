<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Controller\Adminhtml\Order\Sync;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklOrder\Model\MiraklOrderUpdaterFactory;
use Mirakl\Connector\Controller\Adminhtml\AbstractSync;

/**
 * Class Order
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Order extends AbstractSync
{
    /** @var MiraklOrderUpdaterFactory */
    private $miraklOrderUpdaterFactory;

    /**
     * @param Context $context
     * @param ApiConfig $apiConfig
     * @param ProcessFactory $processFactory
     * @param ProcessResourceFactory $processResourceFactory
     * @param LoggerInterface $logger
     * @param ConnectorConfig $connectorConfig
     * @param MiraklOrderUpdaterFactory $miraklOrderUpdaterFactory
     */
    public function __construct(
        Context $context,
        ApiConfig $apiConfig,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        LoggerInterface $logger,
        ConnectorConfig $connectorConfig,
        MiraklOrderUpdaterFactory $miraklOrderUpdaterFactory
    ) {
        $this->miraklOrderUpdaterFactory = $miraklOrderUpdaterFactory;
        parent::__construct(
            $context,
            $apiConfig,
            $processFactory,
            $processResourceFactory,
            $logger,
            $connectorConfig
        );
    }

    /**
     * @return Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            $miraklUpdater = $this->miraklOrderUpdaterFactory->create();
            $miraklUpdater->update();
            $this->messageManager->addSuccessMessage(__('All orders was imported to Magento'));
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            $this->messageManager
                ->addErrorMessage(__('There are some problems with import. ').$exception->getMessage());
        }

        return $this->redirectReferer();
    }
}
