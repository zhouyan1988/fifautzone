<?php

// This class will take an Izberg_product and stores in params and will output an array
// compatible with ApiImport
// The logic is to treat column by column, then get the longest and recreate import lines (like if we imported as a csv)
class Sebfie_Izberg_Model_Product_Importer
{
  protected $_product;
  protected $_xml;
  protected $_current_xml;
  protected $_parent_xml;
  protected $_is_child_product;
  protected $_stores;
  protected $_websites;

  // Variables to get the final result
  protected $_lines;
  protected $_current_line;
  protected $_values_by_column;
  protected $_result;
  protected $_products_to_link;

  public static $CONFIGURABLE_ATTRIBUTES = array("size", "color");

  protected static $COLUMNS = array(
    'sku',
    'name',
    'description',
    'short_description',
    'weight',
    'status',
    'visibility',
    '_type',
    'price',
    // 'special_price',
    // 'special_from_date',
    // 'special_to_date',
    'tax_class_id',
    'free_izberg_shipping',
    '_attribute_set',
    'is_in_stock',
    'qty',
    '_product_websites',
    '_store',
    '_category',
    // 'size',

  );

  protected static $COLUMNS_FOR_SIMPLE_FROM_CONFIGURABLE = array(
    '_super_products_sku',
    '_super_attribute_code',
    '_super_attribute_option',
  );

  // $_values_by_column:
  // [
  //   "name" => array("product_name"),
  //   "_store" => array("fr","en, es")
  // ]

  // $is_child_product is true if we import a child product from a configurable one
  public function __construct(Sebfie_Izberg_Model_Product $product, $stores, $is_child_product = false)
  {
      $this->_product = $product;
      $this->setStore($stores[0]);
      $this->_xml = $product->getIzbergResponse();
      $this->_current_xml = $this->_xml;
      $this->_is_child_product = $is_child_product;
      $this->_values_by_column = array();
      $this->_result = array();
      $this->_lines = array();
      $this->_products_to_link = array();
      $this->_current_line = 0;
      $this->_stores = $stores;
      $this->_websites = $this->retreiveWebsites();
  }

  // It will output the multi dimensional array for ApiImport
  public function output()
  {
    // First, we manage variations
    if ($this->_product->hasVariations()) {
      $this->_parent_xml = $this->_current_xml;
      $this->_is_child_product = true;
    }
    foreach($this->_product->getVariations() as $variation) {
      $this->_current_xml = $variation;
      $this->hydrate();
      $this->format();
      array_push($this->_products_to_link, array(
        "_super_products_sku" => $this->_result[0]["sku"],
        "_super_attribute_code" => "size",
        "_super_attribute_option" => "M",
      ));
      $this->startNewLine();
    }

    // Then we treat the current product
    $this->_is_child_product = false;
    $this->_current_xml = $this->_xml;
    $this->hydrate(true);
    $this->format();

    // The we finished the product
    $this->finish();

    return $this->_lines;
  }

  public function startNewLine()
  {
    $this->_lines[$this->_current_line] = $this->_result;
    $this->_current_line++;
    $this->_result = array();
    $this->_values_by_column = array();
  }

  public function finish()
  {
    $this->startNewLine();

    // We flatten _lines array
    $r = array();
    foreach ($this->_lines as $line) {
      foreach($line as $subline) {
        array_push($r, $subline);
      }
    }
    $this->_lines = $r;
  }

  protected function setStore($store_code)
  {
    $store = Mage::getModel('core/store')->load($store_code, 'code');
    Mage::app()->setCurrentStore($store->getId());
  }

  protected function retreiveWebsites()
  {
    $result = array();
    foreach($this->_stores as $store_code) {
      $store = Mage::getModel('core/store')->load($store_code, 'code');
      array_push($result, $store->getWebsiteId());
    }
    return array_unique($result);
  }

  // This function will feed $_values_by_column
  protected function hydrate($with_extra_columns = false)
  {
    $this->completeXML();
    foreach(self::$COLUMNS as $column_name) {
      $this->_values_by_column[$column_name] = $this->$column_name();
    }
    if ($with_extra_columns) {
      foreach(self::$COLUMNS_FOR_SIMPLE_FROM_CONFIGURABLE as $column_name) {
        $this->_values_by_column[$column_name] = $this->$column_name();
      }
    }
    $this->completeProductAttributesFromMatchingAttributes();
    $this->overwriteAttributeFromModuleSettings();
    $this->overwriteAttributeFromProductForceAttributes();
  }

