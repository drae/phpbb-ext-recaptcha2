<?php
/**
* @copyright (c) Numeric - Paul Owen <http://www.starstreak.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* This file is mostly taken verbatim from the sortables capture:  (c) Derky <http://www.derky.nl>
*/

namespace numeric\recaptchav2;

class ext extends \phpbb\extension\base
{
	const RECAPTCHAV2_CAPTCHA_SERVICE_NAME = 'numeric.recaptchav2.recaptchav2';
	const CONFIG_RECAPTCHAV2_CAPTCHA_WAS_DEFAULT = 'recaptchav2_captcha_was_default';

	/**
	 * Check the phpBB version to determine if this extension can be enabled.
	 *
	 * @return boolean
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return version_compare($config['version'], '3.1.6', '>=');
	}

	/**
	* Single enable step
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	*/
	public function enable_step($old_state)
	{
		// Run parent enable step method
		$return_parent = parent::enable_step($old_state);

		// After the last migration
		if ($return_parent === false)
		{
			// Atomatically reinstate recapturev2 when it was disabled while being the default captcha.
			// recapturev2 will not be set as default captcha for new and purged installations because it requires manual configuration.
			$this->handle_default_captcha_on('enable');
		}
		return $return_parent;
	}

	/**
	* Single disable step
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	*/
	public function disable_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Before the first migration

				// If recapturev2 is the default captcha, reset to phpBB's default captcha and store a recapturev2 was default remember flag.
				return $this->handle_default_captcha_on('disable');

			default:

				// Run parent disable step method
				return parent::disable_step($old_state);
		}
	}

	/**
	* Single purge step
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	*/
	public function purge_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Before the first migration

				// Remove the remember flag for recapturev2 being the default captcha.
				return $this->handle_default_captcha_on('purge');

			default:

				// Run parent purge step method
				return parent::purge_step($old_state);
		}
	}

	/**
	 * In order to update an extension, it has to be disabled. Reset capture to default
	 * GD on disable
	 *
	 * @param string $step enable,disable,purge
	 * @return string
	 */
	public function handle_default_captcha_on($step)
	{
		// Get config
		$config = $this->container->get('config');

		switch ($step)
		{
			case 'enable':

				// On enabling check if recapturev2 was the default captcha before it was disabled
				if ($config[self::CONFIG_RECAPTCHAV2_CAPTCHA_WAS_DEFAULT])
				{
					// Restore recapturev2 as the board's default captcha
					$config->set('captcha_plugin', self::RECAPTCHAV2_CAPTCHA_SERVICE_NAME);
				}
				// Always clean up the remember flag
				$config->delete(self::CONFIG_RECAPTCHAV2_CAPTCHA_WAS_DEFAULT);
			break;

			case 'disable':

				// Check if recapturev2 currently is the default captcha
				if ($config['captcha_plugin'] === self::RECAPTCHAV2_CAPTCHA_SERVICE_NAME)
				{
					// Add a remember flag to the config
					$config->set(self::CONFIG_RECAPTCHAV2_CAPTCHA_WAS_DEFAULT, true);

					// Restore captcha to phpBB's default GD captcha.
					$config->set('captcha_plugin', 'core.captcha.plugins.gd');
				}
			break;

			case 'purge':
				// When the database tables are purged, this captcha can't be set as default captcha until it's reconfigured manually. Therefore delete the remember flag.
				$config->delete(self::CONFIG_RECAPTCHAV2_CAPTCHA_WAS_DEFAULT);
			break;
		}

		return 'default_captcha_handled';
	}
}
