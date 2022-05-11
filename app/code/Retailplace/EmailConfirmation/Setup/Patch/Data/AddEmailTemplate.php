<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Email\Model\ResourceModel\Template as EmailTemplateResourceModel;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Framework\Mail\TemplateInterfaceFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddEmailTemplate
 */
class AddEmailTemplate implements DataPatchInterface
{
    /** @var \Magento\Framework\Mail\TemplateInterfaceFactory */
    private $emailTemplateFactory;

    /** @var \Magento\Email\Model\ResourceModel\Template */
    private $emailTemplateResourceModel;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Mail\TemplateInterfaceFactory $emailTemplateFactory
     * @param \Magento\Email\Model\ResourceModel\Template $emailTemplateResourceModel
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        TemplateInterfaceFactory $emailTemplateFactory,
        EmailTemplateResourceModel $emailTemplateResourceModel,
        WriterInterface $configWriter
    ) {
        $this->emailTemplateFactory = $emailTemplateFactory;
        $this->emailTemplateResourceModel = $emailTemplateResourceModel;
        $this->configWriter = $configWriter;
    }

    /**
     * Apply Patch
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function apply()
    {
        /** @var \Magento\Email\Model\Template $emailTemplate */
        $emailTemplate = $this->emailTemplateFactory->create();
        $emailTemplate->setData($this->getTemplateData());
        $this->emailTemplateResourceModel->save($emailTemplate);
        $this->configWriter->save(Customer::XML_PATH_CONFIRM_EMAIL_TEMPLATE, $emailTemplate->getId());
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get Patch Aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get New Template Data
     *
     * @return array
     */
    private function getTemplateData(): array
    {
        return [
            'template_code' => 'New Account Email Confirmation Key',
            'template_text' => '{{template config_path="design/email/header_template"}}' . PHP_EOL
                .'<p>{{trans "Confirm your email address"}}</p>' . PHP_EOL
                .'<p>{{trans "Your confirmation code is below — enter it in your open browser window:"}}</p>' . PHP_EOL
                .'<p align="center">' . PHP_EOL
                .'    <strong>{{trans "%confirmation_alt" confirmation_alt=$customer.confirmation_alt}}</strong>' . PHP_EOL
                .'</p>' . PHP_EOL
                .'{{template config_path="design/email/footer_template"}}',
            'template_type' => TemplateInterface::TYPE_HTML,
            'template_subject' => '{{trans "%store_name account confirmation code: " store_name=$store.frontend_name}}'
                .'{{trans "%confirmation_alt" confirmation_alt=$customer.confirmation_alt}}',
            'orig_template_code' => 'customer_create_account_email_confirmation_template',
            'orig_template_variables' => '{"var store.frontend_name":"Store Name","var this.getUrl($store,\'customer/account/confirm/\',[_query:[id:$customer.id,key:$customer.confirmation,back_url:$back_url],_nosid:1])":"Account Confirmation URL","var this.getUrl($store, \'customer/account/\')":"Customer Account URL","var customer.email":"Customer Email","var customer.name":"Customer Name"}',
        ];
    }
}
