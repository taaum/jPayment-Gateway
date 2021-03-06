<?php
/**
 * Payment
 *
 * This source file is subject to the new BSD license that is bundled
 * It is also available through the world-wide-web at this URL:
 * http://www.jquerytips.com/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to admin@jquerytips.com so we can send you a copy immediately.
 *
 * @category   Payment
 * @package    Payment
 * @copyright  Copyright (c) 2005-2011 jQueryTips.com
 * @version    1.0b
 */

require_once('AdapterAbstract.php');

class Payment_Adapter_Paysbuy extends Payment_Adapter_AdapterAbstract {
	
	/**
	 * Define Gateway name
	 */
	const GATEWAY = "Paysbuy";
	
	/**
	 * @var Gateway URL
	 */
	protected $_gatewayUrl = "https://www.paysbuy.com/paynow.aspx";
	
	/**
	 * @var Check payment transaction (available only paysbuy)
	 */
	protected $_checkUrl = "https://www.paysbuy.com/getinvoicestatus/getinvoicestatus.asmx/GetInvoice";
	
	/**
	 * @var Payment method
	 */
	protected $_method = "c";
	
	/**
	 * @var Force method 
	 */
	protected $_forceMethod = 0;
	
	/**
	 * @var mapping to transfrom parameter from gateway
	 */
	protected $_defaults_params = array(
		'currencyCode'   => "840",
		'opt_fix_method' => 0,
		'biz'            => "",
		'inv'            => "",
		'itm'            => "",
		'amt'            => "",
		'postURL'        => "",
		'reqURL'         => ""	
	);
	
	/**
	 * @var mapping language frontend interface
	 */
	protected $_language_maps = array(
		'EN' => "e",
		'TH' => "t"
	);
	
	/**
	 * @var mapping currency
	 */
	protected $_currency_maps = array(
		'USD' => "840",
		'AUD' => "036",
		'GBP' => "826",
		'EUR' => "978",
		'HKD' => "344",
		'JPY' => "392",
		'NZD' => "554",
		'SGD' => "702",
		'CHF' => "756",
		'THB' => "764"
	);
	
	/**
	 * @var mapping payment methods
  	 */
	protected $_method_maps = array(
		'psb' => "Paysbuy Account",
		'c'   => "Visa Credit Card",
		'm'   => "Master Card",
		'j'   => "JBC",
		'a'   => "American Express",
		'p'   => "Paypal",
		'cs'  => "Counter Service",
		'ob'  => "Online Banking"
	);

	/**
	 * Construct the payment adapter
	 * 
	 * @access public
	 * @param  array $params (default: array())
	 * @return void
	 */
	public function __construct($params=array())
	{
		parent::__construct($params);
	}
	
	/**
	 * Set to enable sandbox mode
	 * 
	 * @access public
	 * @param  bool 
	 * @return object class (chaining)
	 */
	public function setSandboxMode($val)
	{
		$this->_sandbox = $val;
		if ($val == true) {
			$this->_gatewayUrl = str_replace('www.', 'demo.', $this->_gatewayUrl);
		}
		return $this;
	}
	
	/**
	 * Get sandbox enable
	 * 
	 * @access public
	 * @return bool
	 */
	public function getSandboxMode()
	{
		return $this->_sandbox;
	}
	
	/**
	 * Set payment method
	 * 
	 * @access public
	 * @param  string $val
	 * @return object class (chaining)
	 */
	public function setMethod($val)
	{
		if (array_key_exists($val, $this->_method_maps)) {
			$this->_method = $val;
		}
		return $this;
	}
	
	/**
	 * Get payment method
	 * 
	 * @access public
	 * @return string
	 */
	public function getMethod()
	{
		return $this->_method;
	}
	
	/**
	 * Set force payment method
	 * 
	 * @access public
	 * @param  string $val
	 * @return object class (chaining)
	 */
	public function setForceMethod($val)
	{
		$this->_forceMethod = $val;
		return $this;
	}
	
