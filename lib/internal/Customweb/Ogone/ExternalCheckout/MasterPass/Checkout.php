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

//require_once 'Customweb/Ogone/ExternalCheckout/AbstractCheckout.php';
//require_once 'Customweb/Mvc/Template/RenderContext.php';
//require_once 'Customweb/Mvc/Template/SecurityPolicy.php';
//require_once 'Customweb/Util/JavaScript.php';
//require_once 'Customweb/I18n/Translation.php';



/**
 * MasterPass checkout object.
 *
 * @author Thomas Hunziker
 *
 */
class Customweb_Ogone_ExternalCheckout_MasterPass_Checkout extends Customweb_Ogone_ExternalCheckout_AbstractCheckout {

	private static $LANGUAGE_TO_COUNTRY_MAP = array(
		'pt' => 'br',
		'cn' => 'cn',
		'nl' => 'nl',
		'en' => 'us',
		'fr' => 'fr',
		'de' => 'de',
		'it' => 'it',
	);
	
	public function getMachineName(){
		return 'ogone_masterpass';
	}

	public function getName(){
		return Customweb_I18n_Translation::__("MasterPass");
	}
	
	public function getWidget(Customweb_Payment_ExternalCheckout_IContext $context) {
		$templateContext = new Customweb_Mvc_Template_RenderContext();
		$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
		$templateContext->setTemplate('checkout/masterpass/widget');
		$templateContext->addVariable('languageCode', $this->getLanguageCode($context));
		$templateContext->addVariable('countryCode', strtoupper($this->getCountryCode($context)));
		$templateContext->addVariable('altText', Customweb_I18n_Translation::__("Checkout with MasterPass."));
		$templateContext->addVariable('learnMoreText', Customweb_I18n_Translation::__("Learn More"));
		$token = $this->getContainer()->getCheckoutService()->createSecurityToken($context);
		$templateContext->addVariable('modalJavascript', Customweb_Util_JavaScript::loadLibrary('Modal'));
		$templateContext->addVariable('modalCss', Customweb_Util_JavaScript::loadLibraryCss('Modal'));
		$templateContext->addVariable('redirectUrl', $this->getContainer()->getEndpointAdapter()->getUrl("masterpass", "redirect", array('context-id' => $context->getContextId(), 'token' => $token)));
		return $this->getContainer()->getTemplateRenderer()->render($templateContext);
	}

	private function getLanguageCode(Customweb_Payment_ExternalCheckout_IContext $context) {
		if (isset(self::$LANGUAGE_TO_COUNTRY_MAP[$context->getLanguage()->getIso2LetterCode()])) {
			return $context->getLanguage()->getIso2LetterCode();
		}
		else {
			return 'en';
		}
	}
	
	private function getCountryCode(Customweb_Payment_ExternalCheckout_IContext $context) {
		if (isset(self::$LANGUAGE_TO_COUNTRY_MAP[$context->getLanguage()->getIso2LetterCode()])) {
			return self::$LANGUAGE_TO_COUNTRY_MAP[$context->getLanguage()->getIso2LetterCode()];
		}
		else {
			return 'us';
		}
	}
	
}