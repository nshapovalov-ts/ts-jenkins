<?php
namespace Mirakl\Mci\Model\Product\Import\Handler;

trait CsvTrait
{
    /**
     * If delimiter defined in key fails (CSV with 1 column) try to use fallbacks defined as value
     *
     * @var array
     */
    protected $availableDelimiters = [
        ';' => [','],
        ',' => [';'],
    ];

    /**
     * @param   resource    $fh
     * @param   string      $delimiter
     * @param   string      $enclosure
     * @return  string|false
     */
    public function getValidDelimiter($fh, $delimiter, $enclosure = '"')
    {
        if (!$fh || !isset($this->availableDelimiters[$delimiter])) {
            return $delimiter;
        }

        $delimiters = $this->availableDelimiters[$delimiter];

        rewind($fh);

        while ($delimiter) {
            $cols = fgetcsv($fh, 0, $delimiter, $enclosure);

            if (empty($cols) || !is_array($cols)) {
                $delimiter = false;
                break;
            }

            if (count($cols) > 1) {
                break;
            }

            rewind($fh);
            $delimiter = current($delimiters);
            next($delimiters);
        }

        rewind($fh);

        return $delimiter;
    }
}