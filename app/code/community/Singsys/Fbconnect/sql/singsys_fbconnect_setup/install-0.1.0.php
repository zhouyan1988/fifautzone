<?php
$installer = $this;
$installer->startSetup();

$installer->setCustomerAttributes(
    array(
        'singsys_fbconnect_fid' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),            
        'singsys_fbconnect_ftoken' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        )            
    )
);
$installer->installCustomerAttributes();
$installer->endSetup();
