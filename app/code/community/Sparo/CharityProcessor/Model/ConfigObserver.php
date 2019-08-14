<?php

class Sparo_CharityProcessor_Model_ConfigObserver {
  private $DEBUG_MODULE = false; // Override for all debug output
  private $DEBUG_SIGNIN = false; // Debug SIGN IN - will output HTTP response
  private $DEBUG_SIGNUP = false; // Debug SIGN UP - will output HTTP response

  private $server_signup_url = null;
  private $server_login_url = null;

  private $credential_keys = array('api_key', 'id');
  private $credential_map_keys = array('api_key' => 'apikey', 'id' => 'merchantid' );
  private $widget_created = 'widget_created';
  private $merchant_config_prefix = 'sparoconfig/sparoconfig_merchant/sparo_';
  private $account_config_prefix  = 'sparoconfig/sparoconfig_account/sparo_';
  private $settings_config_prefix  = 'sparoconfig/sparoconfig_settings/sparo_';
  private $account_detail_config_prefix  = 'sparoconfig/sparoconfig_account_detail/sparo_';
  private $signup_config_prefix  = 'sparoconfig/sparoconfig_signup/sparo_';
  private $donation_config_prefix  = 'sparoconfig/sparoconfig_donation/sparo_';
  private $contact_billing_config_prefix  = 'sparoconfig/sparoconfig_contact_billing/sparo_';
  private $login_config_prefix  = 'sparoconfig/sparoconfig_login/sparo_';
  private $merchant_agreement_config_prefix  = 'sparoconfig/sparoconfig_merchant_agreement/sparo_';

  public function __construct(){
    if(Mage::getStoreConfig($this->settings_config_prefix . 'staging_env')){
      $this->DEBUG_MODULE = true;
    }

    if($this->DEBUG_MODULE){
      // STAGING
      $this->server_signup_url = 'http://members.staging.sparo.com/retailers/self_serve.json';
      $this->server_login_url =  'http://members.staging.sparo.com/get_user_info';
    }else{
      // PRODUCTION
      $this->server_signup_url = 'https://members.sparo.com/retailers/self_serve.json';
      $this->server_login_url =  'https://members.sparo.com/get_user_info';
    }
  }

  /*
   * CHECK IF API KEY AND MERCHANT ID ARE PRESENT - GET AND STORE CREDENTIALS IF MISSING
   */
  public function configSparoPlugin($observer){
    if($this->needToSetupAccount() && $this->formIsValid()){
      $this->getSparoApiCredentials();
      $this->deletePasswordFromDatabase();
    }

    if($this->shouldResetModule()){
      $this->resetModule();
    }

    if($this->shouldCreateWidgets()){
      $this->createSparoWidgets();
    }
  }

  // ************************************************************************
  // ******************** PRIVATE METHODS ***********************************
  // ************************************************************************

  private function shouldCreateWidgets(){
    $theme = Mage::getStoreConfig($this->settings_config_prefix . 'widget_theme');
    $theme_setup = Mage::getStoreConfig($this->settings_config_prefix . 'widget_theme_setup');
    if(empty($theme_setup) && !empty($theme)){
      return true;
    }else{
      return false;
    }
  }

  private function shouldResetModule(){
    $reset = Mage::getStoreConfig($this->settings_config_prefix . 'reset');
    if(!empty($reset) && $reset == "1"){
      return true;
    }else{
      return false;
    }
  }

  private function resetModule(){
    $query = 'DELETE FROM core_config_data WHERE core_config_data.path LIKE "sparoconfig/sparoconfig_%"';
    $resource = Mage::getSingleton('core/resource');
    $writeConnection = $resource->getConnection('core_write');
    $writeConnection->query($query);
    Mage::app()->getCacheInstance()->cleanType('config');
  }

