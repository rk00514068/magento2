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

//require_once 'Customweb/Util/Currency.php';
//require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';


abstract class Customweb_Ogone_AbstractLineItemBuilder {
	
	/**
	 * @var Customweb_Payment_Authorization_IOrderContext
	 */
	private $orderContext = null;
	
	private $items = null;
	
	/**
	 * @param Customweb_Payment_Authorization_IOrderContext $orderContext
	 * @param string $items
	 */
	public function __construct(Customweb_Payment_Authorization_IOrderContext $orderContext, $items = null) {
		$this->orderContext = $orderContext;
		if ($items === null) {
			$this->items = $this->orderContext->getInvoiceItems();
		}
		else {
			$this->items = $items;
		}
	}
	
	public function build() {
		$parameters = array();
		$counter = 1;
		foreach ($this->getItems() as $item) {
			if ($this->isItemAllowedInList($item)) {
				$fields = $this->getLineItemFields($item, $counter);
				foreach ($fields as $fieldName => $fieldValue) {
					$parameters[$fieldName . $counter] = $fieldValue;
				}
				$counter++;
			}
		}
		return $parameters;
	}
	
	/**
	 * Returns a key/value map with all fields per line item. The key is the field name. The 
	 * value is the value of the field.
	 * 
	 * @return array
	 */
	abstract protected function getLineItemFields(Customweb_Payment_Authorization_IInvoiceItem $item, $counter);
	
	/**
	 * Returns an array with the following constants in it:
	 * - Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING
	 * - Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT
	 * - Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT
	 * - Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE
	 * 
	 * @return array
	 */
	protected function getAllowedProductTypes() {
		return array(
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING,
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT,
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE,
			Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT,
		);
	}
	
	protected function isItemAllowedInList(Customweb_Payment_Authorization_IInvoiceItem $item) {
		$types = $this->getAllowedProductTypes();
		if (in_array($item->getType(), $types)) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Cleans a given string to be accepted by the remote server.
	 * 
	 * @param string $name
	 * @return string
	 */
	protected function sanatizeItemName($name) {
		$name = str_replace("'", '', $name);
		$name = str_replace('"', '', $name);
		return $name;
	}

	protected function getAmountIncludingTax(Customweb_Payment_Authorization_IInvoiceItem $item) {
		$amount = $item->getAmountIncludingTax();
		if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
			$amount = -1 * $amount;
		}
		return $this->formatAmount($amount);
	}
	
	protected function getAmountExcludingTax(Customweb_Payment_Authorization_IInvoiceItem $item) {
		$amount = $item->getAmountExcludingTax();
		if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
			$amount = -1 * $amount;
		}
		return $this->formatAmount($amount);
	}
	
	protected function getProductPriceIncludingTax(Customweb_Payment_Authorization_IInvoiceItem $item) {
		$amount = $item->getAmountIncludingTax();
		if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
			$amount = -1 * $amount;
		}
		$amount = $amount / $item->getQuantity();
		return $this->formatAmount($amount);
	}
	
	protected function getProductPriceExcludingTax(Customweb_Payment_Authorization_IInvoiceItem $item) {
		$amount = $item->getAmountExcludingTax();
		if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
			$amount = -1 * $amount;
		}
		$amount = $amount / $item->getQuantity();
		return $this->formatAmount($amount);
	}
	
	protected function formatAmount($amount) {
		$decimalPlaces = Customweb_Util_Currency::getDecimalPlaces($this->getOrderContext()->getCurrencyCode()) + 2;
		return number_format($amount, $decimalPlaces, '.', '');
	}
	
	protected function getOrderContext() {
		return $this->orderContext;
	}

	protected function cleanString($string) {
		return $string;
	}

	protected function getItems(){
		return $this->items;
	}
	
	
}