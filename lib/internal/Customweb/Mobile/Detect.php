<?php 

/**
 * Wrapper class to allow the detection based up on the default request object.
 * 
 * @author Thomas Hunziker
 *
 */
final class Customweb_Mobile_Detect{
	
	private $mobileDetect = null;
	
	/**
	 * @var Customweb_Core_Http_IRequest
	 */
	private $request;
	
	public function __construct(Customweb_Core_Http_IRequest $request) {
		$this->request = $request;
	}
	
	public function getMobileDetect() {
		if ($this->mobileDetect === null) {
			$headers = array();
			foreach ($this->request->getParsedHeaders() as $key => $header) {
				$key = strtoupper('HTTP_' . str_replace('-', '_', $key));
				$headers[$key] = $header;
			}
			// We postpone as long as possible the inclusion of the mobile detect to prevent incompatibilities.
			//require_once 'Mobile/Detect.php';
			$this->mobileDetect = new Mobile_Detect($headers);
		}
		
		return $this->mobileDetect;
	}
	
	/**
	 * Returns true, when the given request was from a tablet device.
	 * 
	 * @return boolean
	 */
	public function isTabletDevice() {
		return $this->getMobileDetect()->isTablet();
	}
	
	/**
	 * Returns true, when the given request was from a mobile device.
	 *
	 * @return boolean
	 */
	public function isMobileDevice() {
		return $this->getMobileDetect()->isMobile();
	}
	
	/**
	 * Returns true, when the given request was from a desktop device.
	 *
	 * @return boolean
	 */
	public function isDestopDevice() {
		return !$this->isMobileDevice() && !$this->isTabletDevice();
	}
}