<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Authentication plugin: Macaroons
 *
 * Macaroons: Cookies with Contextual Caveats for Decentralized Authorization
 *
 * @package auth_macaroons
 * @author Brendan Abolivier
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Macaroon.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Caveat.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Packet.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Utils.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Verifier.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Exceptions/CaveatUnsatisfiedException.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Exceptions/InvalidMacaroonKeyException.php');
require_once($CFG->dirroot.'/auth/macaroons/Macaroons/Exceptions/SignatureMismatchException.php');

use Macaroons\Macaroon;
use Macaroons\Verifier;


/**
 * Plugin for no authentication.
 */
class auth_plugin_macaroons extends auth_plugin_base {

	/*
	* The name of the component. Used by the configuration.
	*/
	const COMPONENT_NAME = 'auth_macaroons';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->authtype = 'macaroons';
		$this->config = get_config(self::COMPONENT_NAME);
	}

	/**
	 * Old syntax of class constructor. Deprecated in PHP7.
	 *
	 * @deprecated since Moodle 3.1
	 */
	public function auth_plugin_macaroons() {
		debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
		self::__construct();
	}

	/* Login page hook
	*
	* Called before displaying the login form, is used to authenticate the user
	* and bypass the form.
	*/
	function loginpage_hook() {
		global $DB, $login, $CFG;

		if(!empty($_COOKIE[$this->config->cookie_name])) {
			try {
				// Getting the macaroon from the cookie it's stored in
				$m = Macaroon::deserialize($_COOKIE[$this->config->cookie_name]);

				$callbacks = array();

				// Defining the callbacks according to the plugin's configuration
				// in order to check all caveats
				if(!empty($this->config->caveat1_condition)) {
					array_push($callbacks, function($a) {
						return !strcmp($a, $this->config->caveat1_condition);
					});
				}
				if(!empty($this->config->caveat2_condition)) {
					array_push($callbacks, function($a) {
						return !strcmp($a, $this->config->caveat2_condition);
					});
				}
				if(!empty($this->config->caveat3_condition)) {
					array_push($callbacks, function($a) {
						return !strcmp($a, $this->config->caveat3_condition);
					});
				}

				$v = new Verifier();
				$v->setCallbacks($callbacks);

				// This will check both the signature and the caveats. Both must be OK
				// in order to continue
				if($v->verify($m, $this->config->secret)) {
					$identifier = explode(";", $m->getIdentifier());
					$parsed_id = $this->parse_identifier($identifier);
					if(empty($parsed_id["username"])) {
						$login = $parsed_id["firstname"].$parsed_id["lastname"];
					} else {
						$login = $parsed_id["username"];
					}

					// Checking if the user is accepted by at least one authentication
					// method (ours should accept it), and retrieving the user's class
					// This will create the user if it doesn't exist
					$user = authenticate_user_login($login, null);

					if($user) {
						if(!empty($parsed_id["firstname"])) {
							$user->firstname = $parsed_id["firstname"];
						}
						if(!empty($parsed_id["lastname"])) {
							$user->lastname = $parsed_id["lastname"];
						}

						// Generating the user's e-mail address according
						// to its name and the config's template
						$placeholders[0] = "/{{firstname}}/";
						$placeholders[1] = "/{{lastname}}/";
						$user->email = preg_replace($placeholders, [
							$parsed_id["firstname"],
							$parsed_id["lastname"]
						], $this->config->email_config);
						// Register modifications in DB, and logging the user in
						$DB->update_record('user', $user);
						complete_user_login($user);
						// Authentication is OK, let's redirect the user out of
						// the login page
						redirect($CFG->wwwroot);
					}
				}
			} catch(Exception $e) {
				// We currently do nothing with exceptions
				$message = $e->getMessage();
			}
		}
	}


	/*
	* Parses the macaroon identifier based on the user's config
	*
	* @param array $identifier The macaroon identifier split based on the separator
	* @return array A map linking a field with an user value
	*/
	function parse_identifier($identifier) {
		$placeholders = explode(";", $this->config->identifier_format);

		$parsed_id = array();

		// Check if the identifier has the same number of fields as configured
		if(sizeof($placeholders) != sizeof($identifier)) {
			// Returning an empty array as the return value is expected to be
			// an array
			return $parsed_id;
		}

		// Filling the fields
		if(is_numeric($index = array_search("{{username}}", $placeholders))) {
			$parsed_id["username"] = $identifier[$index];
		}
		if(is_numeric($index = array_search("{{firstname}}", $placeholders))) {
			$parsed_id["firstname"] = $identifier[$index];
		}
		if(is_numeric($index = array_search("{{lastname}}", $placeholders))) {
			$parsed_id["lastname"] = $identifier[$index];
		}

		return $parsed_id;
	}

	/**
	 * Returns true if the username and password work or don't exist and false
	 * if the user exists and the password is wrong.
	 *
	 * @param string $username The username
	 * @param string $password The password
	 * @return bool Authentication success or failure.
	 */
	function user_login ($username, $password) {
		global $login;
		if($login == $username) {
			return true;
		}
		return false;
	}

	/**
	 * Updates the user's password.
	 *
	 * called when the user password is updated.
	 *
	 * @param  object  $user		User table object
	 * @param  string  $newpassword Plaintext password
	 * @return boolean result
	 *
	 */
	function user_update_password($user, $newpassword) {
		$user = get_complete_user_data('id', $user->id);
		// This will also update the stored hash to the latest algorithm
		// if the existing hash is using an out-of-date algorithm (or the
		// legacy md5 algorithm).
		return update_internal_user_password($user, $newpassword);
	}

	function prevent_local_passwords() {
		return false;
	}

	/**
	 * Returns true if this authentication plugin is 'internal'.
	 *
	 * @return bool
	 */
	function is_internal() {
		return false;
	}

	/**
	 * Returns true if this authentication plugin can change the user's
	 * password.
	 *
	 * @return bool
	 */
	function can_change_password() {
		return true;
	}

	/**
	 * Returns the URL for changing the user's pw, or empty if the default can
	 * be used.
	 *
	 * @return moodle_url
	 */
	function change_password_url() {
		return null;
	}

	/**
	 * Returns true if plugin allows resetting of internal password.
	 *
	 * @return bool
	 */
	function can_reset_password() {
		return true;
	}

	/**
	 * Returns true if plugin can be manually set.
	 *
	 * @return bool
	 */
	function can_be_manually_set() {
		return true;
	}

	/**
	 * Prints a form for configuring this authentication plugin.
	 *
	 * This function is called from admin/auth.php, and outputs a full page with
	 * a form for configuring this plugin.
	 *
	 * @param array $page An object containing all the data for this page.
	 */
	function config_form($config, $err, $user_fields) {
		include "config.html";
	}

	/**
	 * Processes and stores configuration data for this authentication plugin.
	 */
	function process_config($config) {
		if(!isset($config->cookie_name)) {
			$config->cookie_name = 'das-macaroon';
		}
		if(!isset($config->secret)) {
			$config->secret = 'pocsecret';
		}
		if(!isset($config->identifier_format)) {
			$config->identifier_format = '{{firstname}};{{lastname}}';
		}
		if(!isset($config->email_config)) {
			$config->email_config = '{{firstname}}.{{lastname}}@company.tld';
		}
		// Caveats
		if(!isset($config->caveat1_condition)) {
				$config->caveat1_condition = '';
		}
		if(!isset($config->caveat2_condition)) {
				$config->caveat2_condition = '';
		}
		if(!isset($config->caveat3_condition)) {
				$config->caveat3_condition = '';
		}


		set_config('cookie_name', $config->cookie_name, self::COMPONENT_NAME);
		set_config('secret', $config->secret, self::COMPONENT_NAME);
		set_config('identifier_format', $config->identifier_format, self::COMPONENT_NAME);
		set_config('email_config', $config->email_config, self::COMPONENT_NAME);
		// Caveats
		set_config('caveat1_condition', $config->caveat1_condition, self::COMPONENT_NAME);
		set_config('caveat2_condition', $config->caveat2_condition, self::COMPONENT_NAME);
		set_config('caveat3_condition', $config->caveat3_condition, self::COMPONENT_NAME);

		return true;
	}

	function is_synchronised_with_external() {
		return false;
	}
}


