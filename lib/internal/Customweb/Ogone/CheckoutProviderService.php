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

//require_once 'Customweb/Ogone/MasterPass/Checkout.php';
//require_once 'Customweb/Ogone/Container.php';
//require_once 'Customweb/Payment/ExternalCheckout/IProviderService.php';



/**
 * MasterPass checkout object.
 *
 * @author Thomas Hunziker
 *
 */
class Customweb_Ogone_CheckoutService implements Customweb_Payment_ExternalCheckout_IProviderService {

	private $masterPassCheckout;
	
	/**
	 * @var Customweb_Ogone_Container
	 */
	private $container;
	
	public function __construct(Customweb_DependencyInjection_IContainer $container) {
		$this->masterPassCheckout = new Customweb_Ogone_MasterPass_Checkout();
		$this->container = new Customweb_Ogone_Container($container);
	}
	
	public function getCheckouts(Customweb_Payment_ExternalCheckout_IContext $context) {
		return array(
			$this->masterPassCheckout,
		);
	}

	public function getWidgetHtml(Customweb_Payment_ExternalCheckout_ICheckout $checkout, Customweb_Payment_ExternalCheckout_IContext $context) {
		
		// TODO: Use a template to generate the button. The button should point to a endpoint which executes the redirection.

		return '';
	}

	public function createTransaction(Customweb_Payment_Authorization_ITransactionContext $transactionContext, Customweb_Payment_ExternalCheckout_IContext $context) {
		
		// TODO: Implement the transaction creation.
		
		throw new Exception("Not yet implemented.");
	}

}