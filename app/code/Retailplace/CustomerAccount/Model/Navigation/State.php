<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Navigation;

use Magento\Framework\DataObject;

class State extends DataObject
{
    const STEP_REGISTER = 'register';

    const STEP_BUSINESS_INFO = 'business_info';

    const STEP_PREFERENCES = 'preferences';

    const STEP_FINISH = 'finish';

    /**
     * Allow steps array
     *
     * @var array
     */
    protected $steps;

    /**
     * State constructor.
     * @param array $data
     */
    public function __construct(
        array $data = []
    ) {
        parent::__construct($data);
        $this->steps = [
            self::STEP_REGISTER => new DataObject(['label' => __('Personal Info'),'class' => "personal-info"]),
            self::STEP_BUSINESS_INFO => new DataObject(['label' => __('Business Info'),'class' => "business-info"]),
            self::STEP_PREFERENCES => new DataObject(['label' => __('Preferences'),'class' => "preferences"]),
            self::STEP_FINISH => new DataObject(['label' => __('Finish'),'class' => "finish"])
        ];
    }

    /**
     * Retrieve available checkout steps
     *
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param $step
     */
    public function setActiveStep($step)
    {
        if (isset($this->steps[$step])) {
            foreach ($this->getSteps() as $stepObject) {
                $stepObject->unsIsActive();
            }
            $this->steps[$step]->setIsActive(true);
        }
    }
}
