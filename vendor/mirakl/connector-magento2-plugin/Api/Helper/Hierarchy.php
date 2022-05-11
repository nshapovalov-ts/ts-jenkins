<?php
namespace Mirakl\Api\Helper;

use Mirakl\MCI\Front\Request\Hierarchy\HierarchyImportRequest;
use Mirakl\MCI\Front\Request\Hierarchy\HierarchyImportErrorReportRequest;
use Mirakl\MCI\Front\Request\Hierarchy\HierarchyImportStatusRequest;

class Hierarchy extends ClientHelper\MCI implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (H01) Import operator hierarchies
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

        $request = new HierarchyImportRequest($data);

        $result = $this->upload($request);

        return $result ? $result->getImportId() : false;
    }

    /**
     * (H03) Send hierarchy import report request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new HierarchyImportErrorReportRequest($synchroId);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (H02) Send hierarchy import status request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \Mirakl\MCI\Common\Domain\AbstractCatalogImportResult
     */
    public function getSynchroResult($synchroId)
    {
        $request = new HierarchyImportStatusRequest($synchroId);

        return $this->send($request);
    }
}
