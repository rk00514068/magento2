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
//require_once 'Customweb/Form/Validator/NotEmpty.php';
//require_once 'Customweb/Form/Control/Select.php';
//require_once 'Customweb/Ogone/Method/Invoice/Open/AfterPay/LineItemBuilder.php';
//require_once 'Customweb/Util/String.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 * 
 * @author Thomas Hunziker
 * @Method(paymentMethods={'OpenInvoice'}, processors={'AfterPay'})
 */
class Customweb_Ogone_Method_Invoice_Open_AfterPay extends Customweb_Ogone_Method_Invoice_Open_Abstract {

	public function getSupportedCountries() {
		return array(
			'NL',
			'DE',
		);
	}
	

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext) {
		$elements = parent::getFormFields($orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext);
		return array_merge(
				$elements,
				$this->getCivilityElements($orderContext, $failedTransaction),
				$this->getPhoneNumberElements($orderContext, $failedTransaction),
				$this->getBirthdayElements($orderContext, $failedTransaction),
				$this->getSalesTaxNumberElements($orderContext, $failedTransaction),
				$this->getCommericalRegisterNumberElements($orderContext, $failedTransaction)
		);
	}
	
	public function getAuthorizationParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData, $authorizationMethod) {
		$parameters = parent::getAuthorizationParameters($transaction, $formData, $authorizationMethod);
	
		$builder = new Customweb_Ogone_Method_Invoice_Open_AfterPay_LineItemBuilder($transaction->getTransactionContext()->getOrderContext());
		$lineItemParameters = $builder->build();
	
		return array_merge(
				$parameters,
				$this->getBillingCivilityParameters($transaction, $formData),
				$this->getBillingAddressParameters($transaction),
				$this->getOwnerPhoneNumberParameters($transaction, $formData),
				$this->getShihippinAddressParameters($transaction),
				$this->getShippingBirthdateParameter($transaction, $formData),
				$this->getCompanyParameters($transaction, $formData),
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
	
	
		return $parameters;
	}
	
	protected function getCompanyParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData) {
		$parameters = array();
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$shippingCompany = $orderContext->getShippingCompanyName();
		if (!empty($shippingCompany)) {
			$parameters['ECOM_SHIPTO_COMPANY'] = Customweb_Ogone_Util::substrUtf8($shippingCompany, 0, 50);
		}
		return array_merge(
			$parameters,
			$this->getCustomerReferenceIdParameters($transaction, $formData),
			$this->getSalesTaxNumberParameters($transaction, $formData)
		);
	}
	
	protected function getOwnerPhoneNumberParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		return array(
			'OWNERTELNO' => Customweb_Util_String::substrUtf8(preg_replace('/[^A-Za-z0-9 ]/', '', $this->getPhoneNumber($transaction, $formData)), 0, 10)
		);
	}
	
	protected function getCivilityElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
	
		$gender = $orderContext->getBillingAddress()->getGender();
		if($gender != "male" && $gender != "female"){
			$options = array(
				'none' => Customweb_I18n_Translation::__('Select your gender'),
				'female' => Customweb_I18n_Translation::__('Female'),
				'male' => Customweb_I18n_Translation::__('Male') 
			);
			$genderControl = new Customweb_Form_Control_Select('civility', $options);
			$genderControl->addValidator(
					new Customweb_Form_Validator_NotEmpty($genderControl, Customweb_I18n_Translation::__("Please select your gender.")));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Gender'), $genderControl, Customweb_I18n_Translation::__('Please select your gender.'));
			$elements[] = $element;
		}
	
		return $elements;
	}
	
	protected function getBillingCivilityParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$gender = $orderContext->getBillingAddress()->getGender();
		if($gender != "male" && $gender != "female"){
			if (empty($formData['civility'])) {
				throw new Exception(Customweb_I18n_Translation::__("You must enter your gender."));
			}
			$gender = $formData['civility'];
		}
		$short = 'M';
		if($gender == 'female') {
			$short ='V';
		}
		return array(
			'CIVILITY' => $short
		);
	}
}

