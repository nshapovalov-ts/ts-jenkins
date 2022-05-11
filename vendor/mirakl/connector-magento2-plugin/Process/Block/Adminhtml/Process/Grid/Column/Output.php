<?php
namespace Mirakl\Process\Block\Adminhtml\Process\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column;

class Output extends AbstractColumn
{
    /**
     * {@inheritdoc}
     */
    public function decorate($value, $row, $column, $isExport)
    {
        $value = $row->getOutput();
        if (strlen($value)) {
            $lines = array_slice(explode("\n", $value), 0, 6);
            if (count($lines) === 6) {
                $lines[5] = '...';
            }
            array_walk($lines, function(&$line) {
                $line = $this->truncate($line);
            });
            $value = implode('<br/>', $lines);
        }

        return $value;
    }

    /**
     * @param   string  $value
     * @param   int     $length
     * @param   string  $etc
     * @param   string  $remainder
     * @param   bool    $breakWords
     * @return  string
     */
    private function truncate($value, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        return $this->filterManager->truncate(
            $value,
            ['length' => $length, 'etc' => $etc, 'remainder' => $remainder, 'breakWords' => $breakWords]
        );
    }
}
