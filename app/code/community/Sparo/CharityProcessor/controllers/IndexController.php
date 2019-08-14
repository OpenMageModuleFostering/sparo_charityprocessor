<?php 
class Sparo_CharityProcessor_IndexController extends Mage_Core_Controller_Front_Action {


  public function indexAction(){
    $model = new Sparo_CharityProcessor_Model_SaleObserver();
    var_dump(get_class($model));

    $model = Mage::getModel('sparo_charityprocessor/saleobserver');
    var_dump(get_class($model));
    //$charity_id = $this->getRequest()->getParam('charity');
    //$transaction_id = $this->getRequest()->getParam('txid');

    //if(!empty($charity_id)){
      //Mage::getSingleton('core/session')->setCharityId($charity_id);
    //}
    //else {
      //Mage::getSingleton('core/session')->unsCharityId();
    //}


    //if(!empty($transaction_id)){
      //Mage::getSingleton('core/session')->setTransactionId($transaction_id);
    //}
    //else {
      //Mage::getSingleton('core/session')->unsTransactionId();
    //}
  }
}

