<?php 

class Sparo_CharityProcessor_Model_Environments {
  public function toOptionArray(){
    return array(
      array('value' => 'http://scripts.demo.sparo.com', 'label' => 'No'),
      array('value' => 'https://scripts.sparo.com', 'label' => 'Yes')
    );
  }
}
