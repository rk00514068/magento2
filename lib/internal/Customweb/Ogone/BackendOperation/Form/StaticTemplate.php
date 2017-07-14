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

//require_once 'Customweb/Form/Button.php';
//require_once 'Customweb/Form/ElementGroup.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Form/Control/TextInput.php';
//require_once 'Customweb/Payment/BackendOperation/Form/Abstract.php';
//require_once 'Customweb/Form/IButton.php';



/**
 * @BackendForm
 */
final class Customweb_Ogone_BackendOperation_Form_StaticTemplate extends Customweb_Payment_BackendOperation_Form_Abstract {
	const STORAGE_SPACE_KEY = 'Ogone_StaticTemplate';

	public static function getTemplateFields(){
		return array(
			'BGCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Background Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => 'white' 
			),
			'TXTCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Text Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => 'black' 
			),
			'TBLBGCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Table Background Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => 'white' 
			),
			'TBLTXTCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Table Text Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => 'black' 
			),
			'BUTTONBGCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Button Background Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => '' 
			),
			'BUTTONTXTCOLOR' => array(
				'label' => Customweb_I18n_Translation::__('Button Text Color'),
				'desc' => Customweb_I18n_Translation::__('The colors can be specified by their hexadecimal code (#FFFFFF) or their name (white).'),
				'default' => 'black' 
			),
			'FONTTYPE' => array(
				'label' => Customweb_I18n_Translation::__('Font Family'),
				'desc' => Customweb_I18n_Translation::__('The Font Family that will be used'),
				'default' => 'Verdana' 
			),
			'LOGO' => array(
				'label' => Customweb_I18n_Translation::__('Logo'),
				'desc' => Customweb_I18n_Translation::__(
						'URL of the logo you want to display at the top of the payment page. The URL must be absolute. The logo needs to be stored on a secure server. If you do not
have a secure environment to store your image, contact your Ogone to store your logo on their server.'),
				'default' => '' 
			) 
		);
	}

	public function isProcessable(){
		return true;
	}

	public function getTitle(){
		return Customweb_I18n_Translation::__("Static Template Css");
	}

	public function getElementGroups(){
		$elemetGroups = array();
		$elemetGroups[] = $this->cssList();
		return $elemetGroups;
	}

	private function cssList(){
		$cssGroup = new Customweb_Form_ElementGroup();
		$cssGroup->setTitle(Customweb_I18n_Translation::__('Static Tempalte Css'));
		foreach (self::getTemplateFields() as $key => $value) {
			$control = new Customweb_Form_Control_TextInput($key, $this->getPrefillValue($key, $value['default']));
			$element = new Customweb_Form_Element($value['label'], $control, $value['desc']);
			$cssGroup->addElement($element);
		}
		return $cssGroup;
	}

	private function getPrefillValue($key, $default){
		$stored = $this->getSettingValue($key);
		if ($stored === null) {
			return $default;
		}
		return $stored;
	}

	/**
	 *
	 * @return Customweb_Storage_IBackend
	 */
	private function getStorageBackend(){
		return $this->getContainer()->getBean('Customweb_Storage_IBackend');
	}

	public function getButtons(){
		return array(
			$this->getSaveButton(),
			$this->getResetButton(),
			$this->getDefaultButton() 
		);
	}

	private function getResetButton(){
		$button = new Customweb_Form_Button();
		$button->setMachineName("reset")->setTitle(Customweb_I18n_Translation::__("Reset"))->setType(Customweb_Form_IButton::TYPE_CANCEL);
		return $button;
	}

	private function getDefaultButton(){
		$button = new Customweb_Form_Button();
		$button->setMachineName("default")->setTitle(Customweb_I18n_Translation::__("Default Values"))->setType(Customweb_Form_IButton::TYPE_DEFAULT);
		return $button;
	}

	public function process(Customweb_Form_IButton $pressedButton, array $formData){
		if ($pressedButton->getMachineName() === 'save') {
			$this->getSettingHandler()->processForm($this, $formData);
		}
		elseif ($pressedButton->getMachineName() === 'default') {
			$this->getSettingHandler()->processForm($this, array());
		}
	}
}