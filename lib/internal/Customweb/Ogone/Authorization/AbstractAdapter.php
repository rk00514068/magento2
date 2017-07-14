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

//require_once 'Customweb/Ogone/Method/DirectDebit/Server/Abstract.php';
//require_once 'Customweb/Core/Http/Response.php';
//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';
//require_once 'Customweb/Ogone/Authorization/Transaction.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Ogone/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Ogone/Authorization/Hidden/AuthorizationParameterBuilder.php';


abstract class Customweb_Ogone_Authorization_AbstractAdapter extends Customweb_Ogone_AbstractAdapter {

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext) {
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName());
		$paymentMethod->preValidate($orderContext, $paymentContext);
	}
	
	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData) {
		
	}
	
	public function isDeferredCapturingSupported(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext) {
		return $orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing');
	}
	
	protected function handleFailedTransaction(Customweb_Ogone_Authorization_Transaction $transaction, $failedTransaction) {
		if ($failedTransaction !== null && $failedTransaction instanceof Customweb_Ogone_Authorization_Transaction) {
			$transaction->addPreviousFailedTransactionId($failedTransaction->getExternalTransactionId());
		}
	}
	
	
	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext) {
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName());
		return $paymentMethod->isAuthorizationMethodSupported($this->getAuthorizationMethodName());
	}
	
	public function authorize(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {		
		// In case the authorization failed, we stop processing here
		if ($transaction->isAuthorizationFailed()) {
			return;
		}
	
		// In case the transaction is authorized, we do not have to do anything here.         	 	 	  	 	  	 
		if ($transaction->isAuthorized()) {
			return;
		}
	
		// In case we have authorized the transaction at the remote side, but we have not already processed the
		// 3D response, we have to do it now
		if ($transaction->is3dRedirectionRequired()) {
			$parameters = array_change_key_case($parameters, CASE_UPPER);
			if (!isset($parameters['SHASIGN'])) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The request does not contain a 'SHASIGN' parameter. The cause is that the callback in the background is not executed."));
			}
			else if ($this->validateResponse($parameters)) {
				$this->setTransactionAuthorizationState($transaction, $parameters);
			}
			else {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The SHA signature of the 3D Secure callback was not valid."));
			}
		}
	
		// In all other cases we have to send the authorization request to the remote side
		else {
			try {
				$builder = new Customweb_Ogone_Authorization_Hidden_AuthorizationParameterBuilder($transaction, $this->getContainer(), $parameters);
				$parameters = $builder->buildParameters();
				$response = $this->sendDirectRequest($this->getDirectOrderUrl(), $parameters);
				// In any case dont save the CVC         	 	 	  	 	  	 
				unset($parameters['CVC']);
				if($this->getPaymentMethodByTransaction($transaction) instanceof  Customweb_Ogone_Method_DirectDebit_Server_Abstract && isset($parameters['CARDNO'])){
					$parameters['CARDNO'] = Customweb_Ogone_Util::maskIban($parameters['CARDNO']);
				}
				$transaction->setDirectLinkCreationParameters($parameters);
				$transaction->appendAuthorizationParameters($response);
				$transaction->appendAuthorizationParameters($parameters);
				
				// Check whether a 3D secure redirection is required or not.
				if (!$transaction->is3dRedirectionRequired()) {
					$this->setTransactionAuthorizationState($transaction, $response);
				}
			}
			catch(Exception $e) {
				$transaction->setAuthorizationFailed($e->getMessage());
			}
		}
	}
	
	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction) {
	
		if ($transaction->isAuthorizationFailed()) {
			if (Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME == $transaction->getAuthorizationMethod()) {
				return Customweb_Core_Http_Response::redirect($transaction->getBackendFailedUrl());
			}
			else {
				return Customweb_Core_Http_Response::redirect($transaction->getFailedUrl());
			}
		}
	
		if ($transaction->isAuthorized()) {
			if (Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME == $transaction->getAuthorizationMethod()) {
				return Customweb_Core_Http_Response::redirect($transaction->getBackendSuccessUrl());
			}
			else {
				return Customweb_Core_Http_Response::redirect($transaction->getSuccessUrl());
			}
		}
	
		// Handle 3D secure case
		if (!$transaction->isAuthorized()) {
			if ($transaction->is3dRedirectionRequired()) {
				$parameters = $transaction->getAuthorizationParameters();
				return Customweb_Core_Http_Response::_(base64_decode($parameters['HTML_ANSWER']));
			}
		}
	
		return Customweb_Core_Http_Response::_("The transaction is in a bad state.");
	}
	

}