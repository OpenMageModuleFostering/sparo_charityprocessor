<?php

class Sparo_CharityProcessor_Model_themes {
  public function toOptionArray(){
    $themes = Mage::getSingleton('core/design_package')->getThemeList();
    $options = array();
    array_push($options, array('value' => '', 'label' => '---- Select ----'));
    foreach($themes as $theme_name => $theme_options){
      foreach($theme_options as $theme_option){
        array_push($options, array('value' => $theme_name . '/' . $theme_option, 'label' => $theme_name . '/' . $theme_option));
      }
    }
    return $options;
  }
}

?>
