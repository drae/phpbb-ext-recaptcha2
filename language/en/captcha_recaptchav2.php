<?php
/**
*
* sortables captcha [English]
*
* @copyright (c) Derky <http://www.derky.nl>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'CAPTCHA_RECAPTCHAV2'			=> 'ReCaptcha vs.2',

	'RECAPTCHAV2_SITEKEY'			=> 'Site key',
	'RECAPTCHAV2_SITEKEY_EXPLAIN'	=> 'Enter the site key as provided by Google',
	'RECAPTCHAV2_SECKEY'			=> 'Secret key',
	'RECAPTCHAV2_SECKEY_EXPLAIN'	=> 'Enter the secret key as provided by Google',
	'RECAPTCHAV2_THEME'				=> 'Theme',
	'RECAPTCHAV2_THEME_LIGHT'		=> 'Light',
	'RECAPTCHAV2_THEME_DARK'		=> 'Dark',
	
	'RECAPTCHAV2_MISSING'			=> 'You did not click the captcha.',
	'RECAPTCHAV2_INCORRECT'			=> 'The captcha was not solved correctly please try again.',
));
