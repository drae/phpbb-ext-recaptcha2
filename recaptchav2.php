<?php
/**
* @copyright (c) Numeric <http://www.starstreak.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace numeric\recaptchav2;
use numeric\recaptchav2\ReCaptcha;

/**
* Sortables captcha with extending of the QA captcha class.
*/
class recaptchav2 extends \phpbb\captcha\plugins\captcha_abstract
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;
	
	protected $acp_form_key = 'acp_captcha_recaptchav2';
	protected $acp_list_url;
	
	private static $_signupUrl = 'https://www.google.com/recaptcha/admin';
	private static $_siteVerifyUrl = 'https://www.google.com/recaptcha/api/siteverify?';
	private static $_version = 'php_1.0';

	var $response;

	/**
	 *
	 * @param \phpbb\db\driver\driver_interface		$db
	 * @param \phpbb\cache\driver\driver_interface	$cache
	 * @param \phpbb\config\config					$config
	 * @param \phpbb\log\log_interface				$log
	 * @param \phpbb\request\request_interface		$request
	 * @param \phpbb\template\template				$template
	 * @param \phpbb\user							$user
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\cache\driver\driver_interface $cache, \phpbb\config\config $config, \phpbb\log\log_interface $log, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->config = $config;
		$this->log = $log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	/**
	* @param int $type  as per the CAPTCHA API docs, the type
	*/
	public function init($type)
	{
		// load our language file
		$this->user->add_lang_ext('numeric/recaptchav2', 'captcha_recaptchav2');
		
		$this->response = $this->request->variable('g-recaptcha-response', '');
	}
	
	public function is_available()
	{
		// load language file for pretty display in the ACP dropdown
		$this->user->add_lang_ext('numeric/recaptchav2', 'captcha_recaptchav2');
		
		return (!empty($this->config['recaptchav2_sitekey']) && !empty($this->config['recaptchav2_seckey']));
	}
	
	/**
	*  API function
	*/
	function has_config()
	{
		return true;
	}

	static public function get_name()
	{
		return 'CAPTCHA_RECAPTCHAV2';
	}

	/**
	* This function is implemented because required by the upper class, but is never used for reCaptchav2.
	*/
	function get_generator_class()
	{
		throw new \Exception('No generator class given.');
	}
	
	function acp_page($id, &$module)
	{
		$captcha_vars = array(
			'recaptchav2_sitekey' 	=> 'RECAPTCHAV2_SITEKEY',
			'recaptchav2_seckey' 	=> 'RECAPTCHAV2_SECKEY',
			'recaptchav2_theme' 	=> 'RECAPTCHAV2_THEME',
		);
		
		$this->user->add_lang('acp/board');
		$this->user->add_lang_ext('numeric/recaptchav2', 'captcha_recaptchav2');

		$module->tpl_name = '@numeric_recaptchav2/captcha_recaptchav2_acp';
		$module->page_title = 'ACP_VC_SETTINGS';
		add_form_key($this->acp_form_key);

		$submit = $this->request->variable('submit', '');

		if ($submit && check_form_key($this->acp_form_key))
		{
			$captcha_vars = array_keys($captcha_vars);
			foreach ($captcha_vars as $captcha_var)
			{
				$value = $this->request->variable($captcha_var, '');
				if ($value)
				{
					$this->config->set($captcha_var, $value);
				}
			}

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_VISUAL');
			trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($this->acp_list_url));
		}
		else if ($submit)
		{
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->acp_list_url));
		}
		else
		{
			foreach ($captcha_vars as $captcha_var => $template_var)
			{
				$var = (isset($_REQUEST[$captcha_var])) ? $this->request->variable($captcha_var, '') : ((isset($this->config[$captcha_var])) ? $this->config[$captcha_var] : '');
				$this->template->assign_var($template_var, $var);
			}

			$this->template->assign_vars(array(
				'CAPTCHA_PREVIEW'	=> $this->get_demo_template($id),
				'CAPTCHA_NAME'		=> $this->get_service_name(),
				'U_ACTION'			=> $module->u_action,
			));

		}
	}

	// not needed
	function execute_demo()
	{
	}

	// not needed
	function execute()
	{
	}

	function get_template()
	{
		$this->template->assign_vars(array(
			'RECAPTCHAV2_ERRORGET'		=> '',
			'RECAPTCHAV2_SITEKEY'		=> isset($this->config['recaptchav2_sitekey']) ? $this->config['recaptchav2_sitekey'] : '',
			'RECAPTCHAV2_THEME'			=> isset($this->config['recaptchav2_theme']) ? $this->config['recaptchav2_theme'] : '',

			'S_RECAPTCHAV2_AVAILABLE'	=> self::is_available(),
		));
		
		return '@numeric_recaptchav2/captcha_recaptchav2.html';
	}

	function get_demo_template($id)
	{
		return $this->get_template();
	}

	function get_hidden_fields()
	{
		$hidden_fields = array();

		// this is required for posting.php - otherwise we would forget about the captcha being already solved
		if ($this->solved)
		{
			$hidden_fields['confirm_code'] = $this->code;
		}
		$hidden_fields['confirm_id'] = $this->confirm_id;
		return $hidden_fields;
	}

	function uninstall()
	{
		$this->garbage_collect(0);
	}

	function install()
	{
		return;
	}

	function validate()
	{
		if (!parent::validate())
		{
			return false;
		}
		else
		{
			if ($this->response == null || strlen($this->response) == 0)
			{
				return $this->user->lang['RECAPTCHAV2_MISSING'];
			}
			
			include_once __DIR__ . '/bundle.php';
			
			$recaptcha = new \numeric\recaptchav2\ReCaptcha($this->config['recaptchav2_seckey']);
			
			$resp = $recaptcha->verify($this->response, $this->user->ip);
			
			if ($resp->isSuccess())
			{
				$this->solved = true;
				return false;
			}
			else
			{
				$this->solved = false;
				
				return ($resp->getErrorCodes()[0]) ? $this->user->lang['RECAPTCHAV2_MISSING'] : $this->user->lang['RECAPTCHA_INCORRECT'];
			}
		}
	}
}
