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

//require_once 'Customweb/Form/ElementGroup.php';
//require_once 'Customweb/Form/Control/Html.php';
//require_once 'Customweb/Form/WideElement.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/BackendOperation/Form/Abstract.php';


/**
 * @BackendForm
 */
final class Customweb_Ogone_BackendOperation_Form_Urls extends Customweb_Payment_BackendOperation_Form_Abstract {

	public function getTitle() {
		return Customweb_I18n_Translation::__("Setup");
	}
	
	public function getElementGroups() {
		return array(
			$this->getSetupGroup(),
			$this->getUrlGroup(),
		);
	}
	
	private function getUrlGroup() {
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle('URLs');
		$group
			->addElement($this->getNotificationUrlElement())
			->addElement($this->getOfflineUpdateUrl())
			->addElement($this->getTemplateUrlElement());
		return $group;
	}

	private function getNotificationUrlElement() {
		$control = new Customweb_Form_Control_Html('notificationURL', $this->getEndpointAdapter()->getUrl('process', 'index'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Notification URL"), $control);
		$element->setDescription(Customweb_I18n_Translation::__(
				"This URL has to be placed in the backend of Ogone under Configuration > Technical Information > Transaction Feedback and their in the two fields for 'Direct HTTP server-to-server request'."
		));
		return $element;
	}
	
	private function getOfflineUpdateUrl() {
		$control = new Customweb_Form_Control_Html('statuschangeURL', $this->getEndpointAdapter()->getUrl('process', 'update'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("HTTP Status Changes URL"), $control);
		$element->setDescription(Customweb_I18n_Translation::__(
				"This URL has to be placed in the backend of Ogone under Configuration > Technical Information > Transaction Feedback and their in the field for 'HTTP request for status changes'."
		));
		return $element;
	}

	private function getTemplateUrlElement() {
		$control = new Customweb_Form_Control_Html('templateURL', $this->getEndpointAdapter()->getUrl('template', 'index'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Template URL"), $control);
		$element->setDescription(Customweb_I18n_Translation::__(
				"This URL is used by default for handling the dynamic template of Ogone. To use this feature " . 
				"you may need activate the feature in your account and you need to add the domain to the Trusted website hostnames. To work properly in all browser you need HTTPS on your server and domain."
		));
		return $element;
	}
	
	public function getSetupGroup() {
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle(Customweb_I18n_Translation::__("Short Installation Instructions:"));
	
		$control = new Customweb_Form_Control_Html('description', Customweb_I18n_Translation::__('This is a brief instruction of the main and most important installation steps, which need to be performed when installing the Ogone module. For detailed instructions regarding additional and optional settings, please refer to the enclosed instructions in the zip. '));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('steps', '<ol>
					<li>'.Customweb_I18n_Translation::__('Enter the PSPID for the test and live (if available) mode.').'</li>
					<li>'.Customweb_I18n_Translation::__('Create the SHA signatures (SHA-IN and SHA-OUT) with the !linkStart SHA signatures generator !linkEnd.', array( '!linkStart' => '<a href=\'http://www.customweb.com/signature_gernerator.php\' target=\'_blank\'>', '!linkEnd' => '</a>')).'</li>
					<li>'.Customweb_I18n_Translation::__('Copy the SHA-IN signature in the module and in the back-end of Ogone under the menu Technical Information > Origin Verification.').'</li>
					<li>'.Customweb_I18n_Translation::__('Copy the SHA-OUT signature in the module and in the back-end of Ogone under and Technical Information > Transaction Feedback.').'</li>
					<li>'.Customweb_I18n_Translation::__('Enter the URLs in the PSP Technical Information > Transaction Feedback. Make sure that the feedback is set to &quot;Online but switch to a deferred request when the online requests fail&quot;.').'</li>
					<li>'.Customweb_I18n_Translation::__('Make sure that the request method is set to POST.').'</li>
					<li>'.Customweb_I18n_Translation::__('Enable the desired payment methods.').'</li>
				</ol>');
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		return $group;	
	}
		
}