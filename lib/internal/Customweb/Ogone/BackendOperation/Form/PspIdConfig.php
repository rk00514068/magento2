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

//require_once 'Customweb/Form/Control/Select.php';
//require_once 'Customweb/Form/ElementGroup.php';
//require_once 'Customweb/Form/Control/SingleCheckbox.php';
//require_once 'Customweb/Util/Currency.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Form/Control/TextInput.php';
//require_once 'Customweb/Payment/BackendOperation/Form/Abstract.php';


/**
 * @BackendForm
 */
final class Customweb_Ogone_BackendOperation_Form_PspIdConfig extends Customweb_Payment_BackendOperation_Form_Abstract {
	
	
	const CONIFGURED_LIST_KEY = 'configuredCurrencies';
	const STORAGE_SPACE_KEY = 'Ogone_PSPIDs';
	
	public function isProcessable(){
		return true;
	}
	
	
	public function getTitle() {
		return Customweb_I18n_Translation::__("PSPID Config");
	}
	
	public function getElementGroups() {
		$elemetGroups = $this->listExisting();
		$elemetGroups[] = $this->addNewConfig();
		return $elemetGroups;
	}
	
	
	private function listExisting() {
		$currencyGroups = array();
		$currencies = Customweb_Util_Currency::getCurrencies();
		foreach($this->getConfiguredCurrencies() as $currency) {
			$group = new Customweb_Form_ElementGroup();
			$group->setTitle($currencies[$currency]['code'] . ' (' . $currencies[$currency]['name'] . ')')->setId($currency)->setMachineName($currency);
			
			$liveControl = new Customweb_Form_Control_TextInput($currency.'_live', $this->getSettingValue($currency.'_live'));
			$liveElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Live PSPID'), $liveControl);
			
			$testControl = new Customweb_Form_Control_TextInput($currency.'_test', $this->getSettingValue($currency.'_test'));
			$testElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Test PSPID'), $testControl);
			
			$removeControl = new Customweb_Form_Control_SingleCheckbox($currency.'_remove', 'true', Customweb_I18n_Translation::__('Yes'));
			$removeElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Remove'), $removeControl);
			
			$group->addElement($liveElement)->addElement($testElement)->addElement($removeElement);
			$currencyGroups[] = $group;
		}
		return $currencyGroups;
	}
	
	
	private function addNewConfig() {
		$group = new Customweb_Form_ElementGroup();
		$group->setId('add')->setMachineName('add')->setTitle(Customweb_I18n_Translation::__('Add new'));
		
		$options = array( 'none' => '');
		foreach(Customweb_Util_Currency::getCurrencies() as $code => $currency) {
			$options[$code] = $currency['code'] . ' (' . $currency['name'] . ')';
		}
		$currencyControl = new Customweb_Form_Control_Select('add_new_currency', $options, 'none');
		$currencyElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Currency'), $currencyControl);
		
		$liveControl = new Customweb_Form_Control_TextInput('add_new_live');
		$liveElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Live PSPID'), $liveControl);
			
		$testControl = new Customweb_Form_Control_TextInput('add_new_test');
		$testElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Test PSPID'), $testControl);
		
		
		$group->addElement($currencyElement)->addElement($liveElement)->addElement($testElement);
		return $group;
	}
	
	/**
	 * 
	 * @return Customweb_Storage_IBackend
	 */
	private function getStorageBackend() {
		return $this->getContainer()->getBean('Customweb_Storage_IBackend');
	}
	
	private function getConfiguredCurrencies() {
		$currencies = $this->getStorageBackend()->read(self::STORAGE_SPACE_KEY , self::CONIFGURED_LIST_KEY);
		if($currencies === null){
			return array();
		}
		return $currencies;
	}
	
	private function addConfiguredCurrency($code) {
		$existing = $this->getConfiguredCurrencies();
		$existing[$code] = $code;
		$this->getStorageBackend()->write(self::STORAGE_SPACE_KEY , self::CONIFGURED_LIST_KEY, $existing);
	}
	
	private function removeConfiguredCurrency($code) {
		$existing = $this->getConfiguredCurrencies();
		unset($existing[$code]);
		$this->getStorageBackend()->write(self::STORAGE_SPACE_KEY , self::CONIFGURED_LIST_KEY, $existing);
	}
	
	private function removeMultipleConfiguredCurrencies(array $codes) {
		if(!empty($codes)) {
			$existing = $this->getConfiguredCurrencies();
			foreach ($codes as $code) {
				unset($existing[$code]);
			}
			$this->getStorageBackend()->write(self::STORAGE_SPACE_KEY , self::CONIFGURED_LIST_KEY, $existing);
		}
	}
	
	public function getButtons(){
		return array( $this->getSaveButton());
	}
	
	public function process(Customweb_Form_IButton $pressedButton, array $formData){
		$toRemove = array();
		if ($pressedButton->getMachineName() === 'save') {
			
			foreach($this->getConfiguredCurrencies() as $currency) {
				if(isset($formData[$currency.'_remove']) && $formData[$currency.'_remove'] == true) {
					unset($formData[$currency.'_remove']);
					unset($formData[$currency.'_live']);
					unset($formData[$currency.'_test']);
					$toRemove[] = $currency;
				}
			}
			
			
			if(isset($formData['add_new_currency']) && $formData['add_new_currency'] != 'none') {
				//new currency added
				$newCurrency = $formData['add_new_currency'];
				$this->addConfiguredCurrency($newCurrency);
				$formData[$newCurrency.'_live'] = $formData['add_new_live'];
				$formData[$newCurrency.'_test'] = $formData['add_new_test'];
			}
			unset($formData['add_new_currency']);
			unset($formData['add_new_live']);
			unset($formData['add_new_test']);
			
			$this->getSettingHandler()->processForm($this, $formData);
		}
		$this->removeMultipleConfiguredCurrencies($toRemove);
	}
}