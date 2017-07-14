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

//require_once 'Customweb/Ogone/MaintenanceParameterBuilder.php';


class Customweb_Ogone_Authorization_Server_ParameterBuilder extends Customweb_Ogone_MaintenanceParameterBuilder {
	
	private $formData = array();
	
	public function __construct($transaction, Customweb_DependencyInjection_IContainer $container, $formData) {
		parent::__construct($transaction, $container);
		$this->formData = $formData;
	}
	
	public function buildParameters() {
		
		$parameters = array_merge(
				$this->getAmountParameter($this->getMaintenanceAmount()),
				$this->getCurrencyParameter(),
				$this->getAuthParameters(),
				$this->getPspParameter(),
				$this->getOrderIdParameter(),
				$this->getOrderDescriptionParameter(),
				$this->getOrigParameter(),
				$this->getCustomerParameters(),
				$this->getAliasManagerParameters(),
				$this->get3DSecureParameters(),
				$this->getReactionUrlParameters(),
				$this->getParamPlusParameters(),
				$this->getOperationParameter(),
				$this->getPaymentMethod()->getAuthorizationParameters(
						$this->getTransaction(),
						$this->formData,
						$this->getTransaction()->getAuthorizationMethod()
				)
		);
		
		$this->addShaSignToParameters($parameters);
	
		return $parameters;
	}
	
	protected function getOperationParameter() {
		return $this->getCapturingModeParameter();
	}
	
	protected function getReactionUrlParameters() {
		$url = $this->getEndpointAdapter()->getUrl('process', 'index', array('cw_transaction_id' => $this->getTransaction()->getExternalTransactionId()));
		return array(
			'ACCEPTURL' => $url,
			'DECLINEURL' => $url,
			'EXCEPTIONURL' => $url
		);
	}
	
	protected function getMaintenanceAmount() {
		return $this->getTransaction()->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals();
	}
}