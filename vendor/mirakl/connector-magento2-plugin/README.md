****
    Copyright © 2021 Mirakl. www.mirakl.com - info@mirakl.com
    All Rights Reserved. Tous droits réservés.
    Strictly Confidential, this data may not be reproduced or redistributed.
    Use of this data is pursuant to a license agreement with Mirakl.
****

# Mirakl Magento 2 Connector

This is the official Mirakl extension for Magento 2.

## How to use

### Prerequisites

 * PHP 5.5+ (the Mirakl Magento Connector uses the Mirakl PHP SDK internally that requires PHP 5.5+)

 * PHP requirements
   * For PHP 7
 ```bash
sudo apt-get install php7.0 php7.0-mcrypt php7.0-curl php7.0-cli php7.0-mysql php7.0-gd libapache2-mod-php7.0 php7.0-intl php7.0-zip php-xml php-mbstring
 ```

### Compatibility

 * Magento Community 2.1.3+
 * Magento Enterprise 2.1.3+

### Installation Steps

#### Install Magento 2

Create the database

`mysql -e "CREATE DATABASE magento2; GRANT ALL PRIVILEGES ON magento2.* TO magento2@localhost IDENTIFIED BY 'magento2'; flush privileges;"`

**Retrieve Magento 2 files**

```bash
cd path/to/magento2
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition .
```

**Install Magento 2**

```bash
php bin/magento setup:install --base-url=http://local.url.mirakl.net/ \
  --db-host=localhost --db-name=magento2 --backend-frontname=admin \
  --db-user=magento2 --db-password=magento2 \
  --admin-firstname=Firstname --admin-lastname=Lastname --admin-email=email@mirakl.com \
  --admin-user=mirakl --admin-password=mirakl123 --language=en_US \
  --currency=USD --timezone=America/Chicago --cleanup-database \
  --sales-order-increment-prefix="MIR$" --session-save=files --use-rewrites=1
```
**Install Magento demo data**

Append some requirement in `composer.json` file
```json
{
    "require": {
        "magento/module-bundle-sample-data": "100.1.*",
        "magento/module-theme-sample-data": "100.1.*",
        "magento/module-widget-sample-data": "100.1.*",
        "magento/module-catalog-sample-data": "100.1.*",
        "magento/module-customer-sample-data": "100.1.*",
        "magento/module-cms-sample-data": "100.1.*",
        "magento/module-tax-sample-data": "100.1.*",
        "magento/module-review-sample-data": "100.1.*",
        "magento/module-catalog-rule-sample-data": "100.1.*",
        "magento/module-sales-rule-sample-data": "100.1.*",
        "magento/module-sales-sample-data": "100.1.*",
        "magento/module-grouped-product-sample-data": "100.1.*",
        "magento/module-downloadable-sample-data": "100.1.*",
        "magento/module-msrp-sample-data": "100.1.*",
        "magento/module-configurable-sample-data": "100.1.*",
        "magento/module-product-links-sample-data": "100.1.*",
        "magento/module-wishlist-sample-data": "100.1.*",
        "magento/module-swatches-sample-data": "100.1.*",
        "magento/sample-data-media": "100.1.*",
        "magento/module-offline-shipping-sample-data": "100.1.*",
    }
}
```

and execute `php composer.phar update; php bin/magento module:enable --all; php bin/magento setup:upgrade`

**Install Frensh translation**

Append one requirement in `composer.json` file
```json
{
    "require": {
        "lalbert/magento2-fr_fr": "*",
    }
}
```

and execute `php composer.phar update; php bin/magento module:enable --all; php bin/magento setup:upgrade`

#### Install the Magento Connector

**With satis**

Edit `composer.json` file and add

```json
{
    "require": {
        "mirakl/connector-magento2-plugin": "*"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://satis.mirakl.net"
        },
        {
            "type": "composer",
            "url": "https://sdk-front-satis.mirakl.net/"
        }
    ]
}
```

and execute `php composer.phar update; php bin/magento module:enable --all; php bin/magento setup:upgrade`

**With atifactory**

Download the last version of Mirakl connector `mirakl-magento2-connector-x.x.x.zip` and store it into `app/artifact` folder.

Download the last version of SDK `mirakl-sdk-php-all-all-x.x.x.zip`, rename it `mirakl-sdk-php-x.x.x.zip` and store it into `app/artifact` folder.

Edit `composer.json` file and add

```json
{
    "require": {
        "mirakl/mirakl-sdk-php": "*",
        "mirakl/connector-magento2-plugin": "*"
    },
    "repositories": [
        {
            "type": "artifact",
            "url": "app/artifact/"
        }
    ]
}
```

and execute `php composer.phar update; php bin/magento module:enable --all; php bin/magento setup:upgrade`

**With github (for Mirakl developper)**

Edit `composer.json` file and add

```json
{
    "require": {
        "mirakl/mirakl-sdk-php": "*",
        "mirakl/connector-magento2-plugin": "dev-develop"
    },
    "minimum-stability": "dev",
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:mirakl/connector-magento2-plugin.git",
            "branch": "develop"
        },
        {
            "type": "vcs",
            "url": "git@github.com:mirakl/sdk-php.git",
            "branch": "develop"
        }
    ]
}
```

and execute `php composer.phar update; php bin/magento module:enable --all; php bin/magento setup:upgrade`

#### Configure Magento

After clearing the Magento cache, go to Admin Panel. A new Mirakl tab should appear at the end of navigation menu.

You can now start configuring Mirakl parameters and synchronize shops, offer states, shipping zones etc.
