<?php

class Sparo_CharityProcessor_Model_Observer {

  public function sendConfirmation($observer){
    $transaction_id = Mage::getSingleton('core/session')->getTransactionId();

    if(!empty($transaction_id)){
      // fetch the order
      $order = $observer->getEvent()->getOrder();

      // retrieve sparo api connection info
      $merchant_id = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_merchantid');
      $apikey = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_apikey');
      $production = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_productionmode');

      // send confirmation with charity and transaction id
      $url = ($production) ? 
        'https://tx.sparo.com/confirm.php' :
        'http://tx.demo.sparo.com/confirm.php';

      $postdata = array(
        'merchant' => $merchant_id,
        'apikey' => $apikey,
        'txid' => $transaction_id,
        'txorderid' => $order->getRealOrderId(),
        'txcost' => $order->getSubtotal()
      );


      $ch = curl_init();
      if(!empty($ch)){
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

        curl_exec($ch);

        if(!curl_errno($ch)){
          // successful connection
          $res = curl_getinfo($ch);
          $this->logMessage(sprintf("%s [order id: %s] [transaction id: %s] Server responded with status code: %d", date('c'), $order->getRealOrderId(), $transaction_id, $res['http_code']));
        }
        else{
          // connection failure
          $this->logMessage(sprintf("%s [order id: %s] [transaction id: %s] Error initiating connection with server: %s", date('c'), $order->getRealOrderId(), $transaction_id, curl_error($ch)));
        }

        curl_close($ch);
      }

      // unset session variables
      Mage::getSingleton('core/session')->unsCharityId();
      Mage::getSingleton('core/session')->unsTransactionId();
    }
  }

  protected function logMessage($msg){
    Mage::log($msg, null, 'sparo.log');
  }

}
