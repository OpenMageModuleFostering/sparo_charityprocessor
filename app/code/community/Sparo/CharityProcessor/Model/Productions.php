<?php

class Sparo_CharityProcessor_Model_Productions {
  public function toOptionArray(){
    return array(
      array('value' => 0, 'label' => 'Test Mode'),
      array('value' => 1, 'label' => 'Production')
    );
  }
}
