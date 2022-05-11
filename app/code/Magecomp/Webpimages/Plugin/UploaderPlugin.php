<?php

namespace Magecomp\Webpimages\Plugin;

class UploaderPlugin {

    protected $data;

    public function __construct(
    \Magecomp\Webpimages\Helper\Data $helperdata
    ) {
        $this->data = $helperdata;
    }

    public function aroundaddValidateCallback(\Magento\Framework\File\Uploader $subject, callable $proceed, $callbackName, $callbackObject, $callbackMethod) {

        if ($subject->getFileExtension() != 'webp') {
            $this->_validateCallbacks[$callbackName] = ['object' => $callbackObject, 'method' => $callbackMethod];
            return $this;
        }
    }

    public function aroundcheckMimeType(\Magento\Framework\File\Uploader $subject, \Closure $proceed, $validTypes = []) {
        $enabled_webp = $this->data->getGeneralConfig('enable');

        if ($enabled_webp == 1) {
            $validTypes['webp'] = 'image/webp';
        }
        return $validTypes;
    }

}
