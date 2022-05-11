<?php
namespace Mirakl\Mcm\Observer\Api;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Core\Response\Decorator\AssocArray;
use Mirakl\Mcm\Helper\Product\Export\Report as ReportHelper;

class GetSynchronizationReportObserver implements ObserverInterface
{
    /**
     * @var ReportHelper
     */
    protected $reportHelper;

    /**
     * @param   ReportHelper    $reportHelper
     */
    public function __construct(ReportHelper $reportHelper)
    {
        $this->reportHelper = $reportHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $response = $observer->getEvent()->getData('response');
        $data = (new AssocArray())->decorate($response);
        if (isset($data['processed_items'])) {
            $this->reportHelper->updateMiraklProductIds($data['processed_items']);
        }
    }
}
