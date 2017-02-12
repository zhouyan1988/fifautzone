<?php
require_once "app/Mage.php";
umask(0);
Mage::app('admin');
Mage::setIsDeveloperMode(true);

$productCollection = Mage::getResourceModel('catalog/product_collection');
$productCollection->addAttributeToFilter(
array(
    array('attribute'=>'sku','like'=>"%izb-%")
));
foreach($productCollection as $product){
  echo $product->getId();
  echo "<br/>";
           $MediaDir=Mage::getConfig()->getOptions()->getMediaDir();
          echo $MediaCatalogDir=$MediaDir .DS . 'catalog' . DS . 'product';
  echo "<br/>";

  $MediaGallery=Mage::getModel('catalog/product_attribute_media_api')->items($product->getId());
  echo "<pre>";
  print_r($MediaGallery);
  echo "</pre>";

      foreach($MediaGallery as $eachImge){
          $MediaDir=Mage::getConfig()->getOptions()->getMediaDir();
          $MediaCatalogDir=$MediaDir .DS . 'catalog' . DS . 'product';
          $DirImagePath=str_replace("/",DS,$eachImge['file']);
          $DirImagePath=$DirImagePath;
          // remove file from Dir

          $io     = new Varien_Io_File();
           $io->rm($MediaCatalogDir.$DirImagePath);

          $remove=Mage::getModel('catalog/product_attribute_media_api')->remove($product->getId(),$eachImge['file']);
      }
}
