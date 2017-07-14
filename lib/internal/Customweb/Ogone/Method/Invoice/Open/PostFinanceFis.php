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

//require_once 'Customweb/Util/Address.php';
//require_once 'Customweb/Ogone/Method/Invoice/Open/Abstract.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Ogone/Method/Invoice/Open/PostFinanceFis/LineItemBuilder.php';


/**
 * 
 * @author Thomas Hunziker
 * @Method(paymentMethods={'OpenInvoice'}, processors={'PostFinanceFIS'})
 */
class Customweb_Ogone_Method_Invoice_Open_PostFinanceFis extends Customweb_Ogone_Method_Invoice_Open_Abstract {
	
	public function getSupportedCountries() {
		return array(
			'CH',
			'LI',
		);
	}
	
	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext) {
		$elements = parent::getFormFields($orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext);
		return array_merge(
			$elements,
			$this->getBirthdayElements($orderContext, $failedTransaction),
			$this->getGenderElements($orderContext, $failedTransaction)
		);
	}
	
	public function getAuthorizationParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData, $authorizationMethod) {
		$parameters = parent::getAuthorizationParameters($transaction, $formData, $authorizationMethod);
		
		$builder = new Customweb_Ogone_Method_Invoice_Open_PostFinanceFis_LineItemBuilder($transaction->getTransactionContext()->getOrderContext());
		$lineItemParameters = $builder->build();
	
		return array_merge(
				$parameters,
				$this->getShippingBirthdateParameter($transaction, $formData),
				$this->getCustomerIdParameter($transaction),
				$this->getCustomerGenderParameter($transaction, $formData),
				$this->getBillingAddressParameters($transaction),
				$this->getShihippinAddressParameters($transaction),
				$lineItemParameters
		);
	}
	
	
	protected function getBillingAddressParameters(Customweb_Ogone_Authorization_Transaction $transaction) {
		$parameters = array();
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$splits = Customweb_Util_Address::splitStreet($orderContext->getBillingStreet(), $orderContext->getBillingCountryIsoCode(), $orderContext->getBillingPostCode());
	
		$parameters['ECOM_BILLTO_POSTAL_NAME_FIRST'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingFirstName(), 0, 50);
		$parameters['ECOM_BILLTO_POSTAL_NAME_LAST'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingLastName(), 0, 50);
		$parameters['OWNERADDRESS'] = Customweb_Ogone_Util::substrUtf8($splits['street'], 0, 35);
		//		$parameters['OWNERADDRESS2'] = 'not present';
		$parameters['ECOM_BILLTO_POSTAL_STREET_NUMBER'] = Customweb_Ogone_Util::substrUtf8($splits['street-number'], 0, 10);
		$parameters['OWNERZIP'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingPostCode(), 0, 10);
		$parameters['OWNERTOWN'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingCity(), 0, 25);
		$parameters['OWNERCTY'] = $orderContext->getBillingCountryIsoCode();
		$parameters['EMAIL'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingEMailAddress(), 0, 50);
		
		// We remove the phone numbers, because the format matters. However it is not defined. They are not required.
		$phoneNumber = $orderContext->getBillingPhoneNumber();
		if (!empty($phoneNumber)) {
// 			$parameters['OWNERTELNO'] = Customweb_Ogone_Util::substrUtf8($phoneNumber, 0, 30);
		}
		$mobile = $orderContext->getBillingMobilePhoneNumber();
		if (!empty($mobile)) {
// 			$parameters['OWNERTELNO2'] = Customweb_Ogone_Util::substrUtf8($orderContext->getBillingMobilePhoneNumber(), 0, 30);
		}
	
		if ($orderContext->getBillingGender() == 'male') {
			$parameters['ECOM_CONSUMER_GENDER'] = 'M';
		}
		if ($orderContext->getBillingGender() == 'female') {
			$parameters['ECOM_CONSUMER_GENDER'] = 'F';
		}
	
		return $parameters;
	}
	
	protected function getShihippinAddressParameters(Customweb_Ogone_Authorization_Transaction $transaction) {
		$parameters = array();
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$splits = Customweb_Util_Address::splitStreet($orderContext->getShippingStreet(), $orderContext->getShippingCountryIsoCode(), $orderContext->getShippingPostCode());
	
		$parameters['ECOM_SHIPTO_POSTAL_NAME_FIRST'] = Customweb_Ogone_Util::substrUtf8($orderContext->getShippingFirstName(), 0, 50);
		$parameters['ECOM_SHIPTO_POSTAL_NAME_LAST'] = Customweb_Ogone_Util::substrUtf8($orderContext->getShippingLastName(), 0, 50);
		$parameters['ECOM_SHIPTO_POSTAL_STREET_LINE1'] = Customweb_Ogone_Util::substrUtf8($splits['street'], 0, 35);
		// 		$parameters['ECOM_SHIPTO_POSTAL_STREET_LINE2'] = '';
		$parameters['ECOM_SHIPTO_POSTAL_STREET_NUMBER'] = Customweb_Ogone_Util::substrUtf8($splits['street-number'], 0, 10);
		$parameters['ECOM_SHIPTO_POSTAL_POSTALCODE'] = Customweb_Ogone_Util::substrUtf8($orderContext->getShippingPostCode(), 0, 10);
		$parameters['ECOM_SHIPTO_POSTAL_CITY'] = Customweb_Ogone_Util::substrUtf8($orderContext->getShippingCity(), 0, 25);
		$parameters['ECOM_SHIPTO_POSTAL_COUNTRYCODE'] = $orderContext->getShippingCountryIsoCode();
	
		$shippingCompany = $orderContext->getShippingCompanyName();
		if (!empty($shippingCompany)) {
			$parameters['ECOM_SHIPTO_COMPANY'] = Customweb_Ogone_Util::substrUtf8($shippingCompany, 0, 50);
		}
	
		return $parameters;
	}
	
}