  // This function will feed $_result
  protected function format()
  {
    foreach($this->_values_by_column as $name => $values) {
      foreach($values as $index => $value) {
        if (!isset($this->_result[$index])) $this->_result[$index] = array();
        $this->_result[$index][$name] = $value;
      }
    }
  }

  protected function getProduct()
  {
    return $this->_product;
  }


  // Functions called to get attributes values
  protected function sku()
  {
    return array($this->_is_child_product ? "izb-v" . (string)$this->_current_xml->id :  "izb-" .  (string)$this->_current_xml->id);
  }

  protected function name()
  {
    $prefixNameWithBrand = Mage::helper("izberg/config")->prefixProductNameWithBrand();
    $name = "";
    if ($prefixNameWithBrand) {
      $name.= $this->_is_child_product ? (string) $this->_parent_xml->brand->name: (string) $this->_current_xml->brand->name;
      $name.= " - ";
    }
    $name.= $this->_is_child_product ? (string) $this->_parent_xml->name . " - " . (string) $this->_current_xml->name : $this->_current_xml->name;
    return array($name);
  }

  protected function description()
  {

    $text = $this->_is_child_product ? (string)$this->_parent_xml->description : (string)$this->_current_xml->description;
    return array(Mage::helper("izberg")->toText($text));
  }

  protected function short_description()
  {
    return $this->description();
  }

  protected function weight()
  {
    $weight = $this->_is_child_product ? (string) $this->_current_xml->weight_numeral : (string)$this->_current_xml->best_offer->weight_numeral;
    return array($weight);
  }

