<?php
/**
* @Copyright Copyright (C) 2016 Kontent Design
* @Copyright Copyright (C) 2012 Roger Noar, www.rayonics.com
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.event.plugin');

class  plgSystemJPassPhrase extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args (void) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param 	array   $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function plgSystemJPassPhrase(& $subject, $config) 
	{
		parent::__construct($subject, $config);
	}
	
	// Don't use onAfterInitialize - not reliable for redirection if SEF's are used
	function onAfterDispatch() 
	{
		$mainframe = JFactory::getApplication();
		//  Get current URL
		$jrpassphrase_version = 'JPassPhrase v4.0';
		$uri = JFactory::getURI(); // Get current URL
		$url = $uri->toString(); // convert to string
		$task = JRequest::getCmd('task');
		$view = JRequest::getCmd('view');
		$layout = JRequest::getCmd('layout');
		$intercept_string = $this->params->def('intercept_string', ''); // get parameter for the intercept substring for 3rd party registration pages
		if (empty($intercept_string)) {
			$intercept_string = "?..:" ; // if empty, use illegal url chars so will never get a match
		} 
		if ($task != 'registration' && $view != 'registration' && (strpos($url, $intercept_string) === false)) {
			return; //Exit if not a registration page - low overhead
		} 
		if ($layout == 'complete') {
			return; // Exit since registration is completed
		} 

	// Check to see if a system message indicates registration in progress - such as if registration info needs to be re-entered by user
	$messages = $mainframe->getMessageQueue();
	if (is_array($messages)) {
		foreach($messages as $msg) {
			//$mainframe->enqueueMessage('msgtype =' . $msg['type'] . ' msg=' . $msg['message']) ; // for testing purposes
			if ($msg['message'] != '') {
				return;
			}
		}
	}

	//  This is a registration page so do the following:
	$pass = '';
	$pass = JRequest::getCmd('pass');
	$option = JRequest::getCmd('option');
	$u_host = $uri->getHost();
	$u_path = $uri->getPath();

	//  Get parameters
	$debug = $this->params->def('debug', '');
	$reg_pass = $this->params->def('reg_passphrase', '');
	$alt_reg_url = $this->params->def('alt_reg_url', '');
	$passphrase_page_url = $this->params->def('passphrase_page_url', '');
	$fail_message = $this->params->def('fail_message', '');
	$intercept_message = $this->params->def('intercept_message', '');
	if (($intercept_message == ' ') || ($intercept_message == '.')) {
		$intercept_message = '';
	}
	$bypass_string = $this->params->def('bypass_string', '');
	$message_type = $this->params->def('message_type', ''); // Joomla enqueue message type
	$reg_path = 'index.php' ; // Reg path is front page by default
	if (!empty($passphrase_page_url)) {
		$reg_path = $passphrase_page_url; //alternate location for passphrase page
	} 

	//	Remove carriage returns	& spaces from the passphrase
	$syms= array(" ","\r\n","\n\r","\r","\n","\l","\t",chr(13),chr(10)); //remove carriage returns & spaces
	$reg_pass= str_replace($syms, '', $reg_pass);
	$pass= str_replace($syms, '', $pass);
	$reg_pass=trim(strtolower($reg_pass)); //lower case only, remove leading & trailing spaces
	$pass=trim(strtolower($pass)); //lower case only, remove leading & trailing spaces

	//  Display debug info
	if ($debug=='1') {
		$mainframe->enqueueMessage('version=' . $jrpassphrase_version) ;
		$mainframe->enqueueMessage('task=' . $task) ;
		$mainframe->enqueueMessage('view=' . $view) ;
		$mainframe->enqueueMessage('option=' . $option) ;
		$mainframe->enqueueMessage('pass=' . $pass) ;
		$mainframe->enqueueMessage('reg_pass=' . $reg_pass) ;
		$mainframe->enqueueMessage('alt_reg_url=' . $alt_reg_url) ;
		$mainframe->enqueueMessage('$passphrase_page_url=' . $passphrase_page_url) ;
		$mainframe->enqueueMessage('$reg_path ='. $reg_path);
		$mainframe->enqueueMessage('JFactory::getURI =' . $url) ;
		$mainframe->enqueueMessage('getHost =' . $u_host) ;
		$mainframe->enqueueMessage('getPath =' . $u_path) ;
		$mainframe->enqueueMessage('$_SERVER[SERVER_NAME] =' . $_SERVER['SERVER_NAME']) ;
		$mainframe->enqueueMessage('$_SERVER[HTTP_HOST] =' . $_SERVER['HTTP_HOST']) ;
		$mainframe->enqueueMessage('$_SERVER[REQUEST_URI] =' . $_SERVER['REQUEST_URI']) ;
		$mainframe->enqueueMessage('$_SERVER[SCRIPT_NAME] =' . $_SERVER['SCRIPT_NAME']) ;
	}

	//  Bypass string checking
	if (!empty($bypass_string) &&  (strpos($url, '&pass=') === false)) {
		$substring = explode(" ",$bypass_string); // get all bypass strings, separated by spaces
		$tag_match = false; // initialize as false
		foreach($substring as $tag) {
			if (empty($tag)) {
				break; // stop if tag empty
			} 
			$tag_match = strpos($url, $tag);  //See if the substring tag is present in the path
			if ($tag_match !== false) {
				return; //If bypass string matches, then don't intercept
			} 
		}
	}

	//	Check passphrase
	if($pass != $reg_pass) {
		if (empty($pass) && !empty($intercept_message)) {
			$mainframe->enqueueMessage($intercept_message, $message_type); 
		}
		if (!empty($pass)) {
			$mainframe->enqueueMessage($fail_message, $message_type); 
		}
		if ($debug=='1') {
			return;
		}
		$mainframe->redirect($reg_path);
		return;  // Redirect since passphrase is incorrect
	}

	//	Passphrase is correct at this point
	// Check to see if we are using an alternate reg page
	if (!empty($alt_reg_url) && $debug=='0') {
		$pos = strpos($url, $alt_reg_url);  //Check to see if already at the alternate reg page, to keep from looping redirection
		if ($pos === false) {
			$mainframe->redirect($alt_reg_url . '&pass=' . $reg_pass); // Redirect to alternate registration page
		} 
	}
	return; // return from function
	}
}
?>