<?php 
class Sparo_CharityProcessor_IndexController extends Mage_Core_Controller_Front_Action {


  public function indexAction(){
    $charity_id = $this->getRequest()->getParam('charity_id');
    $transaction_id = $this->getRequest()->getParam('tx_id');

    if(!empty($charity_id)){
      Mage::getSingleton('core/session')->setCharityId($charity_id);
    }


    if(!empty($transaction_id)){
      Mage::getSingleton('core/session')->setTransactionId($transaction_id);
    }

  }
}

