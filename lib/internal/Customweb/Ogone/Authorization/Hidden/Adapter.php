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

//require_once 'Customweb/Payment/Authorization/Hidden/IAdapter.php';
//require_once 'Customweb/Ogone/Authorization/AbstractAdapter.php';
//require_once 'Customweb/Ogone/Authorization/Transaction.php';
//require_once 'Customweb/Ogone/Authorization/Hidden/AliasGatewayParameterBuilder.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Hidden_Adapter extends Customweb_Ogone_Authorization_AbstractAdapter 
	implements Customweb_Payment_Authorization_Hidden_IAdapter {
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	public function getAdapterPriority() {
		return 150;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Hidden_ITransactionContext $transactionContext, $failedTransaction) {
		$transaction = new Customweb_Ogone_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		
		// Keep the same alias transaction id over multiple transactions, to prevent the customer to renter all the data 
		// on multiple tries         	 	 	  	 	  	 
		if ($failedTransaction !== null) {
			$aliasTransactionId = $failedTransaction->getAliasTransactionId();
			if ($aliasTransactionId != null) {
				$transaction->setAliasTransactionId($aliasTransactionId);
			}
			$transaction->setAliasGatewayAlias($failedTransaction->getAliasGatewayAlias());
			
			$rs = $failedTransaction->getAliasCreationResponse();
			$transaction->setAliasCreationResponse($rs);
		}
		
		$this->handleFailedTransaction($transaction, $failedTransaction);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		
		return $transaction;
	}
	
	public function getHiddenFormFields(Customweb_Payment_Authorization_ITransaction $transaction) {
		$builder = new Customweb_Ogone_Authorization_Hidden_AliasGatewayParameterBuilder($transaction, $this->getContainer());
		return $builder->buildParameters();
	}
	
	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction) {
		return $this->getHiddenAuthorizationUrl();
	}
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext) {
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME);
		return $paymentMethod->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, $customerPaymentContext);
	}
	
	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		
		// Check if it is required to check the validation response         	 	 	  	 	  	 
		if (!$transaction->isAuthorizationFailed() && !$transaction->isAuthorized() && !$transaction->is3dRedirectionRequired()) {
			
			$parameters = array_change_key_case($parameters, CASE_UPPER);
			
			$paramsToStore = $parameters;
			
			// If we have already some data for this alias, we merge in the new values.
			if (is_array($transaction->getAliasCreationResponse())) {
				$paramsToStore = $transaction->getAliasCreationResponse();
				foreach ($parameters as $key => $value) {
					if (!empty($value)) {
						$paramsToStore[$key] = $value;
					}
				}
			}
			$transaction->setAliasCreationResponse($paramsToStore);
			$transaction->appendAuthorizationParameters($paramsToStore);
			
			if (isset($parameters['ALIAS'])) {
				$transaction->setAliasGatewayAlias($parameters['ALIAS']);
			}
			
			// Check status first, because the SHA OUT is not set in error case:
			if ($parameters['STATUS'] == '1') {
				$message = Customweb_I18n_Translation::__("Some input was invalid.");
				if ($parameters['NCERROR'] == '50001184') {
					$message = Customweb_I18n_Translation::__("SAH IN signature is wrong.");
				}
				else if ($parameters['NCERROR'] == '5555554') {
					$message = Customweb_I18n_Translation::__("The transaction id is incorrect.");
				}
				else if ($parameters['NCERROR'] == '50001186') {
					$message = Customweb_I18n_Translation::__("Operation is not supported. For this transaction id, an alias already exists.");
				}
				else if ($parameters['NCERROR'] == '50001187') {
					$message = Customweb_I18n_Translation::__("Operation is not allowed.");
				}
				else if ($parameters['NCERROR'] == '50001300') {
					$message = Customweb_I18n_Translation::__("Wrong 'BRAND' was specified.");
				}
				else if ($parameters['NCERROR'] == '50001301') {
					$message = Customweb_I18n_Translation::__("Wrong bank account format.");
				}
				
				$transaction->setAuthorizationFailed($message);
				return $this->finalizeAuthorizationRequest($transaction);
			}
			
			// In some cases CURRENCY and LANUAGE are returned. Both should not be considered during hash calculation and hence
			// we remove them.
			unset($parameters['currency']);
			unset($parameters['CURRENCY']);
			unset($parameters['language']);
			unset($parameters['LANGUAGE']);
			
			// Validate input
			if (!$this->validateResponse($parameters)) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The SHA signatures do not match."));
				return $this->finalizeAuthorizationRequest($transaction);
			}
		}
		
		$this->authorize($transaction, $parameters);
		
		return $this->finalizeAuthorizationRequest($transaction);
	}

}