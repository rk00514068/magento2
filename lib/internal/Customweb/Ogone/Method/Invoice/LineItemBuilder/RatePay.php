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

//require_once 'Customweb/Ogone/AbstractLineItemBuilder.php';
//require_once 'Customweb/Ogone/Util.php';


/**
 * 
 * @author Thomas Hunziker
 */
class Customweb_Ogone_Method_Invoice_LineItemBuilder_RatePay extends Customweb_Ogone_AbstractLineItemBuilder {
	
	protected function getLineItemFields(Customweb_Payment_Authorization_IInvoiceItem $item, $counter) {
		$fields = array();
		$fields['ITEMID'] = $counter;
		$fields['ITEMNAME'] = Customweb_Ogone_Util::substrUtf8($this->sanatizeItemName($item->getName()), 0, 40);
		$fields['ITEMPRICE'] = $this->getProductPriceExcludingTax($item);
		$fields['ITEMQUANT'] = round($item->getQuantity());
		$fields['ITEMVAT'] = round($item->getTaxRate(), 2) . "%";
		return $fields;
	}
	
}

