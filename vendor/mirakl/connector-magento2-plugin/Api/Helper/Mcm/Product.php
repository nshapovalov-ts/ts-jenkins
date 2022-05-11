<?php
namespace Mirakl\Api\Helper\Mcm;

use Mirakl\Api\Helper\ClientHelper\MCM as ClientMCM;
use Mirakl\Api\Helper\SynchroResultInterface;
use Mirakl\Api\Helper\ExportDataInterface;
use Mirakl\MCM\Front\Domain\Collection\Product\ProductSynchronizeCollection;
use Mirakl\MCM\Front\Request\Catalog\Product\ProductExportCsvRequest;
use Mirakl\MCM\Front\Request\Catalog\Product\ProductSynchronizationReportRequest;
use Mirakl\MCM\Front\Request\Catalog\Product\ProductSynchronizationRequest;
use Mirakl\MCM\Front\Request\Catalog\Product\ProductSynchronizationStatusRequest;

class Product extends ClientMCM implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (CM51) Export products CSV file
     *
     * Delta export of the MCM products that are accepted and valid in CSV format.
     * The exported product file uses the product attribute codes as headers, and has an additional column named mirakl-product-id.
     * This column contains the Mirakl unique identifier for each exported product.
     *
     * @param   array   $data
     * @return  \SplFileObject
     */
    public function import(array $data)
    {
        $request = new ProductExportCsvRequest();

        if (isset($data['updated_since'])) {
            $request->setUpdatedSince($data['updated_since']);
        }

        if (isset($data['validation_status'])) {
            $request->setValidationStatus((array) $data['validation_status']);
        }

        if (isset($data['acceptance_status'])) {
            $request->setAcceptanceStatus((array) $data['acceptance_status']);
        }

        $this->_eventManager->dispatch('mirakl_api_mcm_get_products_before', [
            'request' => $request,
        ]);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (CM21) Builds and sends product synchro request to Mirakl platform
     *
     * @param   array   $data
     * @return  string|false
     */
    public function export(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $productCollection = new ProductSynchronizeCollection($data);

        $request = new ProductSynchronizationRequest($productCollection);

        /** @var \Mirakl\MCM\Front\Domain\Product\Synchronization\ProductSynchronizeTracking $result */
        $result = $this->post($request);

        return $result ? $result->getTrackingId() : false;
    }

    /**
     * (CM22) Send product synchronization status request to Mirakl platform
     *
     * @param   string  $synchroId
     * @return  \Mirakl\MCM\Front\Domain\Product\Synchronization\ProductSynchronizationStatus
     */
    public function getSynchroResult($synchroId)
    {
        $request = new ProductSynchronizationStatusRequest($synchroId);

        return $this->send($request);
    }

    /**
     * (CM23) Get product synchronization report from Mirakl platform
     *
     * @param   string  $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new ProductSynchronizationReportRequest($synchroId);

        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $this->send($request, true);

        $result = \Mirakl\parse_file_response($response, 'json');

        $this->_eventManager->dispatch('mirakl_api_mcm_get_synchronization_report', [
            'request'    => $request,
            'response'   => $response,
            'result'     => $result,
            'synchro_id' => $synchroId,
        ]);

        return $result->getFile();
    }
}
