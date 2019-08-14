<?php

class Sparo_CharityProcessor_Model_Resource_Script_Setup extends Mage_Core_Model_Resource_Setup {
  
  public function addScriptBlock(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $content = <<<HTML
    <script type="text/javascript" id="SPARO-lib" data-merchant="{{config path="sparoconfig/sparoconfig_merchant/sparo_merchantid"}}" data-callback-url="{{config path="web/unsecure/base_url"}}/charityprocessor" src="https://scripts.sparo.com/sparo.min.js"></script>
HTML;

    $scriptBlock = array(
      'title' => 'Sparo Charity Selection Tags',
      'identifier' => 'sparo_charity_selection_tags',
      'content' => $content,
      'is_active' => 1,
      'stores' => array(0),
    );

    Mage::getModel('cms/block')->setData($scriptBlock)->save();

  }

  public function addScriptBlock_1_0_2(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $content = <<<HTML
    <script type="text/javascript" id="SPARO-lib" data-merchant="{{config path="sparoconfig/sparoconfig_merchant/sparo_merchantid"}}" src="{{config path="sparoconfig/sparoconfig_merchant/sparo_productionmode"}}/sparo.min.js"></script>
HTML;

    $scriptBlock = array(
      'title' => 'Sparo Charity Selection Tags',
      'identifier' => 'sparo_charity_selection_tags',
      'content' => $content,
      'is_active' => 1,
      'stores' => array(0),
    );

    Mage::getModel('cms/block')->setData($scriptBlock)->save();
  }

  public function addContainerBlock(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $containerBlock = array(
      'title' => 'Sparo Widget Container',
      'identifier' => 'sparo_widget_container',
      'content' => '<div id="sparo-mount"></div>',
      'is_active' => 1,
      'stores' => array(0),
    );


    Mage::getModel('cms/block')->setData($containerBlock)->save();
  }

  public function updateScriptBlock_1_0_2() {
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $blocks = Mage::getModel('cms/block')
      ->getCollection()
      ->addFieldToFilter('identifier', 'sparo_charity_selection_tags');

    $new_content = <<<HTML
    <script type="text/javascript" id="SPARO-lib" data-merchant="{{config path="sparoconfig/sparoconfig_merchant/sparo_merchantid"}}" src="{{config path="sparoconfig/sparoconfig_merchant/sparo_productionmode"}}/sparo.min.js"></script>
HTML;

    $updated_data = array('content' => $new_content);

    foreach($blocks as $b){
      $model = Mage::getModel('cms/block')->load($b->getId())->addData($updated_data);
      $model->save();
    }

  }

}
