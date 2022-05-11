<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Plugin\Eav\Model\Entity\Attribute\Source;

class Table
{
    /**
     * @var array
     */
    private $imageTypes = [
        'selectimg',
        'multiselectimg'
    ];

    /**
     * @var \Amasty\CustomerAttributes\Helper\Image
     */
    private $imageHelper;

    /**
     * Table constructor.
     *
     * @param \Amasty\CustomerAttributes\Helper\Image $imageHelper
     */
    public function __construct(\Amasty\CustomerAttributes\Helper\Image $imageHelper)
    {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Check attributes and add icon url for each attribute with type described on $this->imageTypes
     *
     * @param \Magento\Eav\Model\Entity\Attribute\Source\Table $source
     * @param array $result
     * @return array
     */
    public function afterGetAllOptions($source, $result)
    {
        if ($source->getAttribute()
            && in_array($source->getAttribute()->getFrontendInput(), $this->imageTypes)
        ) {
            foreach ($result as &$data) {
                if ($data['value']) {
                    $icon = $this->imageHelper->getIconUrl($data['value']);
                    if ($icon) {
                        $data['icon'] = $icon;
                    }
                }
            }
        }
        return $result;
    }
}
