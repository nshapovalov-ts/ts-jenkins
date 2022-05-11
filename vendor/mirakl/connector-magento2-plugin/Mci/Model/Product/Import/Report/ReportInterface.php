<?php
namespace Mirakl\Mci\Model\Product\Import\Report;

interface ReportInterface
{
    /**
     * @return  mixed
     */
    public function getContents();

    /**
     * @param   array   $data
     * @return  mixed
     */
    public function write(array $data);
}