	/**
	 * Get force payment method
	 * 
	 * @access public
	 * @return string
	 */
	public function getForceMethod($val)
	{
		$this->_forceMethod = $val;
	}
	
	/**
	 * Build array data and mapping from API
	 * 
	 * @access public
	 * @param  array $extends (default: array())
	 * @return array
	 */
	public function build($extends=array())
	{
		$pass_parameters = array(
			'biz'            => $this->_merchantAccount,
			'inv'            => $this->_invoice,
			'itm'            => $this->_purpose,
			'amt'            => $this->_amount,
			'postURL'        => $this->_successUrl,
			'reqURL'         => $this->_backendUrl,
			'opt_fix_method' => $this->_forceMethod,
			'currencyCode'   => $this->_currency_maps[$this->_currency]
		);
		$params = array_merge($pass_parameters, $extends);
		$build_data = array_merge($this->_defaults_params, $params);
		return $build_data;
	}
	
	/**
	 * Render from data with hidden fields
	 * 
	 * @access public
	 * @param  array $attrs (default: array())
	 * @return string HTML
	 */
	public function render($attrs=array())
	{
		// make optional with query string
		$opts = array(
			$this->_method => "true",
			'lang'         => $this->_language_maps[$this->_language],
		);
		$query = http_build_query($opts);
		
		$this->_gatewayUrl .= "?".$query;
		
		//echo $this->_gatewayUrl; exit(0);
		$data = $this->build($attrs);
		return $this->_makeFormPayment($data);
	}
	
	/**
	 * Get a post back result from API gateway
	 * POST data from API
	 * Only Paysbuy we re-check transaction 
	 * 
	 * @access public
	 * @return array (POST)
	 */
	public function getFrontendResult()
	{		
		if (count($_POST) == 0 || !array_key_exists('apCode', $_POST)) {
			return false;
		}	
		$postdata = $_POST;
		
		$status = substr($postdata['result'], 0, 2);
		$invoice = substr($postdata['result'], 2);
		
		$result = array(
			'status' => true,
			'data' => array(
				'gateway'  => self::GATEWAY,
				'status'   => (strcmp($status, 00)) ? "success" : "failed",
				'invoice'  => $invoice,
				'currency' => $this->_currency,
				'amount'   => $postdata['amt'],				
				'dump'     => serialize($postdata)
			)
		);
		return $result;
	}
	
	/**
	 * Get data posted to background process.
	 * Sandbox is not available to use this, because have no API
	 * 
	 * @access public
	 * @return array
	 */
	public function getBackendResult()
	{
		// paysbuy sandbox mode is fucking, so they don't have a simulate API to check invoice
		// anyway we can still use get fronend method instead.
		if ($this->_sandbox == true) {
			return $this->getFrontendResult();
		}
		
		if (count($_POST) == 0 || !array_key_exists('apCode', $_POST)) {
			return false;
		}
		$postdata = $_POST;
		
		// invoice from response
		$invoice = substr($postdata['result'], 2);

		try {
			$params = array(
				'merchantEmail' => $this->_merchantAccount, 
				'invoiceNo'     => $invoice,			 
				'strApCode'     => $postdata['apCode']
			);
			$response = $this->_makeRequest($this->_checkUrl, $params);
			$xml = $response['response'];
			
			// parse XML
			$sxe = new SimpleXMLElement($xml);
			
			$methodResult = (string)$sxe->MethodResult;
			$statusResult = (string)$sxe->StatusResult;

			$result = array(
				'status' => true,
				'data'   => array(
					'gateway'  => self::GATEWAY,
					'status'   => $this->_mapStatusReturned($statusResult),
					'invoice'  => $invoice,
					'currency' => $this->_currency,
					'amount'   => (string)$sxe->AmountResult,
					'dump'     => serialize($postdata)
				),
				'custom' => array(
					'recheck' => "yes"
				)
			);
		}
		catch (Exception $e) {
			$result = array(
				'status' => false,
				'msg'    => $e->getMessage()
			);
		}		
		return $result;
	}
	
}

?>