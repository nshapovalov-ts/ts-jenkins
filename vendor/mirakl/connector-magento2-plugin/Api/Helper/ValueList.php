<?php
namespace Mirakl\Api\Helper;

use Mirakl\MCI\Front\Request\ValueList\ValueListImportRequest;
use Mirakl\MCI\Front\Request\ValueList\ValueListImportErrorReportRequest;
use Mirakl\MCI\Front\Request\ValueList\ValueListImportStatusRequest;

class ValueList extends ClientHelper\MCI implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (VL01) Send a file to create, update or delete values list
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

        $request = new ValueListImportRequest($data);

        $result = $this->upload($request);

        return $result ? $result->getImportId() : false;
    }

    /**
     * (VL03) Send values list import report request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new ValueListImportErrorReportRequest($synchroId);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (VL02) Send values list import status request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \Mirakl\MCI\Common\Domain\AbstractCatalogImportResult
     */
    public function getSynchroResult($synchroId)
    {
        $request = new ValueListImportStatusRequest($synchroId);

        return $this->send($request);
    }
}
