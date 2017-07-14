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
//require_once 'Customweb/Core/Url.php';



class Customweb_Ogone_ExternalCheckout_MasterPass_RedirectionRequestParameterBuilder extends Customweb_Ogone_ExternalCheckout_MasterPass_AbstractRequestParameterBuilder{
	
	private $token;
	
	public function __construct(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Ogone_Container $container, $token) {
		parent::__construct($context, $container);
		$this->token = $token;
	}
	
	protected function buildInnerParamters() {
		$feedbackUrl = new Customweb_Core_Url($this->getContainer()->getEndpointAdapter()
				->getUrl(
						'masterpass',
						'update-context',
						array('context-id' => $this->getContext()->getContextId(), 'token' => $this->token)
				));
		return array(
			'ACCEPTURL' => $feedbackUrl->toString(),
			'TXSHIPPING' => '1',
		);
	}
	
}
	