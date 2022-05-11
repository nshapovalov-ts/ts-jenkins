# Mage2 Module Retailplace AmastyRuleOverride

    ``retailplace/module-amastyruleoverride``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Overrided Magento\SalesRule\Model\Rule\Action\Discount\ByPercent to use with Amasty MaxDiscount

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Retailplace`
 - Enable the module by running `php bin/magento module:enable Retailplace_AmastyRuleOverride`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require retailplace/module-amastyruleoverride`
 - enable the module by running `php bin/magento module:enable Retailplace_AmastyRuleOverride`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Plugin
	- afterCalculate - Magento\SalesRule\Model\Rule\Action\Discount\ByPercent > Retailplace\AmastyRuleOverride\Plugin\Magento\SalesRule\Model\Rule\Action\Discount\ByPercent


## Attributes



