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

//require_once 'Customweb/Ogone/AbstractLineItemBuilder.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Util/Currency.php';
//require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';



/**
 *
 * @author Thomas Hunziker
 */
class Customweb_Ogone_Method_LineItemBuilder_AmazonCheckout extends Customweb_Ogone_AbstractLineItemBuilder {

	public function build() {
		$parameters = parent::build();
		//shipping
		$parameters['ORDERSHIPCOST'] = 0;
		$parameters['ORDERSHIPTAX']  = 0;
		foreach ($this->getItems() as $item){
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING){
				$shipFields = $this->getShippingFields($item);
				$parameters['ORDERSHIPCOST'] += $shipFields['ORDERSHIPCOST'];
				$parameters['ORDERSHIPTAX']  += $shipFields['ORDERSHIPTAX'];
			}
		}
		return $parameters;
	}
	
	protected function getAllowedProductTypes() {
		return array(
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT,
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE,
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT,
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Ogone_AbstractLineItemBuilder::getLineItemFields()
	 */
	protected function getLineItemFields(Customweb_Payment_Authorization_IInvoiceItem $item, $counter){
		if($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING){
			return array();
		} else {
			return $this->getProductFields($item, $counter);
		}
	}
	
	/**
	 * 
	 * @param Customweb_Payment_Authorization_IInvoiceItem $item
	 * @return array
	 */
	private function getShippingFields(Customweb_Payment_Authorization_IInvoiceItem $item){
		$fields = array();
		$fields['ORDERSHIPCOST'] = Customweb_Util_Currency::formatAmount($item->getAmountExcludingTax(), $this->getOrderContext()->getCurrencyCode()) * 100;
		$fields['ORDERSHIPTAX'] = round($item->getTaxAmount(), 2) * 100;
		return $fields;
	}
	
	/**
	 * 
	 * @param Customweb_Payment_Authorization_IInvoiceItem $item
	 * @param int $counter
	 * @return array
	 */
	private function getProductFields(Customweb_Payment_Authorization_IInvoiceItem $item, $counter){
		$fields = array();
		$fields['ITEMID'] = $counter;
		$fields['ITEMNAME'] = Customweb_Ogone_Util::substrUtf8($this->sanatizeItemName($item->getName()), 0, 40);
		$fields['ITEMPRICE'] = $this->getProductPriceExcludingTax($item);
		$fields['ITEMQUANT'] = round($item->getQuantity());
		$fields['ITEMVAT'] = Customweb_Util_Currency::formatAmount($item->getTaxAmount()/$item->getQuantity(), $this->getOrderContext()->getCurrencyCode()); // . "%";
		return $fields;
	}
}
