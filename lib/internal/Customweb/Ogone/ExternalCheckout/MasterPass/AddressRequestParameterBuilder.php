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

//require_once 'Customweb/Ogone/ExternalCheckout/MasterPass/AbstractRequestParameterBuilder.php';



class Customweb_Ogone_ExternalCheckout_MasterPass_AddressRequestParameterBuilder extends Customweb_Ogone_ExternalCheckout_MasterPass_AbstractRequestParameterBuilder{
	
	private $data;
	
	public function __construct(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Ogone_Container $container, $data) {
		parent::__construct($context, $container);
		$this->data = $data;
	}
	
	protected function buildInnerParamters() {
		$parameters = array();

		if (empty($this->data['oauth_token'])) {
			throw new Exception("Parameter 'oauth_token' is empty.");
		}

		if (empty($this->data['oauth_verifier'])) {
			throw new Exception("Parameter 'TXVERIFIER' is empty.");
		}

		if (empty($this->data['checkout_resource_url'])) {
			throw new Exception("Parameter 'checkout_resource_url' is empty.");
		}
		
		$parameters['TXTOKEN'] = $this->data['oauth_token'];
		$parameters['TXVERIFIER'] = $this->data['oauth_verifier'];
		$parameters['TXURL'] = $this->data['checkout_resource_url'];
		
		return $parameters;
	}
	
}
	