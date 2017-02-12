<?php
class Sebfie_Izberg_Adminhtml_MatchingController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('izberg/matching_categories')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Matching categories'), Mage::helper('adminhtml')->__('Matching categories'));
        return $this;
    }

    public function categoriesAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_matching_categories')->setTemplate("izberg/matching/categories.phtml"));
        $this->renderLayout();
    }

    public function attributesAction() {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('izberg/adminhtml_matching_attributes')->setTemplate("izberg/matching/attributes.phtml"));
        $this->renderLayout();
    }

    public function getChildCategoriesAction() {
        $params = $this->getRequest()->getPost();
        $type = $params["type"];
        $categories = Mage::helper("izberg")->getIzberg()->get_list(Mage::helper("izberg")->getIzberg()->getHelper()->camelize($type), array("parents" => $params["parent_id"]));

        echo Mage::Helper('core')->jsonEncode($categories);
    }

    public function importCategoriesAction(){
        $izberg = Mage::helper("izberg")->getIzberg();
        try {
            $categories = $izberg->get_list("category");

            Mage::helper("izberg")->manageCategoriesFromAPIResponse($categories);

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("We successfully created/updated categories"));
            $this->_redirect('*/*/categories');

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/categories');
        }

    }

    public function postCategoriesAction() {
        $izberg = Mage::helper("izberg")->getIzberg();
        $data = $this->getRequest()->getPost();

        try {
          if ($this->getRequest()->isPost() && $data) {
              $type = $data["type"];
              Sebfie_Izberg_Model_Category::resetMatching($type);
              foreach($data["matching"] as $key => $value) {
                  $magento_category_id = reset($value["magento"]);
                  $valArr = array_filter($value["izberg"]);
                  $genders = array_filter($value["gender"]);

                  // We add / before and after to search values easier
                  $catPath = "/" . implode($valArr, "/") . "/";

                  $izberg_category = Mage::getModel("izberg/category");

                  if ($catPath && !empty($catPath) && $catPath != "//") {
                      $izberg_category->setMagentoMatchingCategoryId($magento_category_id);
                      $izberg_category->setIzbergCategoryPath($catPath);
                      $izberg_category->setGender(json_encode($genders));
                      $izberg_category->setBreadcrumb($value["breadcrumb"]);

                      if ($izberg_category->isObjectNew()) {
                          $izberg_category->setCreatedAt(time());
                      }
                      $izberg_category->setType($type);
                      $izberg_category->save();
                  }
              }
              Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("We successfully saved categories matching"));
          } else {
            Mage::getSingleton('adminhtml/session')->addError("Bad request type");
          }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    // Method call in POST when we matched attributes
    public function postAttributesAction()
    {
        $izberg = Mage::helper("izberg")->getIzberg();
        $data = $this->getRequest()->getPost();

        if ($this->getRequest()->isPost() && $data) {
            foreach($data["attribute"] as $d) {
                if (!$d["value"]) continue;
                $magento_attribute_id = $d["value"];
                $izberg_attribute_code = $d["key"];
                $izberg_attribute = Mage::getModel("izberg/attribute")->getCollection()->addFieldToFilter('izberg_matching_attribute_code', $izberg_attribute_code)->getFirstItem();
                $izberg_attribute->setMagentoMatchingAttributeId($magento_attribute_id);
                $izberg_attribute->setIzbergMatchingAttributeCode($izberg_attribute_code);
                $izberg_attribute->setCreatedAt(time());
                $izberg_attribute->save();
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("We successfully saved attributes matching"));
            $this->_redirect('*/*/attributes');
        } else {
          Mage::getSingleton('adminhtml/session')->addError("Bad request type");
        }
    }


    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('izberg/matching/matching_categories');
    }
}
