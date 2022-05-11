<?php
namespace Mirakl\Mci\Model\Product\Import\Report;

class Csv extends \SplTempFileObject implements ReportInterface
{
    /**
     * @param   int|null    $maxMemory
     * @param   string      $delimiter
     * @param   string      $enclosure
     */
    public function __construct($maxMemory = null, $delimiter = ';', $enclosure = '"')
    {
        parent::__construct($maxMemory);
        $this->setCsvControl($delimiter, $enclosure);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $this->rewind();

        return $this->fread($this->fstat()['size']);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        return $this->fputcsv($data);
    }
}