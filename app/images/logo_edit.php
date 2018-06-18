<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('logo_add') || permission_exists('logo_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get logo id
	if (isset($_REQUEST["id"])) {
		$logo_uuid = check_str($_REQUEST["id"]);
	}

//get the form value and set to php variables
	if (count($_POST) > 0) {
		$logo_filename = check_str($_POST["logo_filename"]);
		$logo_filename_original = check_str($_POST["logo_filename_original"]);
		$logo_name = check_str($_POST["logo_name"]);
		$logo_description = check_str($_POST["logo_description"]);

		//clean the logo filename and name
		$logo_filename = str_replace(" ", "_", $logo_filename);
		$logo_filename = str_replace("'", "", $logo_filename);
		$logo_name = str_replace("'", "", $logo_name);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	//get logo uuid to edit
		$logo_uuid = check_str($_POST["logo_uuid"]);

	//check for all required data
		$msg = '';
		if (strlen($logo_filename) == 0) { $msg .= $text['label-edit-file']."<br>\n"; }
		if (strlen($logo_name) == 0) { $msg .= $text['label-edit-logo']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//update the database
	if ($_POST["persistformvar"] != "true") {
		if (permission_exists('logo_edit')) {
			//if file name is not the same then rename the file
				if ($logo_filename != $logo_filename_original) {
					rename($_SESSION['switch']['logos']['dir'].'/'.$_SESSION['domain_name'].'/'.$logo_filename_original, $_SESSION['switch']['logos']['dir'].'/'.$_SESSION['domain_name'].'/'.$logo_filename);
				}

			//update the database with the new data
				$sql = "update v_logos set ";
				$sql .= "domain_uuid = '".$domain_uuid."', ";
				$sql .= "logo_filename = '".$logo_filename."', ";
				$sql .= "logo_name = '".$logo_name."', ";
				$sql .= "logo_description = '".$logo_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."'";
				$sql .= "and logo_uuid = '".$logo_uuid."'";
				$db->exec(check_sql($sql));
				unset($sql);

			messages::add($text['message-update']);
			header("Location: logos.php");
			return;
		} //if (permission_exists('logo_edit')) {
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$logo_uuid = $_GET["id"];
		$sql = "select * from v_logos ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and logo_uuid = '".$logo_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$logo_filename = $row["logo_filename"];
			$logo_name = $row["logo_name"];
			$logo_description = $row["logo_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	$document['title'] = $text['title-edit'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' align='right'>\n";
	echo "<tr>\n";
	echo "<td nowrap='nowrap'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='logos.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<b>".$text['title-edit']."</b>\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-logo_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='logo_name' maxlength='255' value=\"$logo_name\">\n";
	echo "<br />\n";
	echo $text['description-logo']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-file_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='logo_filename' maxlength='255' value=\"$logo_filename\">\n";
	echo "    <input type='hidden' name='logo_filename_original' value=\"$logo_filename\">\n";
	echo "<br />\n";
	echo $text['message-file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Description\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='logo_description' maxlength='255' value=\"$logo_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='logo_uuid' value='".$logo_uuid."'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
