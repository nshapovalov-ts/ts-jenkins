<?php
namespace Mirakl\Mcm\Model\Product\Import\Report;

use Mirakl\Mci\Model\Product\Import\Report\ReportInterface;

class Data implements ReportInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        $this->data[] = $data;
    }
}