<?php
namespace Mirakl\Api\Helper;

use Mirakl\MCI\Front\Request\Attribute\AttributeImportRequest;
use Mirakl\MCI\Front\Request\Attribute\AttributeImportErrorReportRequest;
use Mirakl\MCI\Front\Request\Attribute\AttributeImportStatusRequest;
use Mirakl\MCI\Front\Request\Attribute\GetAttributesRequest;

class Attribute extends ClientHelper\MCI implements SynchroResultInterface, ExportDataInterface
{
    /**
     * (PM01) Import operator attributes
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

        $request = new AttributeImportRequest($data);

        $result = $this->upload($request);

        return $result ? $result->getImportId() : false;
    }

    /**
     * (PM11) Retrieves all attributes for parents and children of the requested hierarchy
     *
     * @param   string  $hierarchyCode
     * @param   int     $maxLevel
     * @return  \Mirakl\MCI\Common\Domain\Collection\AttributeCollection
     */
    public function getAttributesConfiguration($hierarchyCode = null, $maxLevel = null)
    {
        $request = new GetAttributesRequest();

        if ($hierarchyCode) {
            $request->setHierarchyCode($hierarchyCode);
        }

        if ($maxLevel) {
            $request->setMaxLevel($maxLevel);
        }

        return $this->send($request);
    }

    /**
     * (PM03) Send attributes import report request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId)
    {
        $request = new AttributeImportErrorReportRequest($synchroId);

        $result = $this->send($request);

        return $result->getFile();
    }

    /**
     * (PM02) Send attributes import status request to Mirakl platform
     *
     * @param   int $synchroId
     * @return  \Mirakl\MCI\Common\Domain\AbstractCatalogImportResult
     */
    public function getSynchroResult($synchroId)
    {
        $request = new AttributeImportStatusRequest($synchroId);

        return $this->send($request);
    }
}
