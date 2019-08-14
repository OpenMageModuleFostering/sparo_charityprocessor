<?php

class Sparo_CharityProcessor_Model_Hideshow {
  public function toOptionArray(){
    return array(
    array('value' => 'none', 'label' => 'Hide'),
      array('value' => 'block', 'label' => 'Show')
    );
  }
}
