<?php
namespace Mirakl\Connector\Model\Offer;

class ImportFileBuilder
{
    /**
     * @var string
     */
    public $delimiter = ';';

    /**
     * @var string
     */
    public $enclosure = '"';

    /**
     * @var string
     */
    protected $tmpFile;

    /**
     * Remove temp file if set
     */
    public function __destruct()
    {
        if ($this->tmpFile) {
            @unlink($this->tmpFile);
        }
    }

    /**
     * @param   string  $file
     * @param   array   $offerTableColumns
     * @return  string
     */
    public function build($file, array $offerTableColumns)
    {
        return $this->buildFile($this->buildData($file, $offerTableColumns));
    }

    /**
     * Creates a temp file of offers to import
     *
     * @param   array   $data
     * @return  string
     */
    public function buildFile(array $data)
    {
        $this->tmpFile = $this->createTempFile();
        $fhOut = fopen($this->tmpFile, 'w');

        if (!empty($data)) {
            $this->writeCsv($fhOut, array_keys($data[0]));

            foreach ($data as $offer) {
                $this->writeCsv($fhOut, $offer);
            }
        }

        fclose($fhOut);

        return $this->tmpFile;
    }

    /**
     * Builds an array of offers to import.
     * It is needed to encode extra columns that are present in OF51 file from Mirakl into additional_info field.
     *
     * @param   string  $file
     * @param   array   $offerTableColumns
     * @param   array   $defaultValues
     * @return  array
     */
    public function buildData($file, array $offerTableColumns, $defaultValues = [])
    {
        $data = [];
        $fhIn = fopen($file, 'r');
        $fileColumns = $this->cleanFileColumns($this->readCsv($fhIn));
        $additionalCols = array_diff($fileColumns, $offerTableColumns);

        while ($row = $this->readCsv($fhIn)) {
            $row = array_combine($fileColumns, $row);
            $offer = array_fill_keys($offerTableColumns, '');
            $offer = array_merge($offer, array_intersect_key($row, array_flip($offerTableColumns)));
            if (!empty($additionalCols)) {
                $offer['additional_info'] = $this->encode(array_intersect_key($row, array_flip($additionalCols)));
            }
            foreach ($offer as $key => $value) {
                if ($value === '' && isset($defaultValues[$key])) {
                    $offer[$key] = $defaultValues[$key];
                }
            }
            $data[] = $offer;
        }

        fclose($fhIn);

        return $data;
    }

    /**
     * @param   array   $cols
     * @return  array
     */
    private function cleanFileColumns(array $cols)
    {
        return str_replace('-', '_', $cols);
    }

    /**
     * @return  string
     */
    protected function createTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'mirakl_offers_');
    }

    /**
     * @param   array   $data
     * @return  string
     */
    private function encode(array $data)
    {
        return json_encode($data);
    }

    /**
     * @param   resource    $fh
     * @return  array
     */
    protected function readCsv($fh)
    {
        // We used the char "\x80" as escape_char to avoid problem when we have a \ before a double quote
        return fgetcsv($fh, null, $this->delimiter, $this->enclosure, "\x80");
    }

    /**
     * @param   resource    $fh
     * @param   array       $data
     * @return  bool|int
     */
    protected function writeCsv($fh, array $data)
    {
        return fputcsv($fh, $data, $this->delimiter, $this->enclosure, "\x80");
    }
}
