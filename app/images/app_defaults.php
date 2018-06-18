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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the logos directory doesn't exist then create it
	if (is_array($_SESSION['switch']['logos']) && strlen($_SESSION['switch']['logos']['dir']."/".$domain_name) > 0) {
		if (!is_readable($_SESSION['switch']['logos']['dir']."/".$domain_name)) { event_socket_mkdir($_SESSION['switch']['logos']['dir']."/".$domain_name,02770,true); }
	}

//process one time
	if ($domains_processed == 1) {

		//if base64, populate from existing logo files, then remove
			if (is_array($_SESSION['logos']['storage_type']) && $_SESSION['logos']['storage_type']['text'] == 'base64') {
				//get logos without base64 in db
					$sql = "select logo_uuid, domain_uuid, logo_filename ";
					$sql .= "from v_logos where logo_base64 is null or logo_base64 = '' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (is_array($result)) {
						foreach ($result as &$row) {
							$logo_uuid = $row['logo_uuid'];
							$logo_domain_uuid = $row['domain_uuid'];
							$logo_filename = $row['logo_filename'];
							//set logo directory
								$logo_directory = $_SESSION['switch']['logos']['dir'].'/'.$domain_name;
							//encode logo file (if exists)
								if (file_exists($logo_directory.'/'.$logo_filename)) {
									$logo_base64 = base64_encode(file_get_contents($logo_directory.'/'.$logo_filename));
									//update logo record with base64
										$sql = "update v_logos set ";
										$sql .= "logo_base64 = '".$logo_base64."' ";
										$sql .= "where domain_uuid = '".$logo_domain_uuid."' ";
										$sql .= "and logo_uuid = '".$logo_uuid."' ";
										$db->exec(check_sql($sql));
										unset($sql);
									//remove local logo file
										@unlink($logo_directory.'/'.$logo_filename);
								}
						}
					}
					unset($sql, $prep_statement, $result, $row);
			}
		//if not base64, decode to local files, remove base64 data from db
			else if (is_array($_SESSION['logos']['storage_type']) && $_SESSION['logos']['storage_type']['text'] != 'base64') {
				//get logos with base64 in db
					$sql = "select logo_uuid, domain_uuid, logo_filename, logo_base64 ";
					$sql .= "from v_logos where logo_base64 is not null ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (count($result) > 0) {
						foreach ($result as &$row) {
							$logo_uuid = $row['logo_uuid'];
							$logo_domain_uuid = $row['domain_uuid'];
							$logo_filename = $row['logo_filename'];
							$logo_base64 = $row['logo_base64'];
							//set logo directory
								$logo_directory = $_SESSION['switch']['logos']['dir'].'/'.$domain_name;
							//remove local file, if any
								if (file_exists($logo_directory.'/'.$logo_filename)) {
									@unlink($logo_directory.'/'.$logo_filename);
								}
							//decode base64, save to local file
								$logo_decoded = base64_decode($logo_base64);
								file_put_contents($logo_directory.'/'.$logo_filename, $logo_decoded);
								$sql = "update v_logos ";
								$sql .= "set logo_base64 = null ";
								$sql .= "where domain_uuid = '".$logo_domain_uuid."' ";
								$sql .= "and logo_uuid = '".$logo_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
						}
					}
					unset($sql, $prep_statement, $result, $row);
			}
	}

?>
