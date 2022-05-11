<?php

namespace Retailplace\Performance\Plugin;

use Magento\Catalog\Model\Product\Image\ParamsBuilder;

class ImageParamsBuilder
{
    /**
     * @param ParamsBuilder $subject
     * @param array $result
     * @param array $imageArguments
     * @param int|null $scopeId
     * @return array
     */
    public function afterBuild(
        ParamsBuilder $subject,
        array $result,
        array $imageArguments,
        int $scopeId = null
    ) {
        $result['version'] = 3;
        return $result;
    }
}
