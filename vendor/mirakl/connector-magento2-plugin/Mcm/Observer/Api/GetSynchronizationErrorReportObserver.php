<?php
namespace Mirakl\Mcm\Observer\Api;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Process\Helper\Data as ProcessHelper;

class GetSynchronizationErrorReportObserver implements ObserverInterface
{
    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @param ProcessHelper $processHelper
     */
    public function __construct(ProcessHelper $processHelper)
    {
        $this->processHelper = $processHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $reportFilePath = $observer->getEvent()->getData('report_file_path');
        $data = @file_get_contents($reportFilePath);
        if ($data) {
            if ($this->hasReportError(json_decode($data, true))) {
                $hasError = $observer->getEvent()->getData('has_error');
                $hasError->setData('error', true);
            }
        }
    }

    /**
     * Data has error for CM23 Response
     *
     * @param array $report
     * @return bool
     */
    public function hasReportError(array $report)
    {
        if (isset($report['processed_items'])) {
            foreach ($report['processed_items'] as $data) {
                if (isset($data['integration_errors']) || isset($data['synchronization_errors'])) {
                    return true;
                }
            }
        }

        if (isset($report['global_errors'])) {
            return true;
        }

        return false;
    }
}
