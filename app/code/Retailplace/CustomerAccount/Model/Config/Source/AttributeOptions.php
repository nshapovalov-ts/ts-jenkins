<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class AttributeOptions extends AbstractSource
{
    const PENDING    = 'pending';
    const APPROVED   = 'approved';
    const NOTAPPROVE = 'notapproved';

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $options = [];

        foreach ($this->toArray() as $key => $label) {
            $options[] = [
                'value' => $key,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::PENDING    => __('Pending'),
            self::APPROVED   => __('Approved'),
            self::NOTAPPROVE => __('Not Approved'),
        ];
    }
}
