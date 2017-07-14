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

//require_once 'Customweb/Form/Validator/MaximalLength.php';
//require_once 'Customweb/Form/Validator/NotEmpty.php';
//require_once 'Customweb/Form/Control/Select.php';
//require_once 'Customweb/Util/String.php';
//require_once 'Customweb/Ogone/Method/DefaultMethod.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/Form/ElementFactory.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Form/Control/TextInput.php';
//require_once 'Customweb/Util/Rand.php';



/**
 *
 * @author Thomas Hunziker
 */
class Customweb_Ogone_Method_Invoice_Abstract extends Customweb_Ogone_Method_DefaultMethod {



	protected function getGenderElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		if ($orderContext->getBillingGender() != 'male' && $orderContext->getBillingGender() != 'female') {
			$genders = array(
				'none' => Customweb_I18n_Translation::__('Select your gender'),
				'f' => Customweb_I18n_Translation::__('Female'),
				'm' => Customweb_I18n_Translation::__('Male') 
			);
			$genderControl = new Customweb_Form_Control_Select('customer_gender', $genders);
			$genderControl->addValidator(
					new Customweb_Form_Validator_NotEmpty($genderControl, Customweb_I18n_Translation::__("Please select your gender.")));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Gender'), $genderControl, 
					Customweb_I18n_Translation::__('Please select your gender.'));
			
