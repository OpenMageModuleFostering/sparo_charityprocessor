<?php

class Sparo_CharityProcessor_Model_Sales {
  public function toOptionArray(){
    return array(
      array('value' => '', 'label' => '---- Select ----'),
      array('value' => 'Below $30,000', 'label' => 'Below $30,000'),
      array('value' => '$30,000-$50,000', 'label' => '$30,000-$50,000'),
      array('value' => '$50,000-$100,000', 'label' => '$50,000-$100,000'),
      array('value' => '$100,000-$200,000', 'label' => '$100,000-$200,000'),
      array('value' => 'Over $200,000', 'label' => 'Over $200,000'),
    );
  }
}

?>
