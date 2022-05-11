<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\FrontOperator\Request\Catalog\Category\CategorySynchroRequest;
use Mirakl\MMP\FrontOperator\Request\Catalog\Category\CategorySynchroErrorReportRequest;
use Mirakl\MMP\FrontOperator\Request\Catalog\Category\CategorySynchroStatusRequest;

class Category extends ClientHelper\MMP implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (CA01) Builds and sends category synchro request to Mirakl platform
     *
     * @param   array   $data
     * @return  int|false
     */
    public function export(array $data)
    {
        if (empty($data)) {
            return false;
        }

        // Add columns in top of file
        array_unshift($data, array_keys(reset($data)));

        $request = new CategorySynchroRequest($data);

        $this->_eventManager->dispatch('mirakl_api_synchronize_categories_before', [
            'request' => $request,
        ]);

        $request->setFileName('MGT2-CA01-' . time() . '.csv');
        $result = $this->upload($request);

        return $result ? $result->getSynchroId() : false;
    }

    /**
     * (CA03) Send category synchronization report request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new CategorySynchroErrorReportRequest($synchroId);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (CA02) Send category synchronization status request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \Mirakl\MMP\FrontOperator\Domain\Synchro\AbstractSynchroResult
     */
    public function getSynchroResult($synchroId)
    {
        $request = new CategorySynchroStatusRequest($synchroId);

        return $this->send($request);
    }
}
