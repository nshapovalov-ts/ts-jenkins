<?php
namespace Mirakl\Api\Helper;

interface SynchroResultInterface
{
    /**
     * Gets synchronization error report file
     *
     * @param   string  $synchroId
     * @return  \SplFileObject
     */
    public function getErrorReport($synchroId);

    /**
     * Gets synchronization result by its id
     *
     * @param   string  $synchroId
     * @return  \Mirakl\MMP\FrontOperator\Domain\Synchro\AbstractSynchroResult
     */
    public function getSynchroResult($synchroId);
}