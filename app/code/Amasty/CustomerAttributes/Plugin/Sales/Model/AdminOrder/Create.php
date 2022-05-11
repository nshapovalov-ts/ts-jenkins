<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Sales\Model\AdminOrder;

class Create
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Create constructor.
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Save new customer attributes values when admin changed them in order create page
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return \Magento\Sales\Model\Order
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function afterCreateOrder($subject, $result)
    {
        $params = $this->request->getParams();

        if (!empty($params['order']['account'])) {
            $customerId = $result->getCustomerId();

            if ($customerId === null) {
                return $result;
            }
            $customer = $this->customerRepository->getById($customerId);
            $attributes = $customer->getCustomAttributes();
            $needSave = false;

            foreach ($params['order']['account'] as $code => $param) {
                if (array_key_exists($code, $attributes)) {
                    if ($attributes[$code]->getValue() !== $param) {
                        $attributes[$code]->setValue($param);
                        $needSave = true;
                    }
                }
            }

            if ($needSave) {
                $customer->setCustomAttributes($attributes);
                $this->customerRepository->save($customer);
            }
        }

        return $result;
    }
}
