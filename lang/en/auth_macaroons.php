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
 * Strings for component 'auth_macaroons', language 'en'.
 *
 * @package   auth_macaroons
 * @copyright 2017 onwards Brendan Abolivier
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_macaroonsdescription'] = 'Macaroons usage for authentication';
$string['pluginname'] = 'Macaroons';
$string['cookie_name_label'] = 'Cookie name';
$string['cookie_name_help'] = 'Name of the cookie your macaroon is located in.';
$string['secret_label'] = 'Secret';
$string['secret_help'] = 'The secret your macaroon was signed with.';
$string['identifier_format_label'] = 'Identifier format';
$string['identifier_format_help'] = 'Your Macaroon\'s identifier format. Available placeholders are {{username}}, {{firstname}}, {{lastname}}. Elements must me delimited with semicolons (";").<br />eg: {{firstname}};{{lastname}}';
$string['email_config_label'] = 'E-mail template';
$string['email_config_help'] = 'Template for emails. Available placeholders are {{firstname}} and {{lastname}}.<br />eg: {{firstname}}.{{lastname}}@company.tld';
$string['caveat1_condition_label'] = 'First caveat condition (optional)';
$string['caveat1_condition_help'] = 'The condition in your macaroon\'s first caveat.';
$string['caveat2_condition_label'] = 'Second caveat condition (optional)';
$string['caveat2_condition_help'] = 'The condition in your macaroon\'s second caveat';
$string['caveat3_condition_label'] = 'Third caveat condition (optional)';
$string['caveat3_condition_help'] = 'The condition in your macaroon\'s third caveat';
