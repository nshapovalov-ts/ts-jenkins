<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class IncompleteApplicationStatus extends AbstractSource
{
    const INCOMPLETE_APPLICATION = 1;
    const COMPLETE_APPLICATION = 2;

    public function getAllOptions()
    {
        return [
            ['value' => self::INCOMPLETE_APPLICATION, 'label' => __('Yes')],
            ['value' => self::COMPLETE_APPLICATION, 'label' => __('No')]
        ];
    }
}
