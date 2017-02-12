<?php
require_once "app/Mage.php";
Mage::app();

Mage::helper("izberg")->log("Running magmi from command line", 8);
Sebfie_Izberg_Model_Magmi::import();
Mage::helper("izberg")->log("End magmi from command line", 8);
