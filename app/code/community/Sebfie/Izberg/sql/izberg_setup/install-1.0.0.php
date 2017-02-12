<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogInventory
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer Mage_Eav_Model_Entity_Setup */

$installer->startSetup();

/**
 * Create table 'izberg_merchant'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/merchant'))
    ->addColumn('merchant_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Merchant Id')

    ->addColumn('izberg_merchant_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Merchant Id')

    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Name')

    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Status')

    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'Description')

    ->addColumn('default_currency', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Merchant currency')

    ->addColumn('store_url', Varien_Db_Ddl_Table::TYPE_TEXT, 1048, array(
        ), 'Store url')

    ->addColumn('cover_image_url', Varien_Db_Ddl_Table::TYPE_TEXT, 1048, array(
        ), 'Image url')

    ->addColumn('merchant_image_url', Varien_Db_Ddl_Table::TYPE_TEXT, 1048, array(
        ), 'Merchant image url')

    ->addColumn('logo_image_url', Varien_Db_Ddl_Table::TYPE_TEXT, 1048, array(
        ), 'Logo image url')

    ->addColumn('region', Varien_Db_Ddl_Table::TYPE_TEXT, 1048, array(
        ), 'Merchant region')

    ->addColumn('to_import', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        ), 'To import from cron job')

    ->addColumn('magento_enabled', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'default' => true
        ), 'Enabled in magento backend')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Merchant region')

    ->addColumn('created_from_json', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '',
        ), 'Store the json used to create the merchant')

    ->setComment('Izberg Merchant');

$installer->getConnection()->createTable($table);



/**
 * Create table 'izberg_product'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/product'))
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Product Id')

    ->addColumn('izberg_product_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Product Id')

    ->addColumn('izberg_merchant_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Merchant Id')

    ->addColumn('izberg_category_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Category Id')

    ->addColumn('slug', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Slug')

    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Name')

    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'Description')

    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, "10,2", array(
        ), 'Product price')
    ->addColumn('price_with_vat', Varien_Db_Ddl_Table::TYPE_DECIMAL, "10,2", array(
        ), 'Product with vat')
    ->addColumn('price_without_vat', Varien_Db_Ddl_Table::TYPE_DECIMAL, "10,2", array(
        ), 'Product without vat')

    ->addColumn('gender', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Product gender')

    ->addColumn('brand', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Product brand')

    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        ), 'Status')

    ->addColumn('free_shipping', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        ), 'Free shipping')

    ->addColumn('shipping_estimation', Varien_Db_Ddl_Table::TYPE_DECIMAL, "10,2", array(
        ), 'Shipping estimation')

    ->addColumn('in_stock', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        ), 'In stock')

    ->addColumn('stock', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Product stock count')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Merchant created at')

    ->addColumn('created_from_json', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '',
        ), 'Store the json used to create the product')

    ->addForeignKey($installer->getFkName('izberg/product', 'izberg_merchant_id', 'izberg/merchant', 'merchant_id'),
        'izberg_merchant_id', $installer->getTable('izberg/merchant'), 'merchant_id',
        Varien_Db_Ddl_Table::ACTION_NO_ACTION, Varien_Db_Ddl_Table::ACTION_NO_ACTION
    )

    ->setComment('Izberg Merchant');

$installer->getConnection()->createTable($table);



/**
 * Create table 'izberg_category'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/category'))
    ->addColumn('category_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Category Id')

    ->addColumn('magento_matching_category_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Magento matching category id')

    ->addColumn('izberg_category_path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Izberg category path')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'default' => 'CURRENT_TIMESTAMP'
        ), 'Category created_at')

    ->setComment('Izberg Category');

$installer->getConnection()->createTable($table);


/**
 * Create table 'izberg_catalog_product'
 * This table will keep in memory all created product from an izberg products. Example : An izberg product can create a configurable product with many simple products
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('izberg/catalog_product'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Catalog product Id')

    ->addColumn('izberg_product_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Product Id')

    ->addColumn('catalog_product_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Parent Category Id')

    ->addColumn('offer_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Offer Id')

    ->addColumn('variation_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        ), 'Izberg Variation Id')

    ->addForeignKey($installer->getFkName('izberg/catalog_product', 'izberg_product_id', 'izberg/product', 'product_id'),
        'izberg_product_id', $installer->getTable('izberg/product'), 'product_id',
        Varien_Db_Ddl_Table::ACTION_NO_ACTION, Varien_Db_Ddl_Table::ACTION_CASCADE
    )

    ->addForeignKey($installer->getFkName('izberg/catalog_product', 'catalog_product_id', 'catalog/product', 'entity_id'),
        'catalog_product_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )

    ->setComment('Izberg Created Catalog product');

$installer->getConnection()->createTable($table);

$installer->endSetup();