  /*
   * BUILD ACCOUNT SETTINGS AND STORE IN MAGENTO CONFIGURATION
   */
  private function getSparoApiCredentials(){
      if( $this->isLoginIn() ){

        $fields = array(
          //login
          'email' =>     Mage::getStoreConfig($this->login_config_prefix . 'email' ),
          'password' =>  Mage::getStoreConfig($this->login_config_prefix . 'password' )
        );

        $credentials = $this->curlSparoServerForLogin($fields);
        $this->storeSparoAccoutnInfo($credentials);
      }else{

        $fields = array(
          'merchant' => array(
           //signup_config
           'name' =>                  urlencode( Mage::getStoreConfig($this->signup_config_prefix . 'businessname') ),
           'sales_per_month' =>       urlencode( Mage::getStoreConfig($this->signup_config_prefix . 'sales_per_month') ),
           'website' =>               urlencode( Mage::getStoreConfig($this->signup_config_prefix . 'website') ),
           'employer_id' =>           urlencode( Mage::getStoreConfig($this->signup_config_prefix . 'employer_id') ),
           'category_id' =>           urlencode( Mage::getStoreConfig($this->signup_config_prefix . 'category') ),
           //donation
           'pct_donation' =>          urlencode( Mage::getStoreConfig($this->donation_config_prefix . 'donation_percentage') ),
           //contact_billing
           'address_attributes' =>
             array(
               'contact_first_name' =>  urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'firstname') ),
               'contact_last_name' =>   urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'lastname') ),
               'contact_email' =>       urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'email') ),
               'contact_phone' =>       urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'phone') ),
               'contact_address' =>     urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'address1') ) . " " . urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'adress2') ),
               'contact_city' =>        urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'city') ),
               'contact_state' =>       urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'state') ),
               'contact_zipcode' =>     urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'zipcode') ),
               'contact_country' =>     'US',
             ),
           //contact_billing
           'users_attributes' =>
              array(
               array(
                 'first_name' =>          urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'firstname') ),
                 'last_name' =>           urlencode( Mage::getStoreConfig($this->contact_billing_config_prefix . 'lastname') ),
                 'email' =>               Mage::getStoreConfig($this->contact_billing_config_prefix . 'email'),
               ),
             ),
           //merchant_agreement
           'agreement' =>             urlencode( Mage::getStoreConfig($this->merchant_agreement_config_prefix . 'agreement_text') ),
         )
       );

       $credentials = $this->curlSparoServer( $fields );
       $this->storeSparoApiCredentials($credentials);
       $this->setupFields();
     }
  }

  private function isLoginIn(){
    $email = Mage::getStoreConfig($this->login_config_prefix . 'email');
    $password = Mage::getStoreConfig($this->login_config_prefix . 'password');
    return !empty($email) && !empty($password);
  }

  private function deletePasswordFromDatabase(){
    $coreModelConfig = new Mage_Core_Model_Config();
    $coreModelConfig->saveConfig($this->login_config_prefix . 'password', '', 'default', 0);
  }

  private function getServerUrl(){
    if($this->isLoginIn()){
      return $this->server_login_url;
    }else{
      return $this->server_signup_url;
    }
  }

  /*
   * POST QUERY TO SERVER - GET + DECODE JSON BACK
   */
  private function curlSparoServer($fields, $SSL_VERSION = CURL_SSLVERSION_TLSv1_2){

    //url-ify the data for the POST
    $fields_json = json_encode($fields);

    //open connection
    $ch = curl_init();

    //build curl
    curl_setopt($ch, CURLOPT_URL, $this->getServerUrl());
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($this->DEBUG_MODULE && $this->DEBUG_SIGNUP){
      curl_setopt($ch, CURLOPT_VERBOSE, 1);
      curl_setopt($ch, CURLOPT_HEADER, 1);
    }
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    if(!$this->DEBUG_MODULE){
      curl_setopt($ch, CURLOPT_PORT, 443);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
    curl_setopt($ch, CURLOPT_SSLVERSION, $SSL_VERSION);

    //execute post
    $result = curl_exec($ch);
    $all_info = curl_getinfo($ch);
    $error = curl_error($ch);

    //close connection
    curl_close($ch);

    //json decode returned data
    $contents = utf8_encode($result);
    $result_array = json_decode($contents, true);

    if($error == "Unsupported SSL protocol version"){
      Mage::getSingleton('core/session')->addError($error . ": " . $SSL_VERSION);
      switch($SSL_VERSION){
        case CURL_SSLVERSION_TLSv1_2:
            $SSL_VERSION = CURL_SSLVERSION_SSLv3;
          break;
        case CURL_SSLVERSION_SSLv3:
            $SSL_VERSION = CURL_SSLVERSION_SSLv2;
        break;
        case CURL_SSLVERSION_SSLv2:
            $SSL_VERSION = CURL_SSLVERSION_DEFAULT;
        break;
        default:
            $SSL_VERSION = CURL_SSLVERSION_DEFAULT;
      }
      $result_array = $this->curlSparoServer($fields, $SSL_VERSION);
    }

    if(!empty($error)){
      Mage::getSingleton('core/session')->addError($error);
    }

    if(empty($result_array)){
      Mage::getSingleton('core/session')->addError('There was an error creating your account, please contact Sparo support.');
    }

    if($this->DEBUG_MODULE && $this->DEBUG_SIGNUP){
      echo '<b>SIGN UP RESULTS</b><br><br>URL: ';
      echo $this->getServerUrl();
      echo '<br><br>FIELDS: ';
      var_dump($fields);
      echo '<br><br>JSON PARAMS: ';
      echo $fields_json;
      echo '<br><br>ALL INFO: ';
      print_r($all_info);
      echo '<br><br>RESULT: ';
      print_r($result);
      echo '<br><br>ERROR: ';
      echo $error;
      echo '<br><br>CONTENTS: ';
      print_r($contents);
      echo '<br><br>RESULT ARRAY: ';
      print_r($result_array);
      die();
    }

    if(empty($contents)){
      Mage::getSingleton('core/session')->addError('There was an error creating your account, please contact Sparo support.');
    }

    return $result_array;
  }

  private function curlSparoServerForLogin($fields, $SSL_VERSION=CURL_SSLVERSION_TLSv1_2){

    $login = $fields['email'];
    $password = $fields['password'];

    //open connection
    $ch = curl_init();

    //build curl
    curl_setopt($ch, CURLOPT_URL, $this->getServerUrl());
    curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($this->DEBUG_MODULE && $this->DEBUG_SIGNIN){
      curl_setopt($ch, CURLOPT_VERBOSE, 1);
      curl_setopt($ch, CURLOPT_HEADER, 1);
    }
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    if(!$this->DEBUG_MODULE){
      curl_setopt($ch, CURLOPT_PORT, 443);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
    curl_setopt($ch, CURLOPT_SSLVERSION, $SSL_VERSION);

    //execute post
    $result = curl_exec($ch);
    $all_info = curl_getinfo($ch);
    $error = curl_error($ch);

    //close connection
    curl_close($ch);

    $contents = utf8_encode($result);
    $result_array = json_decode($contents, true);

    if($error == "Unsupported SSL protocol version"){
      $result_array = $this->curlSparoServerForLogin($fields, true);
    }

    if($error == "Unsupported SSL protocol version"){
      Mage::getSingleton('core/session')->addError($error . ": " . $SSL_VERSION);
      switch($SSL_VERSION){
        case CURL_SSLVERSION_TLSv1_2:
            $SSL_VERSION = CURL_SSLVERSION_SSLv3;
          break;
        case CURL_SSLVERSION_SSLv3:
            $SSL_VERSION = CURL_SSLVERSION_SSLv2;
        break;
        case CURL_SSLVERSION_SSLv2:
            $SSL_VERSION = CURL_SSLVERSION_DEFAULT;
        break;
        default:
            $SSL_VERSION = CURL_SSLVERSION_DEFAULT;
      }
      $result_array = $this->curlSparoServerForLogin($fields, $SSL_VERSION);
    }

    if(!empty($error)){
      Mage::getSingleton('core/session')->addError($error);
    }

    if(empty($result_array)){
      Mage::getSingleton('core/session')->addError('There was an error creating your account, please contact Sparo support.');
    }

    if($this->DEBUG_MODULE && $this->DEBUG_SIGNIN){
      echo '<b>LOGIN RESULTS</b><br><br>URL: ';
      echo $this->getServerUrl();
      echo '<br><br>FIELDS: ';
      var_dump($fields);
      echo '<br><br>JSON PARAMS: ';
      echo $fields_json;
      echo '<br><br>ALL INFO: ';
      print_r($all_info);
      echo '<br><br>RESULT: ';
      print_r($result);
      echo '<br><br>ERROR: ';
      echo $error;
      echo '<br><br>CONTENTS: ';
      print_r($contents);
      echo '<br><br>RESULT ARRAY: ';
      print_r($result_array);
      die();
    }

    return $result_array;
  }

  /*
   * STORE CREDENTIALS TO MAGENTO MAIN CONFIGURATION TABLE
   */
  private function storeSparoApiCredentials($credential_values){
    //build Magento config manager
    $coreModelConfig = new Mage_Core_Model_Config();

    //loop through keys
    foreach($this->credential_keys as $key){
      //ensure key is present is credentials_array
      if(isset($credential_values[$key]) && !empty($credential_values[$key])){
        //extract value
        $value = $credential_values[$key];
        //build Sparo Magento conf key
        if($key == 'id'){
          $full_key_name = $this->account_detail_config_prefix . $this->credential_map_keys[$key];
        }else{
          $full_key_name = $this->settings_config_prefix . $this->credential_map_keys[$key];
        }
        //save value to core config
        $coreModelConfig->saveConfig($full_key_name, $value, 'default', 0);
      }
    }

    if(!empty($credential_values)){
      Mage::getSingleton('core/session')->getMessages(true);
    }

    if($credential_values['error']){
      Mage::getSingleton('core/session')->addError($credential_values['error']);
    }else{
      Mage::getSingleton('core/session')->addSuccess('Sparo Account Successfully Created');
    }
  }


  /*
   * STORE CREDENTIALS TO MAGENTO MAIN CONFIGURATION TABLE
   */
  private function storeSparoAccoutnInfo($account_info){
    //build Magento config manager
    $coreModelConfig = new Mage_Core_Model_Config();

    $merchant = $account_info['merchants'][0];
    $merchant_address = $merchant['address'];
    $merchant_category = $merchant['category'];

    $fields = array(
      $this->settings_config_prefix . 'apikey'                  => $merchant['api_key'],
      $this->account_detail_config_prefix . 'merchantid'        => $merchant['id'],
      $this->account_detail_config_prefix . 'business_name'     => $merchant['name'],
      $this->account_detail_config_prefix . 'website'           => $merchant['website'],
      $this->account_detail_config_prefix . 'email_address'     => $account_info['email'],
      $this->account_detail_config_prefix . 'phone'             => $merchant_address['contact_phone'],

      $this->signup_config_prefix . 'businessname'              => $merchant['name'],
      $this->signup_config_prefix . 'sales_per_month'           => $merchant['sales_per_month'],
      $this->signup_config_prefix . 'website'                   => $merchant['website'],
      $this->signup_config_prefix . 'category'                  => $merchant_category['id'],

      $this->donation_config_prefix . 'donation_percentage'     => $merchant['pct_donation'],
      $this->settings_config_prefix . 'donation'                => intval($merchant['pct_donation']),

      $this->contact_billing_config_prefix . 'firstname'        => $merchant_address['contact_first_name'],
      $this->contact_billing_config_prefix . 'lastname'         => $merchant_address['contact_last_name'],
      $this->contact_billing_config_prefix . 'email'            => $merchant_address['contact_email'],
      $this->contact_billing_config_prefix . 'phone'            => $merchant_address['contact_phone'],
      $this->contact_billing_config_prefix . 'address1'         => $merchant_address['contact_address'],
      $this->contact_billing_config_prefix . 'address2'         => $merchant_address['contact_address2'],
      $this->contact_billing_config_prefix . 'city'             => $merchant_address['contact_city'],
      $this->contact_billing_config_prefix . 'state'            => $merchant_address['contact_state'],
      $this->contact_billing_config_prefix . 'zipcode'          => $merchant_address['contact_zipcode']
    );

    //loop through keys
    foreach($fields as $key_name => $value){
      //save value to core config
      $coreModelConfig->saveConfig($key_name, $value, 'default', 0);
    }

    if(!empty($account_info)){
      Mage::getSingleton('core/session')->getMessages(true);
    }

    if($credential_values['error']){
      Mage::getSingleton('core/session')->addError($credential_values['error']);
    }
  }


  private function needToSetupAccount(){
    $apikey = Mage::getStoreConfig($this->settings_config_prefix . 'apikey');
    $merchantid = Mage::getStoreConfig($this->account_detail_config_prefix . 'merchantid');
    return empty($apikey) || empty($merchantid);
  }

  private function setupFields(){
    //build Magento config manager
    $coreModelConfig = new Mage_Core_Model_Config();

    //business name
    $value = Mage::getStoreConfig($this->signup_config_prefix . 'businessname');
    $coreModelConfig->saveConfig($this->account_detail_config_prefix . 'business_name', $value, 'default', 0);

    //website
    $value = Mage::getStoreConfig($this->signup_config_prefix . 'website');
    $coreModelConfig->saveConfig($this->account_detail_config_prefix . 'website', $value, 'default', 0);

    //email
    if( $this->isLoginIn() ){
      $value = Mage::getStoreConfig($this->login_config_prefix . 'email');
    }else{
      $value = Mage::getStoreConfig($this->contact_billing_config_prefix . 'email');
    }
    $coreModelConfig->saveConfig($this->account_detail_config_prefix . 'email_address', $value, 'default', 0);

    //phone
    $value = Mage::getStoreConfig($this->contact_billing_config_prefix . 'phone');
    $coreModelConfig->saveConfig($this->account_detail_config_prefix . 'phone', $value, 'default', 0);

    //donation
    $value = Mage::getStoreConfig($this->donation_config_prefix . 'donation_percentage');
    $coreModelConfig->saveConfig($this->settings_config_prefix . 'donation', $value, 'default', 0);
  }

  private function formIsValid(){
    if( $this->isLoginIn() ){
      $fields = array(
        //login_config
       array( 'field' => 'email', 'prefix' => $this->login_config_prefix ),
       array( 'field' => 'password', 'prefix' => $this->login_config_prefix ),
      );
    }else{
      $fields = array(
        //signup_config
       array( 'field' => 'businessname', 'prefix' => $this->signup_config_prefix ),
       array( 'field' => 'sales_per_month', 'prefix' => $this->signup_config_prefix ),
       array( 'field' => 'website', 'prefix' => $this->signup_config_prefix ),
       array( 'field' => 'employer_id', 'prefix' => $this->signup_config_prefix ),
       array( 'field' => 'category', 'prefix' => $this->signup_config_prefix ),
       //donation
       array( 'field' => 'donation_percentage', 'prefix' => $this->donation_config_prefix ),
       //contact_billing
       array( 'field' => 'firstname', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'lastname', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'email', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'phone', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'address1', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'city', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'state', 'prefix' => $this->contact_billing_config_prefix ),
       array( 'field' => 'zipcode', 'prefix' => $this->contact_billing_config_prefix ),
       //merchant_agreement
       // array( 'field' => 'agreement', 'prefix' => $this->merchant_agreement_config_prefix ),
      );
    }
    $valid = true;
    foreach($fields as $field){
      $value = Mage::getStoreConfig($field['prefix'] . $field['field']);
      if(empty($value)){
        $valid = false;
      }
    }

    $valid = $this->donationAmountValid($valid);

    if(!$valid){
      Mage::getSingleton('core/session')->addError('Please fill up all the params');
    }
    return $valid;
  }

  private function donationAmountValid($valid){
    $donation = Mage::getStoreConfig($this->donation_config_prefix . 'donation_percentage');
    if( is_numeric($donation) ){
      $int_donation = $donation + 0;
      if(2 <= $int_donation && $int_donation <= 90){
        return $valid;
      }else{
        Mage::getSingleton('core/session')->addError('Donation amount should be between 2 and 90');
        return false;
      }
    }else{
      Mage::getSingleton('core/session')->addError('Donation amount should be an integer');
      return false;
    }
  }

  /*
  * CREATE SPARO WIDGET AND ADD THEME TO SHOPPING CART
  */
  private function createSparoWidgets(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    // create blocks
    $this->createSelectionBlock();
    $this->createConfirmationBlock();

    // getting block to include in widgets
    $selection_block_id = $this->getBlockId('sparo_charity_selection_block');
    $confirmation_block_id = $this->getBlockId('sparo_charity_confirmation_block');

    // get theme widgets need to be attached to
    $theme = Mage::getStoreConfig($this->settings_config_prefix . 'widget_theme'); // for instance default/default

    // create the widgets
    $container_widget = $this->createDefaultWidget('sparo_selection', $selection_block_id, $theme);
    $confirmation_widget = $this->createDefaultWidget('sparo_confirmation', $confirmation_block_id, $theme);

    // store in config that widgets have been created
    $coreModelConfig = new Mage_Core_Model_Config();
    $coreModelConfig->saveConfig($this->settings_config_prefix . 'widget_theme_setup', true, 'default', 0);
  }

  /*
  * CREATE WIDGET USING DEFAULT DATA USE FOR SPARO PLUGIN
  */
  private function createDefaultWidget($title, $block_id, $theme){
    $widgetParameters = array(
      'block_id' => $block_id,
      'template' => 'cms/widget/static_block/default.phtml'
    );
    return $this->createWidget(array(
        'type' => 'cms/widget_block',
        'package_theme' => $theme, // has to match the concrete theme containing the template
        'title' => $title,
        'store_ids' => 0, // or comma separated list of ids
        'widget_parameters' => serialize($widgetParameters),
      )
    );
  }

  /*
  * CREATE WIDGET THROUGH MAGENTO API
  */
  private function createWidget($data){
    $instance = Mage::getModel('widget/widget_instance');
    $instance->setData($data);
    $instance->save();
    return $instance;
  }

  /*
  * RETRIEVE BLOCK ID BY IDENTIFIER
  */
  private function getBlockId($identifier){
    $block_id = false;
    $blocks = Mage::getModel('cms/block')
      ->getCollection()
      ->addFieldToFilter('identifier', $identifier);
    foreach($blocks as $b){
      $block_id = $b->getId();
    }
    return $block_id;
  }

  /*
  * CREATE SELECTION BLOCK
  */
  private function createSelectionBlock(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $block_name = 'sparo_charity_selection_block';
    if( !$this->getBlockId($block_name) ){

      $content = <<<HTML
      <div style="display:{{config path="sparoconfig/sparoconfig_settings/sparo_activated"}}" id="sparo-mount"></div>
      <script type="text/javascript" id="SPARO-lib" data-transaction-type="{{config path="sparoconfig/sparoconfig_settings/sparo_productionmode"}}" data-merchant="{{config path="sparoconfig/sparoconfig_account_detail/sparo_merchantid"}}" src="https://scripts.sparo.com/sparo.min.js"></script>
HTML;

      $scriptBlock = array(
        'title' => 'Sparo Charity Selection Block',
        'identifier' => $block_name,
        'content' => $content,
        'is_active' => 1,
        'stores' => array(0),
      );

      Mage::getModel('cms/block')->setData($scriptBlock)->save();

    }
  }

  /*
  * CREATE CONFIRMATION BLOCK
  */
  private function createConfirmationBlock(){
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $block_name = 'sparo_charity_confirmation_block';
    if( !$this->getBlockId($block_name) ){

      $content = <<<HTML
      <div style="display:{{config path="sparoconfig/sparoconfig_settings/sparo_activated"}}" id="sparo-mount"></div>
      <script id="SPARO-lib" data-thank-you="true" data-size="large" data-merchant="{{config path="sparoconfig/sparoconfig_account_detail/sparo_merchantid"}}" src="https://scripts.sparo.com/sparo.min.js"></script>
HTML;

      $scriptBlock = array(
        'title' => 'Sparo Charity Confirmation Block',
        'identifier' => $block_name,
        'content' => $content,
        'is_active' => 1,
        'stores' => array(0),
      );

      Mage::getModel('cms/block')->setData($scriptBlock)->save();

    }
  }
}
