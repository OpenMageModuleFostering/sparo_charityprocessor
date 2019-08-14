<?php

class Sparo_CharityProcessor_Model_Reset {
  public function toOptionArray(){
    return array(
      array('value' => 0, 'label' => 'Do not logout'),
      array('value' => 1, 'label' => 'Logout upon save')
    );
  }
}
