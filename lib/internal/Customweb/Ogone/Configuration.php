<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2016 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

//require_once 'Customweb/I18n/Translation.php';



/**
 * This class implements all basic configuration interfaces.
 * The conrete class
 * has only to implement the getConfigurationValue() method which provides access
 * to the configuration storage facility.
 *         	 	 	  	 	  	 
 *
 * @author Thomas Hunziker
 * @Bean
 */
class Customweb_Ogone_Configuration {
	
	/**
	 *         	 	 	  	 	  	 
	 * 
	 * @var Customweb_Payment_IConfigurationAdapter
	 */
	private $configurationAdapter = null;

	public function __construct(Customweb_Payment_IConfigurationAdapter $configurationAdapter){
		$this->configurationAdapter = $configurationAdapter;
	}

	public function getConfigurationAdapter(){
		return $this->configurationAdapter;
	}

	public function getConfigurationValue($key){
		return $this->configurationAdapter->getConfigurationValue($key);
	}

	public function isTestMode(){
		if (strtolower($this->getConfigurationValue('operation_mode')) == 'live') {
			return false;
		}
		else {
			return true;
		}
	}

	public function isTransactionUpdateActive(){
		
		if ($this->configurationAdapter->existsConfiguration('transaction_updates') &&
				 $this->configurationAdapter->getConfigurationValue('transaction_updates') == 'active') {
			return true;
		}
		
		return false;
	}

	/**
	 *
	 * @return mixed
	 * @deprecated use instead getActivePspId
	 */
	public function getPspId(){
		return $this->configurationAdapter->getConfigurationValue('pspid');
	}

	/**
	 *
	 * @return mixed
	 * @deprecated use instead getActivePspId
	 */
	public function getTestPspId(){
		return $this->configurationAdapter->getConfigurationValue('test_pspid');
	}

	public function getActivePspId(){
		$pspid = null;
		if ($this->isTestMode()) {
			$pspid = $this->getTestPspId();
		}
		else {
			$pspid = $this->getPspId();
		}
		if (empty($pspid)) {
			throw new Exception(Customweb_I18n_Translation::__("The provided PSPID is empty. Please check the module settings."));
		}
		return $pspid;
	}

	public function getLiveShaPassphraseIn(){
		return $this->configurationAdapter->getConfigurationValue('live_sha_passphrase_in');
	}

	public function getLiveShaPassphraseOut(){
		return $this->configurationAdapter->getConfigurationValue('live_sha_passphrase_out');
	}

	public function getTestShaPassphraseIn(){
		return $this->configurationAdapter->getConfigurationValue('test_sha_passphrase_in');
	}

	public function getTestShaPassphraseOut(){
		return $this->configurationAdapter->getConfigurationValue('test_sha_passphrase_out');
	}

	public function getHashMethod(){
		return $this->configurationAdapter->getConfigurationValue('hash_method');
	}

	public function getOrderIdSchema(){
		return $this->configurationAdapter->getConfigurationValue('order_id_schema');
	}

	public function getOrderDescriptionSchema(){
		return $this->configurationAdapter->getConfigurationValue('order_description_schema');
	}

	public function getAliasUsageMessage($language){
		return $this->configurationAdapter->getConfigurationValue('alias_usage_message', $language);
	}

	public function getApiUserId(){
		$apiUser = $this->configurationAdapter->getConfigurationValue('api_user_id');
		if (empty($apiUser)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to define an API user id in the module settings."));
		}
		return $apiUser;
	}

	public function getApiPassword(){
		$apiPassword = $this->configurationAdapter->getConfigurationValue('api_password');
		if (empty($apiPassword)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to define an API password in the module settings."));
		}
		return $apiPassword;
	}

	public function getShopId(){
		return $this->configurationAdapter->getConfigurationValue('shop_id');
	}

	public function getTemplateUrl(){
		if ($this->configurationAdapter->getConfigurationValue('template') == 'default') {
			return 'default';
		}
		elseif ($this->configurationAdapter->getConfigurationValue('template') == 'custom') {
			return $this->configurationAdapter->getConfigurationValue('template_url');
		}
		elseif ($this->configurationAdapter->getConfigurationValue('template') == 'static') {
			return 'static';
		}
		else {
			return '';
		}
	}

	public function getShaPassphraseIn(){
		if ($this->isTestMode()) {
			return $this->getTestShaPassphraseIn();
		}
		else {
			return $this->getLiveShaPassphraseIn();
		}
	}

	public function getShaPassphraseOut(){
		if ($this->isTestMode()) {
			return $this->getTestShaPassphraseOut();
		}
		else {
			return $this->getLiveShaPassphraseOut();
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getBaseEndPointUrl(){
		if ($this->isTestMode()) {
			return 'https://secure.ogone.com/ncol/test/';
		}
		else {
			return 'https://secure.ogone.com/ncol/prod/';
		}
	}

	public function getTitle($language){
		return $this->getConfigurationAdapter()->getConfigurationValue('title', $language);
	}
}