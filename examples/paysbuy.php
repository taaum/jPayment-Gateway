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

/**
 * Include config
 */
require_once('config.inc.php');

/**
 * Instance payment class
 */
$mp = Payment::factory('paysbuy', array(
	'successUrl'    => "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?action=process",
	'cancelUrl'     => "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?action=process",
	'backendUrl'    => "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?action=process"	
));

/**
 * Set account
 user : demo@paysbuy.com
 password : paysbuy123
 PSBID: 8303545188
 Secure Code: 1586093A8F80CBB5003001B42F0EEB7C

 user : example@paysbuy.com
 password : psb12345
 PSBID: 3687016837
 Secure Code:  B9350779BE7F822F9DF5033372E288CD
 */
$mp->setMerchantAccount('teepluss@gmail.com');

/**
 * Set sandbox environment
 */
$mp->setSandboxMode(true);

/**
 * Custom gateway
 * Not work for all adapters
 */
$mp->setLanguage('TH')
	->setCurrency('THB');
	
/**
 * Custom payment method
 * Work for match merchant
 */
$mp->setMethod('psb')
	->setForceMethod(false);
	
/** 
 * Set billing 
 */
$mp->setInvoice('DEMO00000C07')
	->setPurpose('Buy Something')
	->setAmount(999);
	
/**
 * When gateway redirect back with success status
 */	
if ($mp->isSuccessPosted())
{
	echo "<h1>Success Posted, just redirect the user to thank you page.</h1>";
	
	$result = $mp->getFrontendResult();
	echo "<pre>".print_r($result, true)."</pre>";
	
	exit(0);
}

/**
 * When gateway POSTED back with status
 */
if ($mp->isBackendPosted())
{
	echo "<h1>Backend Posted, keep the transaction data and update depend on status responded.</h1>";
	
	$result = $mp->getBackendResult();
	$result = print_r($result, true);
	
	echo "<pre>".$result."</pre>";
	file_put_contents('log.txt', $result);
	
	exit(0);
}

//echo $mp->render(); exit(0);


?>
<!DOCTYPE html>
<html>
	<head>
		<title>Redirecting to Payment Gateway</title>
		<style type="text/css">
			html, body { font-family: verdana; font-size: 12px; }
			h3 { font-size: 1em; font-weight: normal; }
		</style>
		<script type="text/javascript">
			function onDocumentReady() {
				document.getElementById('form-gateway').submit();
			}
		</script>
	</head>
	<body onload="onDocumentReady();">
		<h3>Waiting to redirect...</h3>
		<?php echo $mp->render(); ?>
	</body>
</html>