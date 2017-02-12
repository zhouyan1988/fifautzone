<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();


// Table catalog_product
// Drop foreign keys

$installer->getConnection()->dropForeignKey($installer->getTable('izberg/catalog_product'), $installer->getFkName('izberg/catalog_product', 'catalog_product_id', 'catalog/product', 'entity_id'));
$installer->getConnection()->dropForeignKey($installer->getTable('izberg/catalog_product'), $installer->getFkName('izberg/catalog_product', 'izberg_product_id', 'izberg/product', 'product_id'));


$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/catalog_product'),
    'entity_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/catalog_product'),
    'izberg_product_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/catalog_product'),
    'catalog_product_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/catalog_product'),
    'offer_id',
    array(
       'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/catalog_product'),
    'variation_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);


// Table job
$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/job'),
    'job_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

// Table access_tokens
$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/accesstoken'),
    'access_token_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/accesstoken'),
    'quote_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

// Table attributes
$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/attribute'),
    'attribute_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/attribute'),
    'magento_matching_attribute_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

// Table merchant
$installer->getConnection()->dropForeignKey($installer->getTable('izberg/product'), $installer->getFkName('izberg/product', 'izberg_merchant_id', 'izberg/merchant', 'merchant_id'));

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/merchant'),
    'merchant_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/merchant'),
    'izberg_merchant_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);


// Table product
$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/product'),
    'product_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/product'),
    'izberg_product_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/product'),
    'izberg_merchant_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/product'),
    'izberg_category_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);


// Add constraint again

// Table category
$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/category'),
    'category_id',
    array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);

$installer->getConnection()->modifyColumn(
    $installer->getTable('izberg/category'),
    'magento_matching_category_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    )
);


//Readd foreign keys
$installer->getConnection()->addForeignKey($installer->getFkName('izberg/catalog_product', 'catalog_product_id', 'catalog/product', 'entity_id'),
    $installer->getTable('izberg/catalog_product'), 'catalog_product_id', $installer->getTable('catalog/product'), 'entity_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
);

$installer->getConnection()->addForeignKey($installer->getFkName('izberg/catalog_product', 'izberg_product_id', 'izberg/product', 'product_id'),
    $installer->getTable('izberg/catalog_product'), 'izberg_product_id', $installer->getTable('izberg/product'), 'product_id',
    Varien_Db_Ddl_Table::ACTION_NO_ACTION, Varien_Db_Ddl_Table::ACTION_CASCADE
);

$installer->getConnection()->addForeignKey($installer->getFkName('izberg/product', 'izberg_merchant_id', 'izberg/merchant', 'merchant_id'),
    $installer->getTable('izberg/product'), 'izberg_merchant_id', $installer->getTable('izberg/merchant'), 'merchant_id',
    Varien_Db_Ddl_Table::ACTION_NO_ACTION, Varien_Db_Ddl_Table::ACTION_NO_ACTION
);


$installer->endSetup();
