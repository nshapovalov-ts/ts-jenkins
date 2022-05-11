<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class StepData extends Template
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * StepData constructor.
     * @param Template\Context $context
     * @param CurrentCustomer $currentCustomer
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CurrentCustomer $currentCustomer,
        Session $session,
        array $data = []
    ) {
        $this->session = $session;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getCurrentStep()
    {
        if ($this->session->getChangePassword()) {
            return 'register';
        }

        $registrationStep = $this->currentCustomer->getCustomer()->getCustomAttribute('registration_step');
        if ($registrationStep) {
            return $registrationStep->getValue();
        }
        return 'register';
    }

    /**
     * @return bool
     */
    public function isChangePassword()
    {
        return (int) $this->session->getChangePassword();
    }
}
