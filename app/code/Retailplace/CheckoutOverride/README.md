# Mage2 Module Retailplace CheckoutOverride

    ``retailplace/module-checkoutoverride``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Change label for Business Street

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Retailplace`
 - Enable the module by running `php bin/magento module:enable Retailplace_CheckoutOverride`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require retailplace/module-checkoutoverride`
 - enable the module by running `php bin/magento module:enable Retailplace_CheckoutOverride`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Plugin
	- afterProcess - Magento\Checkout\Block\Checkout\LayoutProcessor > Retailplace\CheckoutOverride\Plugin\Magento\Checkout\Block\Checkout\LayoutProcessor


## Attributes



