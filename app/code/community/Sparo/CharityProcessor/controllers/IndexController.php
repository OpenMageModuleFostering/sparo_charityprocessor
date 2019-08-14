<?php 
class Sparo_CharityProcessor_IndexController extends Mage_Core_Controller_Front_Action {


  public function indexAction(){
    $charity_id = $this->getRequest()->getParam('charity');
    $transaction_id = $this->getRequest()->getParam('txid');

    if(!empty($charity_id)){
      Mage::getSingleton('core/session')->setCharityId($charity_id);
    }
    else {
      Mage::getSingleton('core/session')->unsCharityId();
    }


    if(!empty($transaction_id)){
      Mage::getSingleton('core/session')->setTransactionId($transaction_id);
    }
    else {
      Mage::getSingleton('core/session')->unsTransactionId();
    }
  }
}

