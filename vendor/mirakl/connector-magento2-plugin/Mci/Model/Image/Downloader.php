<?php

namespace Mirakl\Mci\Model\Image;

use Mirakl\Mci\Helper\Config as MciConfigHelper;

class Downloader
{
    /**
     * @var MciConfigHelper
     */
    protected $mciConfigHelper;

    /**
     * @param   MciConfigHelper $mciConfigHelper
     */
    public function __construct(MciConfigHelper $mciConfigHelper)
    {
        $this->mciConfigHelper = $mciConfigHelper;
    }

    /**
     * @param   string  $url
     * @return  resource|false
     */
    public function download($url)
    {
        $opts = [
            'http' => [
                'method'           => 'GET',
                'ignore_errors'    => true,
                'timeout'          => $this->mciConfigHelper->getImagesImportTimeout(),
                'protocol_version' => $this->mciConfigHelper->getImagesImportProtocolVersion(),
            ],
        ];

        if ($headers = $this->mciConfigHelper->getImagesImportHeaders()) {
            $opts['http']['header'] = $headers;
        }

        set_error_handler(function($errno, $errstr) {
            if ($errno == E_WARNING) {
                throw new \ErrorException('Download error: ' . $errstr);
            }
        });

        $resource = fopen($url, 'r', false, stream_context_create($opts));

        restore_error_handler();

        return $resource;
    }
}