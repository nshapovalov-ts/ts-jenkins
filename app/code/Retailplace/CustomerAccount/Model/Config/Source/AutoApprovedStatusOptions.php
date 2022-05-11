<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class AutoApprovedStatusOptions extends AbstractSource
{
    const CONDITIONALLY_APPROVED   = 'conditionally_approved';
    const APPROVED   = 'approved';
    const PENDING = 'pending';

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
            self::CONDITIONALLY_APPROVED    => __('Conditionally Approved'),
            self::APPROVED   => __('Auto Approved'),
            self::PENDING => __('Pending'),
        ];
    }
}