  protected function status()
  {
    $status = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
    if (!$this->has_stock()) {
      if (Mage::helper("izberg/config")->skipProductsWithoutStock()) {
        // We remove product if already imported
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $this->sku());
        // We also delete all childs if it's a configurable product
        if ($product && !$this->_is_child_product) {
          $childProducts = Mage::getModel('catalog/product_type_configurable')
                  ->getUsedProducts(null,$product);
          foreach($childProducts as $child) {
            $child->delete();
          }
          $product->delete();
        }
      } else if (Mage::helper("izberg/config")->disableProductsWithoutStock()) {
        $status = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
      }
    }
    return array($status);
  }

  protected function is_in_stock()
  {
    return array($this->has_stock());
  }

  protected function has_stock()
  {
    return $this->qty() > 0 ? true : false;
  }

  protected function qty()
  {
    $qty = $this->_is_child_product ? (int) $this->_current_xml->stock : (int) $this->_current_xml->best_offer->stock;
    return array($qty);
  }

  protected function visibility()
  {
    return array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
  }

  protected function _type()
  {
    $result = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
    if ($this->_current_xml->best_offer->variations && count($this->_current_xml->best_offer->variations->children()) > 0) {
      $result = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }
    return array($result);
  }

  protected function price()
  {
    $use_tax = Mage::helper("izberg/config")->useTax();
    if ($this->_is_child_product) {
      $price = $use_tax ? (float) $this->_current_xml->price_with_vat : (float) $this->_current_xml->price_without_vat;
    } else {
      $price = $use_tax ? (float) $this->_current_xml->best_offer->price_with_vat : (float) $this->_current_xml->best_offer->price_without_vat;
    }
    return array($price);
  }

  protected function tax_class_id()
  {
    $tax_class_id = Mage::helper("izberg/config")->getTaxeGroupId() ? Mage::helper("izberg/config")->getTaxeGroupId() : 0;
    return array($tax_class_id);
  }

  protected function free_izberg_shipping()
  {
    return array(strtoupper(Mage::helper("core")->__("Yes")));
  }

  protected function _attribute_set()
  {
    $set = Mage::helper("izberg")->getDefaultProductAttributeSet();
    return array($set->getAttributeSetName());
  }

  protected function _product_websites()
  {
    $codes = array();
    foreach($this->_websites as $website_id) {
      $website = Mage::app()->getWebsite($website_id);
      array_push($codes, $website->getCode());
    }
    return $codes;
  }

  protected function _store()
  {
    $codes = array();
    foreach($this->_stores as $store_code) {
      array_push($codes, $store_code);
    }
    return $codes;
  }

  protected function _super_products_sku()
  {
    $r = array();
    foreach($this->_products_to_link as $to_link)
    {
      array_push($r, $to_link["_super_products_sku"]);
    }
    return $r;
  }

  protected function _super_attribute_code()
  {
    $r = array();
    foreach($this->_products_to_link as $to_link)
    {
      array_push($r, $to_link["_super_attribute_code"]);
    }
    return $r;
  }

  protected function _super_attribute_option()
  {
    $r = array();
    foreach($this->_products_to_link as $to_link)
    {
      array_push($r, $to_link["_super_attribute_option"]);
    }
    return $r;
  }

  protected function _category() {
    if (($this->_is_child_product && Mage::helper("izberg/config")->link_configurable_to_categories()) || !$this->_is_child_product) {
      // We add product categories
      $magentoCategories = $this->_product->getCategoryMatching();
      $category_full_paths = array();
      if ($magentoCategories) {
        $final_ids = array();
        foreach($magentoCategories as $c) {
          $path = $c->getPath();
          $ids = explode('/', $path);
          unset($ids[0]);
          unset($ids[1]);
          $category_full_path = array();
          $cpath = "";
          foreach($ids as $id) {
            $category = Mage::getModel("catalog/category")->load($id);
            array_push($category_full_path, $category->getName());
            array_push($category_full_paths, implode($category_full_path,"/"));
          }
        }
      } else {
        Mage::helper("izberg/log")->log("We did not set any category for product " . $name . " so it will not appear in your catalog");
      }

      $cpath = "";
      $r = array();
      foreach($category_full_paths as $counter => $cpath)
      {
        array_push($r,$cpath);
      }
      return $r;
    }
  }


  # ======================================
  # Functions to post process attributes
  # ======================================

  // This function will add element in xml to make parsing easier
  protected function completeXML()
  {
    if ($this->_is_child_product) {
      $this->_current_xml->merchant_name = (string) $this->_parent_xml->best_offer->merchant->name;
    } else {
      $this->_current_xml->merchant_name = $this->_current_xml->best_offer->merchant->name;
    }
  }

  protected function completeProductAttributesFromMatchingAttributes(){
    $matchedAttributes = Mage::getModel("izberg/attribute")->getCollection();
    foreach($matchedAttributes as $attribute){
      $attr = Sebfie_Izberg_Model_Attribute::getMagentoAttributeFromIzbergAttributeCode($attribute->getIzbergMatchingAttributeCode());
      $attribute_code = $attribute->getIzbergMatchingAttributeCode();

      if (isset($this->_current_xml->$attribute_code)) {
        $this->_values_by_column[$attr->getAttributeCode()] = array(
          Mage::helper("izberg")->toText($this->_current_xml->$attribute_code)
        );
      }
    }
  }

  protected function overwriteAttributeFromModuleSettings() {
    try {
        $json_config = Mage::helper('core')->jsonDecode(Mage::getStoreConfig("izberg/izberg_product_settings/izberg_default_product_attribute", Mage::app()->getStore()));
        foreach($json_config as $key => $value) {
          if (is_bool($value)) $value = ($value) ? Mage::helper("core")->__("Yes") : Mage::helper("core")->__("No") ;
          $this->_values_by_column[$key] = array($value);
        }
      } catch (Exception $e) {
        // In case the json is not well formated
      }
  }

  protected function overwriteAttributeFromProductForceAttributes() {
      try {
        $json_config = (array) $this->_product->getForceAttributesValues();
        foreach($json_config as $key => $value) {
          if (is_array($value)) continue;
          if (is_bool($value)) $value = ($value) ? Mage::helper("core")->__("Yes") : Mage::helper("core")->__("No") ;
          $this->_values_by_column[$key] = array($value);
        }
      } catch (Exception $e) {
        // In case the json is not well formated
      }
  }


}
