<?php
namespace Mirakl\Mcm\Block\Adminhtml\Process;

use Mirakl\Process\Block\Adminhtml\Process\View as ProcessView;

class View extends ProcessView
{
    /**
     * Returns parsing errors report (integration_errors, synchronization_errors, global_errors)
     *
     * @param   int $limit
     * @return  array
     */
    public function getErrors($limit = 100)
    {
        $errors = [];

        $reportFilePath = $this->getProcess()->getFileUrl(true);

        if (!empty($reportFilePath) && ($reportFile = @file_get_contents($reportFilePath))) {
            $report = json_decode($reportFile, true);

            if (isset($report['processed_items'])) {
                foreach ($report['processed_items'] as $item) {
                    if ($limit <= 1) {
                        break;
                    }
                    $identifier = isset($item['mirakl_product_id']) ? $item['mirakl_product_id'] : $item['product_sku'];
                    if (isset($item['integration_errors'])) {
                        foreach ($item['integration_errors'] as $integrationError) {
                            $errors['products'][$identifier][] = ['integration', $integrationError['code'], $integrationError['message']];
                            $limit--;
                        }
                    }
                    if (isset($item['synchronization_errors'])) {
                        foreach ($item['synchronization_errors'] as $synchronizationError) {
                            $errors['products'][$identifier][] = ['synchronization', $synchronizationError['code'], $synchronizationError['message']];
                            $limit--;
                        }
                    }
                }
            }

            if (isset($report['global_errors'])) {
                foreach ($report['global_errors'] as $globalError) {
                    if ($limit <= 1) {
                        break;
                    }
                    $errors['global'][] = ['global', $globalError['code'], $globalError['message']];
                    $limit--;
                }
            }
        }

        return $errors;
    }
}
