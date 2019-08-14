<?php

class Sparo_CharityProcessor_Model_Categories {
  public function toOptionArray(){
    return array(
      array('value' => '', 'label' => '---- Select ----'),
      array('value' => '1', 'label' => 'Beauty, Health & Grocery'),
      array('value' => '2', 'label' => 'Clothing, Shoes & Jewelry'),
      array('value' => '3', 'label' => 'Services'),
      array('value' => '4', 'label' => 'Automotive & Industrial'),
      array('value' => '5', 'label' => 'Books & Audible'),
      array('value' => '6', 'label' => 'Electronics & Computers'),
      array('value' => '7', 'label' => 'Home, Garden & Tools'),
      array('value' => '8', 'label' => 'Movies, Music, Games'),
      array('value' => '9', 'label' => 'Other'),
      array('value' => '10', 'label' => 'Sports & Outdoors'),
      array('value' => '11', 'label' => 'Toys, Kids & Baby')
    );
  }
}

?>
