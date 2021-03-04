<?php
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

	// List from lib/upgradelib.php:upgrade_stale_php_files_present()
    $someexamplesofremovedfiles = array(
        // Removed in 3.10.
        '/grade/grading/classes/privacy/gradingform_provider.php',
        '/lib/coursecatlib.php',
        '/lib/form/htmleditor.php',
        '/message/classes/output/messagearea/contact.php',
		/*
        // Removed in 3.9.
        '/course/classes/output/modchooser_item.php',
        '/course/yui/build/moodle-course-modchooser/moodle-course-modchooser-min.js',
        '/course/yui/src/modchooser/js/modchooser.js',
        '/h5p/classes/autoloader.php',
        '/lib/adodb/readme.txt',
        '/lib/maxmind/GeoIp2/Compat/JsonSerializable.php',
        // Removed in 3.8.
        '/lib/amd/src/modal_confirm.js',
        '/lib/fonts/font-awesome-4.7.0/css/font-awesome.css',
        '/lib/jquery/jquery-3.2.1.min.js',
        '/lib/recaptchalib.php',
        '/lib/sessionkeepalive_ajax.php',
        '/lib/yui/src/checknet/js/checknet.js',
        '/question/amd/src/qbankmanager.js',
        // Removed in 3.7.
        '/lib/form/yui/src/showadvanced/js/showadvanced.js',
        '/lib/tests/output_external_test.php',
        '/message/amd/src/message_area.js',
        '/message/templates/message_area.mustache',
        '/question/yui/src/qbankmanager/build.json',
        // Removed in 3.6.
        '/lib/classes/session/memcache.php',
        '/lib/eventslib.php',
        '/lib/form/submitlink.php',
        '/lib/medialib.php',
        '/lib/password_compat/lib/password.php'
		*/
		);

/**
 * Check whether the given files exists on the given web site.
 * @param $ch Curl handle.
 * @param $wwwroot URL of the site, for example "https://moodle.example.com".
 * @param $files Array of files to check, for example:
 * array("lib/amd/src/search-input.js", "lib/jabber/XMPP/README.txt")
 * @return Associative array containing each file and the HTTP status, for
 * example array("lib/amd/src/search-input.js" => 200,
 * "lib/jabber/XMPP/README.txt" => 404)
 */
function file_check($ch, $wwwroot, $files) {
	$http_statuses = array();
	
	foreach ($files as $file) {
		$url = "$wwwroot/$file";

		curl_setopt($ch, CURLOPT_URL, $url);
		if (!curl_exec($ch)) {
			$http_statuses[$file] = curl_error($ch);
		} else {
			$http_statuses[$file] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
	}

	return $http_statuses;
}

/**
 * For a given Moodle version determine if files are still present from
 * previous versions.
 * @param $wwwroot URL of the Moodle site, for example:
 * "https://moodle.example.com".
 * @param $ver Moodle major version number without period, for example "310"
 * for Moodle 3.10.
 * @return Array with two elements 'old' and 'stale', each of which is an array
 * of files (or directories) and the corresponding HTTP status (200, 404,
 * etc.).  For example:
 *   array(
 *     'old' => array(
 *       'lib/jabber/XMPP/README.txt' => 200
 *     ),
 *     'stale' => array(
 *       'lib/coursecatlib.php' => 404
 *     )
 */
function softac_check($wwwroot, $ver) {
	global $someexamplesofremovedfiles;
	$old_files = array();
	$fh = fopen("./$ver", 'r');

	while ($line = fgets($fh)) {
		$old_files[] = trim($line);
	}

	$results = array();
	$ch = curl_init();
	
	 // Use HEAD so we don't actually transfer the file (via
	 // https://stackoverflow.com/a/770200), we're just interested in the
	 // 200 OK (file exists) or 404 Not Found in the header.
	curl_setopt($ch, CURLOPT_NOBODY, true);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);

	 // Check for "old" files.  This is a list of files common to all Moodle
	 // versions the current version could be upgraded from (e.g. Moodle 3.5 to
	 // 3.9 for Moodle 3.10) but that have been removed in that target version.
	 // None of these should be present.
	$results['old'] = file_check($ch, $wwwroot, $old_files);

	 // Check for "stale" files, the subset of files Moodle checks do not exist
	 // before upgrade can proceed.  If all the "old" files get 404 responses
	 // then there's probably no actual point also doing this check.
	$results['stale'] = file_check($ch, $wwwroot, $someexamplesofremovedfiles);

	curl_close($ch);
	return $results;
}
