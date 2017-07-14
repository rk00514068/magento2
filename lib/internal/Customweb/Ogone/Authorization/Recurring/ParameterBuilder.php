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
//require_once 'Customweb/Ogone/Authorization/AbstractAuthorizationParameterBuilder.php';


class Customweb_Ogone_Authorization_Recurring_ParameterBuilder extends Customweb_Ogone_Authorization_AbstractAuthorizationParameterBuilder
{
	
	public function __construct($transaction, Customweb_DependencyInjection_IContainer $container) {
		parent::__construct($transaction, $container, array());
	}
	
	public function buildParameters() {
		
		$parameters = array_merge(
			$this->getAmountParameter($this->getMaintenanceAmount()),
			$this->getCurrencyParameter(),
			$this->getAuthParameters(),
			$this->getPspParameter(),
			$this->getOrderIdParameter(),
			$this->getOrderDescriptionParameter(),
			$this->getCustomerParameters(),
			$this->getOperationParameter(),
			$this->getPaymentMethodParameters()
		);
		
		// Add ECI (we support currently only regular recurring, no moto recurring)
		$parameters['ECI'] = Customweb_Ogone_IAdapter::ECI_REGULAR_RECURRING;
		
		if ($this->getInitialTransaction()->getAliasIdentifier() === NULL) {
			throw new Exception("The given transaction has no alias associated and therefore it cannot be used for a recurring authorisation.");
		}
		
		// Add recurring parameter
		$parameters['ALIAS'] = $this->getInitialTransaction()->getAliasIdentifier();
	
		$this->addShaSignToParameters($parameters);
		return $parameters;
	}
	
	/**
	 * @return Customweb_Ogone_Authorization_Transaction
	 */
	protected function getInitialTransaction() {
		return $this->getTransaction()->getTransactionContext()->getInitialTransaction();
	}
	
	
	
}