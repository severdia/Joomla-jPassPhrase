<?php
/**
* @Copyright Copyright (C) 2016 Kontent Design
* @Copyright Copyright (C) 2012 Roger Noar, www.rayonics.com
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

// no direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.keepalive'); // Joomla 3
JHtml::_('bootstrap.tooltip'); // Joomla 3
ob_start();  // output buffer to allow redirection later, must occur before any html code
?>

<!-- BEGIN jPASSPHRASE PHP CODE TO PROCESS PASSPHRASE SUBMIT BUTTON -->
<?PHP
	$mainframe = &JFactory::getApplication();
	$redirect_url = 'index.php?option=com_users&view=registration' ; //Standard Joomla registration page
	if (isset($_POST['submitted'])) {  //if submit button clicked
		$passphrase = $_POST['passphrase']; //text from submit box
		$syms= array(" ","\r\n","\n\r","\r","\n","\l","\t",chr(13),chr(10)); // carriage returns & spaces
		$passphrase = str_replace($syms, '', $passphrase); // remove carriage returns & spaces
		$passphrase = trim(strtolower($passphrase)); //lower case, remove spaces
		$pass = "&pass=".$passphrase;  //append passphrase
		ob_end_clean();   //finish output buffer stated with ob_start above
		$mainframe->redirect($redirect_url . $pass,  '');
	}
?>

<!-- jPASSPHRASE INPUT FORM AND BUTTON -->
<form action="" method="post" name="passphrase" id="form-jpassphrase" >
	<?php echo $params->def('pre_text'); ?>
	<fieldset class="input">
		<p id="form-JRpassphrase">
			<input id="modjpassphrase" type="text" name="passphrase" class="input-<?php echo $params->def('box_width'); ?>" alt="passphrase" size="18" />
		</p>
		<?php $cnt=$params->def('button_space');
			for ($i = 0; $i < $cnt; $i++) { 
				echo '<br />' ; 
			}
		?>
		<input type="submit" name="Submit" class="button" value="<?PHP echo $params->def('button_text'); ?>" />
	</fieldset>
	<input type="hidden" name="submitted" value="true" > <!-- Needed to fix Internet Explorer problem if "Enter" used to submit form -->
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
	<?php echo $params->def('post_text'); ?>
</form>