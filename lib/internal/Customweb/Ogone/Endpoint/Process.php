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

//require_once 'Customweb/Payment/Endpoint/Controller/Abstract.php';
//require_once 'Customweb/Payment/Endpoint/Annotation/ExtractionMethod.php';
//require_once 'Customweb/Core/Http/Response.php';
//require_once 'Customweb/Ogone/Util.php';



/**
 *
 * @author Thomas Hunziker
 * @Controller("process")
 *
 */
class Customweb_Ogone_Endpoint_Process extends Customweb_Payment_Endpoint_Controller_Abstract {

	/**
	 *
	 * @param Customweb_Core_Http_IRequest $request @ExtractionMethod
	 */
	public function getTransactionIdentifier(Customweb_Core_Http_IRequest $request){
		$parameters = $request->getParameters();
		
		if (isset($parameters['cwTransId'])) {
			return array(
				'id' => $parameters['cwTransId'],
				'key' => Customweb_Payment_Endpoint_Annotation_ExtractionMethod::EXTERNAL_TRANSACTION_ID_KEY
			);
		}
		if (isset($parameters['cw_transaction_id'])) {
			return array(
				'id' => $parameters['cw_transaction_id'],
				'key' => Customweb_Payment_Endpoint_Annotation_ExtractionMethod::EXTERNAL_TRANSACTION_ID_KEY 
			);
		}
		if (isset($parameters['PAYID'])) {
			return array(
				'id' => $parameters['PAYID'],
				'key' => Customweb_Payment_Endpoint_Annotation_ExtractionMethod::PAYMENT_ID_KEY 
			);
		}
		
		throw new Exception("No transaction identifier present in the request.");
	}

	/**
	 * @Action("update")
	 */
	public function update(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		if (! $transaction->isAuthorized() && ! $transaction->isAuthorizationFailed()) {
			return $this->process($transaction, $request);
		}
		if ($transaction->getStatusAfterReceivingUpdate() != 'pending') {
			//We already handled an update successfully, or it's a update we do not expect/handle
			return new Customweb_Core_Http_Response();
		}
		$responseParameters = $request->getParameters();
		$parameters = $transaction->getAuthorizationParameters();
		$config = $this->getContainer()->getBean('Customweb_Ogone_Configuration');
		
		$hash = Customweb_Ogone_Util::calculateHash($responseParameters, 'OUT', $config);
		
		if (isset($responseParameters['SHASIGN']) && $responseParameters['SHASIGN'] == $hash) {
			$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
			$adapter->processNewStatus($transaction, $responseParameters, $parameters['INITIALSTATUS']);
		}
		return new Customweb_Core_Http_Response();
	}

	/**
	 *
	 * @Action("index")
	 */
	public function process(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request) {
		
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		/* @var $adapter Customweb_Ogone_AbstractAdapter */
		$pm = $adapter->getPaymentMethodByTransaction($transaction);
		$parameters = $request->getParameters();
		return $pm->processAuthorization($adapter, $transaction, $parameters);
		
	}
	
	/**
	 * This is the regular completion of an Amazon Transaction.
	 * 
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param Customweb_Core_Http_IRequest $request
	 * 
	 * @Action("amco")
	 */
	public function handleAmazoneStepTwo(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		$parameters = $request->getParsedBody();
		return $adapter->processAuthorization($transaction, $parameters);
	}
}