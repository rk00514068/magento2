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

//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Util/Currency.php';



abstract class Customweb_Ogone_ExternalCheckout_MasterPass_AbstractRequestParameterBuilder {
	
	/**
	 * @var Customweb_Ogone_Container
	 */
	private $container;
	
	/**
	 * @var Customweb_Payment_ExternalCheckout_IContext
	 */
	private $context;
	
	public function __construct(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Ogone_Container $container) {
		$this->container = $container;
		$this->context = $context;	
	}
	
	/**
	 * @return array
	 */
	public function build() {
		
		$parameters = array(
			'PSPID' => $this->getConfiguration()->getActivePspId(),
			'ORDERID' => 'mp_c_' . $this->getContext()->getContextId(),
			'AMOUNT' => Customweb_Util_Currency::formatAmount($this->getContext()->getOrderAmountInDecimals(), $this->getContext()->getCurrencyCode(), '', ''),
			'CURRENCY' => $this->getContext()->getCurrencyCode(),
			'PSWD' => $this->getConfiguration()->getApiPassword(),
			'USERID' => $this->getConfiguration()->getApiUserId(),
			'PM' => 'MasterPass',
			'BRAND' => 'MasterPass',
		);
		
		$parameters = array_merge($parameters, $this->buildInnerParamters());
		
		$parameters['SHASIGN'] = Customweb_Ogone_Util::calculateHash($parameters, 'in', $this->getConfiguration());
		
		return $parameters;
	}
	
	abstract protected function buildInnerParamters();
	
	protected function getConfiguration() {
		return $this->container->getConfiguration();
	}
	
	/**
	 * @return Customweb_Ogone_Container
	 */
	protected function getContainer() {
		return $this->container;
	}
	
	/**
	 * @return Customweb_Payment_ExternalCheckout_IContext
	 */
	protected function getContext() {
		return $this->context;
	}
	
	
}
	