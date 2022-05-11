<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\FrontOperator\Request\Catalog\Product\ProductSynchroRequest;
use Mirakl\MMP\FrontOperator\Request\Catalog\Product\ProductSynchroErrorReportRequest;
use Mirakl\MMP\FrontOperator\Request\Catalog\Product\ProductSynchroStatusRequest;

class Product extends ClientHelper\MMP implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (P21) Builds and sends product synchro request to Mirakl platform
     *
     * @param   array   $data
     * @return  int|false
     */
    public function export(array $data)
    {
        if (empty($data)) {
            return false;
        }

        // Init CSV file here in order to specify the 'escape' parameter manually
        // that causes a bug if a product field contains some special chars like \"
        $file = new \SplTempFileObject();
        $file->setFlags(\SplFileObject::READ_CSV);
        $file->setCsvControl(';', '"', "\x80");

        // Add columns in top of file
        $file->fputcsv(array_keys(reset($data)));

        // Add products data
        foreach ($data as $fields) {
            $file->fputcsv($fields);
        }

        $request = new ProductSynchroRequest($file);

        $this->_eventManager->dispatch('mirakl_api_synchronize_products_before', [
            'request' => $request,
        ]);

        $request->setFileName('MGT2-P21-' . time() . '.csv');
        $result = $this->upload($request);

        return $result ? $result->getSynchroId() : false;
    }

    /**
     * (P23) Send product synchronization report request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new ProductSynchroErrorReportRequest($synchroId);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (P22) Send product synchronization status request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \Mirakl\MMP\FrontOperator\Domain\Synchro\AbstractSynchroResult
     */
    public function getSynchroResult($synchroId)
    {
        $request = new ProductSynchroStatusRequest($synchroId);

        return $this->send($request);
    }
}
