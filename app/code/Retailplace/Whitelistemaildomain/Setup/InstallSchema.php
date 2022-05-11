<?php

namespace Retailplace\Whitelistemaildomain\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0){
            $installer->run('create table onboarding_whitelist_email_domain(domain_id int not null auto_increment, domain varchar(255),status int(6) DEFAULT 1,primary key(domain_id))');
        }

        $installer->endSetup();

    }
}