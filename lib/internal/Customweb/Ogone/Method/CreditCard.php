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

//require_once 'Customweb/Ogone/Authorization/Hidden/Adapter.php';
//require_once 'Customweb/Ogone/Method/DefaultMethod.php';
//require_once 'Customweb/Payment/Authorization/Method/CreditCard/ElementBuilder.php';
//require_once 'Customweb/Payment/Util.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Ogone/Authorization/Server/Adapter.php';


/**
 * 
 * @author Thomas Hunziker
 * @Method(paymentMethods={'CreditCard', 'Diners', 'Mastercard', 'Cofinoga', 'Visa', 'AmericanExpress', 'Maestro', 'Maestrouk', 'Jcb', 'Cartebleu', 'Aurore', 'Solo', 'BCMC', 'UATP', 'DiscoverCard'})
 */
class Customweb_Ogone_Method_CreditCard extends Customweb_Ogone_Method_DefaultMethod {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext) {
		$elements = array();
		
		/* @var $aliasTransaction Customweb_Ogone_Authorization_Transaction */

		if (Customweb_Ogone_Authorization_Hidden_Adapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod ||
				Customweb_Ogone_Authorization_Server_Adapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod) {
			
			$formBuilder = new Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder();
			
			// Set field names
			$formBuilder
				->setCardHolderFieldName('CN')
				->setCardNumberFieldName('CARDNO')
				->setCvcFieldName('CVC')
				->setExpiryMonthFieldName('ECOM_CARDINFO_EXPDATE_MONTH')
				->setExpiryYearFieldName('ECOM_CARDINFO_EXPDATE_YEAR')
				->setExpiryYearNumberOfDigits(2);
			
			
			// Handle brand selection
			if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
				$formBuilder
					->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), $this->getPaymentMethodConfigurationValue('credit_card_brands'), 'brand')
					->setAutoBrandSelectionActive(true);
			}
			else {
				$formBuilder
					->setFixedBrand(true)
					->setSelectedBrand($this->getPaymentMethodName())
					->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), $this->getPaymentMethodName(), 'brand')
					;
			}
			
			// Set context values
			$formBuilder->setCardHolderName($orderContext->getBillingFirstName() . ' ' . $orderContext->getBillingLastName());
			if($aliasTransaction !== null && $aliasTransaction !== 'new'){
				
				$params = $aliasTransaction->getAuthorizationParameters();
				
				$params = $aliasTransaction->getDirectLinkCreationParameters();
				$aliasCreationResponse = $aliasTransaction->getAliasCreationResponse();
				if (is_array($aliasCreationResponse)) {
					$params = $aliasCreationResponse;
				}
				
				if (isset($params['ECOM_CARDINFO_EXPDATE_MONTH'])) {
					$formBuilder->setSelectedExpiryMonth($params['ECOM_CARDINFO_EXPDATE_MONTH']);
				}
				
				if (isset($params['ECOM_CARDINFO_EXPDATE_YEAR'])) {
					$formBuilder->setSelectedExpiryYear($params['ECOM_CARDINFO_EXPDATE_YEAR']);
				}
				
				if (isset($params['ED'])) {
					$dates = Customweb_Payment_Util::extractExpiryDate($params['ED']);
					$formBuilder->setSelectedExpiryMonth($dates['month']);
					$formBuilder->setSelectedExpiryYear($dates['year']);
				}
				
				if (!empty($params['CN'])) {
					$formBuilder->setCardHolderName($params['CN']);
				}
				
				if (!empty($params['CARDNO'])) {
					$formBuilder->setMaskedCreditCardNumber($params['CARDNO']);
				}
				
				$aliasTransaction->getAuthorizationMethod();
				
				if (isset($params['BRAND']) && !empty($params['BRAND'])) {
					$brand = $formBuilder->getCardHandler()->mapExternalBrandNameToBrandKey($params['BRAND']);
					$formBuilder->setSelectedBrand($brand);
				}
				
			}
			
			
			if ($failedTransaction !== null) {
				$requestParameters = $failedTransaction->getDirectLinkCreationParameters();
				$aliasCreationResponse = $failedTransaction->getAliasCreationResponse();
				
				$errorCode = $this->getErrorCode($failedTransaction);
				if ($errorCode !== null) {
					$formBuilder
					->setExpiryElementErrorMessage($this->getExpiryErrorMessage($errorCode))
					->setCardHolderElementErrorMessage($this->getCardHolderErrorMessage($errorCode))
					->setCardNumberElementErrorMessage($this->getCardNumberErrorMessage($errorCode))
					->setCvcElementErrorMessage($this->getCVCErrorMessage($errorCode))
					;
				}
			
				// Override the request object with alias response, to fill the form with the response.
				if (is_array($aliasCreationResponse)) {
					$requestParameters = $aliasCreationResponse;
				}
			
				if (isset($requestParameters['CN'])) {
					$formBuilder->setCardHolderName($requestParameters['CN']);
				}
			
				if (isset($requestParameters['ECOM_CARDINFO_EXPDATE_MONTH'])) {
					$formBuilder->setSelectedExpiryMonth($requestParameters['ECOM_CARDINFO_EXPDATE_MONTH']);
				}
			
				if (isset($requestParameters['ECOM_CARDINFO_EXPDATE_YEAR'])) {
					$formBuilder->setSelectedExpiryYear($requestParameters['ECOM_CARDINFO_EXPDATE_YEAR']);
				}
			
				if (isset($requestParameters['ED'])) {
					$dates = Customweb_Payment_Util::extractExpiryDate($requestParameters['ED']);
					$formBuilder->setSelectedExpiryMonth($dates['month']);
					$formBuilder->setSelectedExpiryYear($dates['year']);
				}
			}
				
			return $formBuilder->build();
		}

		return $elements;
	}
	
	public function getAuthorizationParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData, $authorizationMethod) {
		$parameters = parent::getAuthorizationParameters($transaction, $formData, $authorizationMethod);

		if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
			$listing = $this->getPaymentMethodConfigurationValue('payment_method_listing');
			if ($listing == '0' || $listing == '1' || $listing == '2') {
				$parameters['PMLISTTYPE'] = $listing;
			}
		}
		
		if (Customweb_Ogone_Authorization_Server_Adapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod) {
			if (empty($formData['CN'])) {
				throw new Exception(Customweb_I18n_Translation::__("You have to enter the card holder."));
			}
			
			if (empty($formData['CARDNO'])) {
				throw new Exception(Customweb_I18n_Translation::__("You have to enter the a card number."));
			}
			
			if (empty($formData['ECOM_CARDINFO_EXPDATE_MONTH'])) {
				throw new Exception(Customweb_I18n_Translation::__("You have to enter the month of the card expiry."));
			}
			
			if (empty($formData['ECOM_CARDINFO_EXPDATE_YEAR'])) {
				throw new Exception(Customweb_I18n_Translation::__("You have to enter the year of the card expiry."));
			}
			
			if (empty($formData['CVC'])) {
				throw new Exception(Customweb_I18n_Translation::__("You have to enter the CVC code from the back of your credti card."));
			}
			
			$parameters['CN'] = strip_tags($formData['CN']);
			$parameters['CARDNO'] = strip_tags($formData['CARDNO']);
			$parameters['ED'] = strip_tags($formData['ECOM_CARDINFO_EXPDATE_MONTH']) . strip_tags($formData['ECOM_CARDINFO_EXPDATE_YEAR']);
			$parameters['CVC'] = strip_tags($formData['CVC']);
		}
		
		return $parameters;
	}
	
	private function getErrorCode(Customweb_Ogone_Authorization_Transaction $failedTransaction) {
		$authorizationParameters = $failedTransaction->getAuthorizationParameters();
		$aliasCreationResponse = $failedTransaction->getAliasCreationResponse();
			
		$errorCode = null;
		if (isset($authorizationParameters['NCERROR'])) {
			$errorCode = $authorizationParameters['NCERROR'];
		}
			
		if (isset($aliasCreationResponse['NCERROR'])) {
			$errorCode = $aliasCreationResponse['NCERROR'];
		}
			
		if (isset($aliasCreationResponse['NCERRORCN']) && $aliasCreationResponse['NCERRORCN'] != 0) {
			$errorCode = $aliasCreationResponse['NCERRORCN'];
		}
			
		if (isset($aliasCreationResponse['NCERRORCARDNO']) && $aliasCreationResponse['NCERRORCARDNO'] != 0) {
			$errorCode = $aliasCreationResponse['NCERRORCARDNO'];
		}
		if (isset($aliasCreationResponse['NCERRORCVC']) && $aliasCreationResponse['NCERRORCVC'] != 0) {
			$errorCode = $aliasCreationResponse['NCERRORCVC'];
		}
		if (isset($aliasCreationResponse['NCERRORED']) && $aliasCreationResponse['NCERRORED'] != 0) {
			$errorCode = $aliasCreationResponse['NCERRORED'];
		}
		
		return $errorCode;
	}

	
	private function getExpiryErrorMessage($errorCode) {
		if ($errorCode == '30541001' || $errorCode == '30331001' || $errorCode == '31061001' || $errorCode == '50001005') {
			return  Customweb_I18n_Translation::__('The expiry date is invalid.');
		}
	
		if ($errorCode == '30541001' || $errorCode == '30331001') {
			return Customweb_I18n_Translation::__('The credit card is expired.');
		}
	
		if ($errorCode == '50001181') {
			return Customweb_I18n_Translation::__('Expiry date contains non-numeric data.');
		}
	
		if ($errorCode == '50001182') {
			return Customweb_I18n_Translation::__('Invalid expiry month.');
		}
	
		if ($errorCode == '50001183') {
			return Customweb_I18n_Translation::__('Expiry date must be in the future.');
		}
	
		if ($errorCode == '31061001') {
			return Customweb_I18n_Translation::__('Expiry date is in wrong format.');
		}
	
		return null;
	}
	
	private function getCardHolderErrorMessage($errorCode) {
		if ($errorCode == '50001174') {
			return Customweb_I18n_Translation::__('The cardholder name is too long.');
		}
		if ($errorCode == '60001057') {
			return Customweb_I18n_Translation::__('The cardholder name is missing.');
		}
		return null;
	}
	
	private function getCVCErrorMessage($errorCode) {
		if ($errorCode == '50001066') {
			return Customweb_I18n_Translation::__('Invalid CVC format.');
		}
		if ($errorCode == '50001090') {
			return Customweb_I18n_Translation::__('You have to enter a CVC code.');
		}
		if ($errorCode == '50001179') {
			return Customweb_I18n_Translation::__('The CVC code is too long.');
		}
		if ($errorCode == '50001180') {
			return Customweb_I18n_Translation::__('The CVC contains invalid chars.');
		}
	
		return null;
	}
	
	private function getCardNumberErrorMessage($errorCode) {
		if ($errorCode == '0050001036') {
			return Customweb_I18n_Translation::__('Your card number is invalid. May be you choose the wrong credit card type.');
		}
	
		if ($errorCode == '30141001' || $errorCode == '50001054') {
			return Customweb_I18n_Translation::__('Your card number is invalid.');
		}
	
		if ($errorCode == '50001069') {
			return Customweb_I18n_Translation::__('Brand and card number do not match.');
		}
	
		if ($errorCode == '50001176') {
			return Customweb_I18n_Translation::__('The card number is too long.');
		}
	
		if ($errorCode == '50001177') {
			return Customweb_I18n_Translation::__('The card number contains invalid chars.');
		}
	
		if ($errorCode == '50001178') {
			return Customweb_I18n_Translation::__('The card number is too short.');
		}
	
		return null;
	}
	
}