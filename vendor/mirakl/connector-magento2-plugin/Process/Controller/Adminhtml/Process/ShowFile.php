<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

/**
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class ShowFile extends AbstractProcessAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        $file = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();

        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            // Show a JSON file
            $contents = json_decode(file_get_contents($file), true);
            $body = '<pre>' . htmlentities(json_encode($contents, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            // Try to show a CSV file
            $fh = fopen($file, 'r');
            $fgetcsv = function () use ($fh) {
                return fgetcsv($fh, 0, ';', '"', "\x80");
            };

            if (count($fgetcsv()) > 1) {
                // Parse CSV and show as HTML table
                fseek($fh, 0);
                $body = '<table border="1" cellpadding="2" style="border-collapse: collapse; border: 1px solid #aaa;">';
                while ($data = $fgetcsv()) {
                    $body .= sprintf('<tr>%s</tr>', implode('', array_map(function ($value) {
                        if (preg_match('#^(https?:\/\/.+)$#', $value)) {
                            $value = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
                        } else {
                            $value = htmlspecialchars($value);
                        }

                        return '<td>' . $value . '</td>';
                    }, $data)));
                }
                $body .= '</table>';
            } else {
                // Show raw contents
                $body = '<pre>' . htmlentities(file_get_contents($file)) . '</pre>';
            }
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($body)
            ->sendResponse();
    }
}
