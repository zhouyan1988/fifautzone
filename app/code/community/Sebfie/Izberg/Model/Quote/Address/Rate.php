<?php
class Sebfie_Izberg_Model_Quote_Address_Rate extends Mage_Sales_Model_Quote_Address_Rate
{

   public function getIzbergRate($address)
   {
     $quote = $address->getQuote();
     $izberg = Mage::helper("izberg")->getIzberg(array("quote_id" => $quote->getId()));

     $firstname = trim($quote->getCustomerFirstname()) == "" || is_null($quote->getCustomerFirstname()) ? $address->getFirstname() : $quote->getCustomerFirstname();
     $lastName = trim($quote->getCustomerLastname()) == "" || is_null($quote->getCustomerLastname()) ? $address->getLastname() : $quote->getCustomerLastname();

     $email = $quote->getBillingAddress()->getEmail();
     if (!$email) $email = $quote->getCustomerEmail();

     Mage::helper("izberg")->sso(array(
       "email" => $email,
       "firstName" => $firstname,
       "lastName" => $lastName,
       "quote_id" => $quote->getId()
     ));

     // Only for a complete address
     if ($quote->getBillingAddress()->getCity()) Mage::helper("izberg")->setIzbergBillingAddress($quote->getBillingAddress());
     if ($quote->getShippingAddress()->getCity()) Mage::helper("izberg")->setIzbergShippingAddress($quote->getShippingAddress());

     $cart = Mage::helper("izberg")->getIzbergCart($izberg);

     // Manage exceptions
     switch ($cart->status) {
        case Sebfie_Izberg_Helper_Data::IZBERG_CART_STATUS_EMPTY:
            throw new Exception(Mage::helper("izberg")->__("Your cart is empty"));
            break;
        case Sebfie_Izberg_Helper_Data::IZBERG_CART_STATUS_DONE:
            throw new Exception(Mage::helper("izberg")->__("Cart already managed"));
            break;
        case Sebfie_Izberg_Helper_Data::IZBERG_CART_STATUS_SHIPPING_IMPOSSIBLE:
            throw new Exception(Mage::helper("izberg")->__("Shipping impossible"));
            break;
        case Sebfie_Izberg_Helper_Data::IZBERG_CART_STATUS_NO_STOCK:
            throw new Exception(Mage::helper("izberg")->__("One or more products are not in stock"));
            break;
        case Sebfie_Izberg_Helper_Data::IZBERG_CART_STATUS_CANCELED:
            throw new Exception(Mage::helper("izberg")->__("This cart has been canceled"));
            break;
     }

    return $cart->shipping_amount;
   }

   public function hasOnlyIzbergProduct($address)
   {
     $quote = $address->getQuote();
     $has_only_izberg_products = true;
     foreach($quote->getAllVisibleItems() as $item){
          $productSku = $item->getProduct()->getSku();
          if(!Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $productSku)->getFirstItem()->getId()){
              $has_only_izberg_products = false;
          }
      }
      return $has_only_izberg_products;
   }

   public function hasIzbergProduct($address)
   {
     $quote = $address->getQuote();
     $has_izberg_products = false;
     foreach($quote->getAllVisibleItems() as $item){
          $productSku = $item->getProduct()->getSku();
          if(Mage::getModel("izberg/catalog_product")->getCollection()->addFieldToFilter("catalog_product_sku" , $productSku)->getFirstItem()->getId()){
              $has_izberg_products = true;
          }
      }
      return $has_izberg_products;
   }

   public function importShippingRate(Mage_Shipping_Model_Rate_Result_Abstract $rate)
    {
        if ($rate instanceof Mage_Shipping_Model_Rate_Result_Error) {
            $this
                ->setCode($rate->getCarrier().'_error')
                ->setCarrier($rate->getCarrier())
                ->setCarrierTitle($rate->getCarrierTitle())
                ->setErrorMessage($rate->getErrorMessage())
            ;
        } elseif ($rate instanceof Mage_Shipping_Model_Rate_Result_Method) {
            $address = $this->getAddress();
            if ($this->hasIzbergProduct($address)) {
              try {
                $izberg_rate = $this->getIzbergRate($address);
              } catch (Exception $e){
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($rate->getCode());
                $error->setCarrierTitle($rate->getCarrierTitle());
                $error->setErrorMessage($e->getMessage());
                // We loop no return no shipping methods
                return $this->importShippingRate($error);
              }
              $price = $this->hasOnlyIzbergProduct($address) ? $izberg_rate : $rate->getPrice() + $izberg_rate;
            } else {
              $price = $rate->getPrice();
            }

            $this
                ->setCode($rate->getCarrier().'_'.$rate->getMethod())
                ->setCarrier($rate->getCarrier())
                ->setCarrierTitle($rate->getCarrierTitle())
                ->setMethod($rate->getMethod())
                ->setMethodTitle($rate->getMethodTitle())
                ->setMethodDescription($rate->getMethodDescription())
                ->setPrice((float)$price)
            ;
        }
        return $this;
    }
}