			$elements[] = $element;
		}
		return $elements;
	}

	protected function getSalutationElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		
		$salutation = $orderContext->getBillingSalutation();
		if (empty($salutation)) {
			$control = new Customweb_Form_Control_TextInput('salutation');
			$control->addValidator(new Customweb_Form_Validator_NotEmpty($control, Customweb_I18n_Translation::__("Please enter your salutation.")));
			$control->addValidator(
					new Customweb_Form_Validator_MaximalLength($control, 
							Customweb_I18n_Translation::__("The salutation can not be longer than 5 chars."), 5));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Salutation'), $control, 
					Customweb_I18n_Translation::__('Please enter here a salutation.'));
			$elements[] = $element;
		}
		
		return $elements;
	}

	protected function getPhoneNumberElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		
		$phoneNumber = $orderContext->getBillingPhoneNumber();
		if (empty($phoneNumber)) {
			$control = new Customweb_Form_Control_TextInput('phone_number');
			$control->addValidator(new Customweb_Form_Validator_NotEmpty($control, Customweb_I18n_Translation::__("Please enter your phone number.")));
			
			$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Phone Number'), $control, 
					Customweb_I18n_Translation::__('Please enter here your phone number.'));
			$elements[] = $element;
		}
		
		return $elements;
	}

	protected function getBirthdayElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		$birthdate = $orderContext->getShippingDateOfBirth();
		if ($birthdate === null || empty($birthdate)) {
			$elements[] = Customweb_Form_ElementFactory::getDateOfBirthElement('date_of_birth_year', 'date_of_birth_month', 'date_of_birth_day');
		}
		return $elements;
	}

	protected function getSalesTaxNumberElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		$billingCompany = $orderContext->getShippingCompanyName();
		$salesTaxNumber = $orderContext->getShippingSalesTaxNumber();
		if (!empty($billingCompany) && empty($salesTaxNumber)) {
			$elements[] = Customweb_Form_ElementFactory::getSalesTaxNumberElement('sales_tax_number');
		}
		return $elements;
	}

	protected function getCommericalRegisterNumberElements(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		$billingCompany = $orderContext->getShippingCompanyName();
		$commercialNumber = $orderContext->getShippingCommercialRegisterNumber();
		if (!empty($billingCompany) && empty($commercialNumber)) {
			$elements[] = Customweb_Form_ElementFactory::getCommercialNumberElement('commercial_number');
		}
		return $elements;
	}

	protected function getSocialSecurityNumberElement(Customweb_Payment_Authorization_IOrderContext $orderContext, $failedTransaction){
		$elements = array();
		$billingCompany = $orderContext->getShippingCompanyName();
		$socialSecurityNumber = $orderContext->getShippingSocialSecurityNumber();
		if (empty($billingCompany) && empty($socialSecurityNumber)) {
			$elements[] = Customweb_Form_ElementFactory::getSocialSecurityNumberElement('social_security_number');
		}
		return $elements;
	}

	/**
	 *
	 * @param Customweb_Ogone_Authorization_Transaction $transaction
	 * @param array $formData
	 * @throws Exception
	 * @return DateTime
	 */
	protected function getBirthdate(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$dateOfBirth = $transaction->getTransactionContext()->getOrderContext()->getShippingDateOfBirth();
		
		if ($dateOfBirth === null || empty($dateOfBirth)) {
			if (!isset($formData['date_of_birth_year'])) {
				throw new Exception(Customweb_I18n_Translation::__("No year set in the date of birth field."));
			}
			if (!isset($formData['date_of_birth_month'])) {
				throw new Exception(Customweb_I18n_Translation::__("No month set in the date of birth field."));
			}
			if (!isset($formData['date_of_birth_day'])) {
				throw new Exception(Customweb_I18n_Translation::__("No day set in the date of birth field."));
			}
			
			$year = $formData['date_of_birth_year'];
			$month = $formData['date_of_birth_month'];
			$day = $formData['date_of_birth_day'];
			$dateOfBirth = new DateTime();
			$dateOfBirth->setDate(intval($year), intval($month), intval($day));
		}
		
		return $dateOfBirth;
	}

	protected function getShippingBirthdateParameter(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		return array(
			'ECOM_SHIPTO_DOB' => $this->getBirthdate($transaction, $formData)->format('d/m/Y') 
		);
	}

	protected function getCustomerIdParameter(Customweb_Ogone_Authorization_Transaction $transaction){
		$id = $transaction->getTransactionContext()->getOrderContext()->getCustomerId();
		if (empty($id)) {
			$id = Customweb_Util_Rand::getUuid();
		}
		
		$id = Customweb_Ogone_Util::substrUtf8($id, 0, 50);
		
		return array(
			'REF_CUSTOMERID' => Customweb_Util_String::substrUtf8($id, 0, 17) 
		);
	}

	protected function getCustomerGender(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$parameters = array();
		if ($orderContext->getBillingGender() != 'male' && $orderContext->getBillingGender() != 'female') {
			if (!isset($formData['customer_gender']) || $formData['customer_gender'] == 'none') {
				throw new Exception(Customweb_I18n_Translation::__("You must define your gender."));
			}
			$gender = strtoupper($formData['customer_gender']);
			if ($gender != 'M' && $gender != 'F') {
				throw new Exception("Invalid gender selected.");
			}
			return $gender;
		}
		else {
			if ($orderContext->getBillingGender() == 'male') {
				return 'M';
			}
			else if ($orderContext->getBillingGender() == 'female') {
				return 'F';
			}
		}
	}

	protected function getCustomerGenderParameter(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		return array(
			'ECOM_CONSUMER_GENDER' => $this->getCustomerGender($transaction, $formData) 
		);
	}

	protected function getSalutation(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		$salutation = $orderContext->getBillingSalutation();
		if (!empty($salutation)) {
			return Customweb_Util_String::substrUtf8($salutation, 0, 5);
		}
		else {
			if (empty($formData['salutation'])) {
				throw new Exception(Customweb_I18n_Translation::__("You must enter your salutation."));
			}
			else if (strlen($formData['salutation']) > 5) {
				throw new Exception(Customweb_I18n_Translation::__("The entered salutation is too long. It can not be longer than 5 chars."));
			}
			return strip_tags($formData['salutation']);
		}
	}

	protected function getBillingCivilityParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		return array(
			'CIVILITY' => Customweb_Util_String::substrUtf8($this->getSalutation($transaction, $formData), 0, 10) 
		);
	}

	protected function getSalesTaxNumber(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$billingCompany = $orderContext->getShippingCompanyName();
		
		if (!empty($billingCompany)) {
			$salesTaxNumber = $orderContext->getShippingSalesTaxNumber();
			if (!empty($salesTaxNumber)) {
				return $salesTaxNumber;
			}
			else {
				if (empty($formData['sales_tax_number'])) {
					throw new Exception(Customweb_I18n_Translation::__("You must enter the sales tax number."));
				}
				
				return strip_tags($formData['sales_tax_number']);
			}
		}
		
		return null;
	}

	protected function getSalesTaxNumberParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$salesTaxNumber = $this->getSalesTaxNumber($transaction, $formData);
		if (empty($salesTaxNumber)) {
			return array();
		}
		else {
			return array(
				'ECOM_SHIPTO_TVA' => Customweb_Util_String::substrUtf8($salesTaxNumber, 0, 20) 
			);
		}
	}

	protected function getPhoneNumber(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$phoneNumber = $orderContext->getBillingPhoneNumber();
		if (!empty($phoneNumber)) {
			return $phoneNumber;
		}
		else {
			if (empty($formData['phone_number'])) {
				throw new Exception(Customweb_I18n_Translation::__("You must enter your phone number."));
			}
			return strip_tags($formData['phone_number']);
		}
	}

	protected function getOwnerPhoneNumberParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		return array(
			'OWNERTELNO' => Customweb_Util_String::substrUtf8($this->getPhoneNumber($transaction, $formData), 0, 30) 
		);
	}

	protected function getCustomerReferenceIdParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$registrationNumber = $this->getCommericalRegisterNumber($transaction, $formData);
		if (empty($registrationNumber)) {
			return array();
		}
		else {
			return array(
				'REF_CUSTOMERREF' => Customweb_Util_String::substrUtf8($registrationNumber, 0, 20) 
			);
		}
	}

	protected function getCommericalRegisterNumber(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$billingCompany = $orderContext->getShippingCompanyName();
		if (!empty($billingCompany)) {
			$commercialNumber = $orderContext->getShippingCommercialRegisterNumber();
			if (!empty($commercialNumber)) {
				return $commercialNumber;
			}
			else {
				if (empty($formData['commercial_number'])) {
					throw new Exception(Customweb_I18n_Translation::__("You must enter the company registration number."));
				}
				
				return strip_tags($formData['commercial_number']);
			}
		}
		
		return null;
	}

	protected function getSocialSecurityNumber(Customweb_Ogone_Authorization_Transaction $transaction, array $formData){
		$orderContext = $transaction->getTransactionContext()->getOrderContext();
		
		$billingCompany = $orderContext->getShippingCompanyName();
		if (empty($billingCompany)) {
			$socialSecurityNumber = $orderContext->getShippingSocialSecurityNumber();
			if (!empty($socialSecurityNumber)) {
				return $socialSecurityNumber;
			}
			else {
				if (empty($formData['social_security_number'])) {
					throw new Exception(Customweb_I18n_Translation::__("You must enter your social security number."));
				}
				
				return strip_tags($formData['social_security_number']);
			}
		}
		
		return null;
	}
}

	