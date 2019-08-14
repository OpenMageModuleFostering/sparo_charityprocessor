<?php

class Sparo_CharityProcessor_Model_Donations {
  public function toOptionArray(){
    $donation_options = array();
    for($i = 2; $i <= 90; $i++){
      $donation_option = array('value' => $i, 'label' => $i);
      array_push($donation_options, $donation_option);
    }
    return $donation_options;
  }
}
