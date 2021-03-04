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
?>
<!DOCTYPE html><html lang=en-GB>
	<head><title>Check for old Moodle files</title></head>
	<body>
<?php
require_once('softac_check.php');
?>
<h1>Check for old Moodle files</h1>
<p>When upgrading Moodle only the new version's source code files should be present. Before upgrade Moodle checks for some of the old version's files before proceeding. Some installers (Softaculous) remove only the checked files and leave behind all other old files.</p>
<p>This checker looks for the files that Moodle checks for and then checks for other files that should not be present. If all rows in the <q>stale</q> list are 404 but one or more rows in the <q>old</q> list are 200 then files from the previous version are still present. This may be adversely affecting your Moodle site's functionality. Follow <a href="https://docs.moodle.org/310/en/Upgrading#Standard_install_package">the upgrade steps</a> to resolve this.</p>
<form action="./" method="GET">
<p><label for="wwwroot">Moodle site:</label><input name="wwwroot">
<label for="ver">Version:</label><select name="ver">
<option value="310" selected>3.10</option>
</select>
<input type="submit">
</form>
<?php

if (isset($_GET['wwwroot']) && isset($_GET['ver'])) {
	$results = softac_check($_GET['wwwroot'], $_GET['ver']);
?>
	<h1><q>Stale</q> files</h1>
	<p>Files that Moodle checks for.</p>
	<table>
		<tr><th>File/dir</th><th>Status</th></tr>
<?php
	$stalefilepresent = false;

	foreach ($results['stale'] as $file => $status) {
		echo "<tr><td>$file</td><td>$status</td></tr>" . PHP_EOL;

		if ($status != 404) {
			$stalefilepresent = true;
		}
	}

	echo "</table>" . PHP_EOL;

	if ($stalefilepresent) {
		echo "<p>Stale file present, this is unexpected!</p>" . PHP_EOL;
	}
?>

	<h1>Other old files</h1>
	<p>Other old files which should not be present.</p>
	<table>
		<tr><th>File/dir</th><th>Status</th></tr>
<?php
	$old_file_count = 0;

	foreach ($results['old'] as $file => $status) {
		echo "<tr><td>$file</td><td>$status</td></tr>" . PHP_EOL;

		if ($status == 200) {
			$old_file_count++;
		}
	}

	echo "</table>" . PHP_EOL;
	echo "<p>$old_file_count old file(s) present.</p>" . PHP_EOL;

	if ($old_file_count > 0) {
		echo "<p>Files from the previous Moodle version are still present.</p>" . PHP_EOL;
	}
}
?>
	</body>
</html>
