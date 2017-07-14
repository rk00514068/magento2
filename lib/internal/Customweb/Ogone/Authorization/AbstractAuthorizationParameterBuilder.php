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

//require_once 'Customweb/Core/Util/System.php';
//require_once 'Customweb/Core/Http/ContextRequest.php';
//require_once 'Customweb/Ogone/MaintenanceParameterBuilder.php';


abstract class Customweb_Ogone_Authorization_AbstractAuthorizationParameterBuilder extends Customweb_Ogone_MaintenanceParameterBuilder {
	
	protected $formData;
	
	public function __construct(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_DependencyInjection_IContainer $container, array $formData) {
		parent::__construct($transaction, $container);
		$this->formData = $formData;
	}
	
	
	public function buildParameters() {
		
		$this->addShopIdToCustomParameters();
		$parameters = array_merge(
			$this->getAmountParameter($this->getMaintenanceAmount()),
			$this->getCurrencyParameter(),
			$this->getAuthParameters(),
			$this->getPspParameter(),
			$this->getOrderIdParameter(),
			$this->getOrderDescriptionParameter(),
			$this->getOrigParameter(),
			$this->getCustomerParameters(),
			$this->getDeliveryAndInvoicingParameters(),
			$this->getAliasManagerParameters(),
			$this->get3DSecureParameters(),
			$this->getReactionUrlParameters(),
			$this->getParamPlusParameters(),
			$this->getOperationParameter(),
			$this->getTemplateParameter(),
			$this->getPaymentMethodParameters(),
			$this->getECIParameters(),
			$this->getLanguageParameter(),
			$this->getRemoteAddressParameters()
		);
	
		$this->addShaSignToParameters($parameters);
	
		return $parameters;
	}
	
	protected function getPaymentMethodParameters() {
		return $this->getPaymentMethod()->getAuthorizationParameters(
				$this->getTransaction(),
				$this->formData,
				$this->getTransaction()->getAuthorizationMethod()
		);
	}
	
	protected function getRemoteAddressParameters() {
		return array(
			'REMOTE_ADDR' => Customweb_Core_Http_ContextRequest::getClientIPAddressV6(),
// 			'CUSTIP' => Customweb_Core_Util_System::getClientIPAddress(),
		);
	}
	
	protected function getOperationParameter() {
		return $this->getCapturingModeParameter();
	}
	
	protected function getMaintenanceAmount() {
		return $this->getTransaction()->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals();
	}
	
	protected function validateCreditRating(){
		
		
	}
}
