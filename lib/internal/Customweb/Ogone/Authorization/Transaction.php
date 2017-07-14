<?php 
/**
  * You are allowed to use this API in your web application.
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

//require_once 'Customweb/Ogone/IAdapter.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';
//require_once 'Customweb/Payment/Authorization/DefaultTransaction.php';
//require_once 'Customweb/Util/Url.php';
//require_once 'Customweb/I18n/Translation.php';


class Customweb_Ogone_Authorization_Transaction extends Customweb_Payment_Authorization_DefaultTransaction {
	
	const AUTHORIZATION_STATE_INITIAL = 'initial';
	const AUTHORIZATION_STATE_3DSECURE = '3dsecure';
	
	private $authorizationState = null;
	
	private $aliasIdentifier = null;
	
	private $aliasCreationResponse = null;
	
	private $directLinkCreationParameters = null;
	
	private $aliasTransactionId = null;
	
	private $aliasGatewayAlias = null;
	
	private $orderId = null;
	
	private $previousFailedTransactionIds = array();

	private $statusAfterReceivingUpdate = null;
	
	public function __construct(Customweb_Payment_Authorization_ITransactionContext $transactionContext) {
		parent::__construct($transactionContext);
		
		
		// We set all transaction to updatable, as long they are not processed.
		$this->setUpdatable(true);
		
	}
	
	public function getAliasName() {
		$params = $this->getAuthorizationParameters();
		return $params['ALIAS'];
	}
	
	public function getExternalOrderId() {
		return $this->orderId;		
	}
	
	public function setExternalOrderId($id) {
		$this->orderId = $id;
		return $this;
	}
	
	/**
	 * This method returns the identifier to create new transactions with the credentials
	 * of this transaction.
	 * 
	 * @return string
	 */
	public function getAliasIdentifier() {
		$params = $this->getAuthorizationParameters();
		if (isset($params['ALIAS'])) {
			return $params['ALIAS'];
		}
		else {
			return NULL;
		}
	}
	
	/**
	 * 
	 * @param array $parameters
	 * @return Customweb_Ogone_Authorization_Transaction
	 */
	public function appendAuthorizationParameters(array $parameters) {
		$all = $this->getAuthorizationParameters();
		if (!is_array($all)) {
			$all = array();
		}
		
		foreach (array_change_key_case($parameters, CASE_UPPER) as $key => $value) {
			if(!empty($value)){
				$all[$key] = $value;
			}
			
		}
		
		$this->setAuthorizationParameters($all);
		return $this;
	}
	
	public function getTransactionSpecificLabels() {
		$labels = array();

		$params = $this->getAuthorizationParameters();
		
		if (isset($params['ALIAS'])) {
			$labels['alias_psp'] = array(
				'label' => Customweb_I18n_Translation::__('Alias Token'),
				'value' => $params['ALIAS']
			);
		}
		
		if (isset($params['ACCEPTANCE'])) {
			$labels['acceptance'] = array(
				'label' => Customweb_I18n_Translation::__('Acceptance'),
				'value' => $params['ACCEPTANCE']
			);
		}
		
		if (isset($params['CARDNO'])) {
			$labels['cardnumber'] = array(
				'label' => Customweb_I18n_Translation::__('Card Number'),
				'value' => $params['CARDNO']
			);
		}
		
		if (isset($params['ED'])) {
			$labels['card_expiry'] = array(
				'label' => Customweb_I18n_Translation::__('Card Expiry Date'),
				'value' => substr($params['ED'], 0, 2) . '/' . substr($params['ED'], 2, 4)
			);
		}
		
		if (isset($params['BRAND']) && $params['BRAND'] != '') {
			
			$labels['brand'] = array(
				'label' => Customweb_I18n_Translation::__('Brand'),
				'value' => $params['BRAND'],
			);
		}
		
		if (isset($params['ORDERID'])) {
			$labels['orderid'] = array(
				'label' => Customweb_I18n_Translation::__('Merchant Reference'),
				'value' => $params['ORDERID'],
			);
		}

		if (isset($params['SCORING'])) {
			$labels['scoring'] = array(
				'label' => Customweb_I18n_Translation::__('Risk Scoring'),
				'value' => $params['SCORING']
			);
		}
		
		if (isset($params['SCO_CATEGORY'])) {
			$value = '';
			if ($params['SCO_CATEGORY'] == 'G') {
				$value = Customweb_I18n_Translation::__('Green');
			}
			else if ($params['SCO_CATEGORY'] == 'R') {
				$value = Customweb_I18n_Translation::__('Red');
			}
			else if ($params['SCO_CATEGORY'] == 'O') {
				$value = Customweb_I18n_Translation::__('Orange');
			}
			$labels['scoring_category'] = array(
				'label' => Customweb_I18n_Translation::__('Risk Category'),
				'value' => $value
			);
		}
		
		if ($this->isMoto()) {
			$labels['moto'] = array(
				'label' => Customweb_I18n_Translation::__('Mail Order / Telephone Order (MoTo)'),
				'value' => Customweb_I18n_Translation::__('Yes'),
			);
		}
		
		return $labels;
	}
	
	public function isMoto() {
		return $this->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAuthorizationState() {
		return $this->authorizationState;
	}
	
	public function setAuthorizationState($state) {
		$this->authorizationState = $tstate;
		return $this;
	}
	
	public function getDirectLinkCreationParameters() {
		return $this->directLinkCreationParameters;
	}
	
	public function setDirectLinkCreationParameters($params) {
		$this->directLinkCreationParameters = $params;
		return $this;
	}
	
	public function setAliasTransactionId($transactionId) {
		$this->aliasTransactionId = $transactionId;
		return $this;
	}
	
	public function getAliasTransactionId() {
		return $this->aliasTransactionId;
	}
	
	public function getFailedUrl() {
		return Customweb_Util_Url::appendParameters(
				$this->getTransactionContext()->getFailedUrl(),
				$this->getTransactionContext()->getCustomParameters()
		);
	}
	
	public function getBackendFailedUrl() {
		return Customweb_Util_Url::appendParameters(
				$this->getTransactionContext()->getBackendFailedUrl(),
				$this->getTransactionContext()->getCustomParameters()
		);
	}
	
	public function getSuccessUrl() {
		return Customweb_Util_Url::appendParameters(
				$this->getTransactionContext()->getSuccessUrl(),
				$this->getTransactionContext()->getCustomParameters()
		);
	}
	
	public function getBackendSuccessUrl() {
		return Customweb_Util_Url::appendParameters(
				$this->getTransactionContext()->getBackendSuccessUrl(),
				$this->getTransactionContext()->getCustomParameters()
		);
	}
	
	public function is3dRedirectionRequired() {
		$params = $this->getAuthorizationParameters();
		
		if (!isset($params['STATUS'])) {
			return false;
		}
		
		return $params['STATUS'] == Customweb_Ogone_IAdapter::STATUS_WAITING_FOR_IDENTIFICATION;
	}
	
	public function getAliasCreationResponse() {
		return $this->aliasCreationResponse;
	}
	
	public function setAliasCreationResponse(array $response) {
		$this->aliasCreationResponse = $response;
		return $this;
	}
	
	public function getAliasGatewayAlias() {
		return $this->aliasGatewayAlias;
	}
	
	public function setAliasGatewayAlias($alias) {
		$this->aliasGatewayAlias = $alias;
		return $this;
	}
	
	public function setAuthorizationParameters(array $parameters) {
		return parent::setAuthorizationParameters(array_change_key_case($parameters, CASE_UPPER));
	}

	public function getPreviousFailedTransactionIds(){
		return $this->previousFailedTransactionIds;
	}

	public function setPreviousFailedTransactionIds(array $previousFailedTransactionIds){
		$this->previousFailedTransactionIds = $previousFailedTransactionIds;
		return $this;
	}
	
	public function addPreviousFailedTransactionId($id){
		$this->previousFailedTransactionIds[$id] = $id;
		return $this;
	}
	
	public function setStatusAfterReceivingUpdate($state) {
		$this->statusAfterReceivingUpdate = $state;
	}
	
	public function getStatusAfterReceivingUpdate() {
		return $this->statusAfterReceivingUpdate;
	}
	
	
	protected function getCustomOrderStatusSettingKey($statusKey) {
		$method = $this->getPaymentMethod();
		if($this->getStatusAfterReceivingUpdate() == 'success') {
			if ($method->existsPaymentMethodConfigurationValue('status_success_after_uncertain')) {
				$updateSuccess = $method->getPaymentMethodConfigurationValue('status_success_after_uncertain');
				if ($updateSuccess != 'no_status_change' && $updateSuccess != 'none') {
					$statusKey = 'status_success_after_uncertain';
				}
			}
		}
		else if($this->getStatusAfterReceivingUpdate() == 'refused') {
			if ($method->existsPaymentMethodConfigurationValue('status_refused_after_uncertain')) {
				$updateRefused = $method->getPaymentMethodConfigurationValue('status_refused_after_uncertain');
				if ($updateRefused != 'no_status_change' && $updateRefused != 'none') {
					$statusKey = 'status_refused_after_uncertain';
				}
			}	
		}	
		
		return $statusKey;
	}
	
	/**
	 * Returns the specified authorization parameter or null,
	 * if the key doesn't exist.
	 * 
	 * @param string $key
	 * @return mixed | NULL
	 */
	public function getAuthorizationParameter($key){
		$params = $this->getAuthorizationParameters();
		if($params != null && array_key_exists($key, $params)){
			return $params[$key];
		} else {
			return null;
		}
	}
	
	
}