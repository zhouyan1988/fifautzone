<?php

$installer = $this;
$installer->startSetup();

$installer = Mage::getResourceModel('sales/setup', 'sales_setup');

$order_entity_id = $installer->getEntityTypeId('order');
$list_attribute = array();
array_push($list_attribute , array(
    'name' => 'is_izberg_external_order',
    'label' => 'is Izberg order',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => true,
));
array_push($list_attribute , array(
    'name' => 'izberg_external_order_id',
    'label' => 'Izberg external id',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
));

foreach($list_attribute as $attr) {
  $order_attribute = $installer->getAttribute($order_entity_id, $attr['name']);
  if(!$order_attribute) {
      $attr =
      $installer->addAttribute('order', $attr["name"], array(
          'name' => $attr['name'],
          'label' => $attr['label'],
          'type' => $attr['type'],
          'visible' => true,
          'required' => false,
          'unique' => false,
          'filterable' => 1,
          'sort_order' => 700,
          'default' => $attr['default'],
          'input' => $attr['input'],
          'source' => $attr['source'],
          'grid'   => $attr['grid'],
      ));
  }
}

$installer->endSetup();
