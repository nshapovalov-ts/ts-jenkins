<?php

namespace Retailplace\Performance\Plugin;

use Magento\Framework\View\Result\Page;

class ResultPage
{
    /**
     * @param Page $subject
     * @param array $parameters
     * @param null $defaultHandle
     * @param bool $entitySpecific
     * @return array
     */
    public function beforeAddPageLayoutHandles(
        Page $subject,
        array $parameters = [],
        $defaultHandle = null,
        $entitySpecific = true
    ) {
        if (!$entitySpecific) {
            return null;
        }

        $handle = $defaultHandle ? $defaultHandle : $subject->getDefaultLayoutHandle();
        if ($handle == 'catalog_product_view') {
            $parameters = [];
            return [$parameters, $defaultHandle, $entitySpecific];
        }

        return null;
    }
}
