<?php
namespace Mirakl\Process\Model\Output;

class Cli extends AbstractOutput
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        if (!$this->process->getQuiet()) {
            echo $str . PHP_EOL;
            @ob_flush();
        }

        return $this;
    }
}
