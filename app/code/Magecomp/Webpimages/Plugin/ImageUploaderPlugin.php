<?php

namespace Magecomp\Webpimages\Plugin;

class ImageUploaderPlugin {

    protected $data;

    public function __construct(
    \Magecomp\Webpimages\Helper\Data $helperdata
    ) {
        $this->data = $helperdata;
    }

    public function aftergetAllowedExtensions(\Magento\Catalog\Model\ImageUploader $subject, $result) {
        
        $enabled_webp = $this->data->getGeneralConfig('enable');
        if ($enabled_webp == 1) {
            $result['webp'] = 'webp';
        }
        return $result;
    }

}
