<?php

class Sparo_CharityProcessor_Model_SaleObserver {

  public function sendConfirmation($observer){
    $transaction_id = Mage::getModel('core/cookie')->get('sparo_txid');

    if(!empty($transaction_id)){
      // fetch the order
      $order = $observer->getEvent()->getOrder();
      if($order->getAlreadyProcessedBySparo()){
        return;
      }

      // retrieve sparo api connection info
      $merchant_id = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_merchantid');
      $apikey = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_apikey');
      $environment = Mage::getStoreConfig('sparoconfig/sparoconfig_merchant/sparo_productionmode');

      // send confirmation with charity and transaction id
      $url = (strstr($environment, 'demo.sparo.com') !== false) ? 'http://tx2.demo.sparo.com/confirm.php' : 'https://tx2.sparo.com/confirm.php';

      $postdata = array(
        'merchant' => $merchant_id,
        'apikey' => $apikey,
        'txid' => $transaction_id,
        'txorderid' => $order->getRealOrderId(),
        'txcost' => $order->getSubtotal()
      );

      $opts = array(              
        'http' => array(          
          'method'=>'POST',       
          'content'=>http_build_query($postdata)
        )                         
      );                          
                                  
      $context = stream_context_create($opts);
      $fp = fopen($url, 'rb', false, $context);
                                  
      if(!$fp){                   
        $this->logMessage(sprintf("Problem connecting to url: %s", $url));
      }                           
      else{                       
        $res = stream_get_contents($fp);   
        fclose($fp);
        if($res === false){       
          $this->logMessage(sprintf("%s [order id: %s] [transaction id: %s] Error initiating connection with server: %s", date('c'), $order->getRealOrderId(), $transaction_id, curl_error($ch)));
        }                         
        else{                     
          $this->logMessage(sprintf("%s [order id: %s] [transaction id: %s] Server responded with status code: %d", date('c'), $order->getRealOrderId(), $transaction_id, $res['http_code']));
        }                         
      }                           

      // unset session variables
      Mage::getModel('core/cookie')->delete('sparo_txid');
      Mage::getModel('core/cookie')->delete('sparo_charity');
      $order->setAlreadyProcessedBySparo(true);
    }
  }

  protected function logMessage($msg){
    Mage::log($msg, null, 'sparo.log');
  }

}
