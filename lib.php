<?php
# This began life as a list of 140-character text fields 
# With careful input validation
# Persisted in a mysql table

function open_db() {
	$db_username="list";
	$db_password="not2secure";
	$db_host="localhost";
	$db_name="listdb";
	$link = new mysqli($db_host, $db_username, $db_password, $db_name);
	if ($link->connect_errno) {
    		echo "Failed to connect to MySQL: (" . $link->connect_errno . ") " . $link->connect_error;
	}
	return $link;
}

# Validate input, stick it in the table
function process_form($database) {
	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		# If there's a new item, insert it.
		if (isset($_POST["item"])) {
			# Validate item
			$item = trim($_POST["item"]);
			$item = substr($item, 0, 200);
			$item = stripslashes($item);
			$item = htmlspecialchars($item);
			$item = $database->real_escape_string($item);

			if (isset($_POST["latitude"])) {
				if ($_POST["latitude"]) {
					$mylat = (float)$_POST["latitude"];
				}
				else {
					$mylat = "39.0458";
				}
			}
			else {
				$mylat = "39.0458";
			}
			if (isset($_POST["longitude"])) {
				if ($_POST["longitude"]) {
					$mylong = (float)$_POST["longitude"];
				}
				else {
					$mylong = "76.6413";
				}
			}
			else {
				$mylong = "76.6413";
			}

			if (!$database->query("INSERT INTO list (item, date, latitude, longitude) VALUES ('$item', '" . time() . "','$mylat','$mylong')")) {
				echo "Insert failed: (" . $database->errno . ") " . $database->error;
			}
		}
		
		# If there's a delete request, delete it.
		if (isset($_POST["delete"])) {
			# Validate delete
			$delete = (int)$_POST["delete"];
			
			if (!$database->query("DELETE FROM list WHERE id = $delete")) {
				echo "Delete failed: (" . $database->errno . ") " . $database->error;
			}
		}
	}
}


# Populate a table or UL from the database
function populate($database) {
	if( $result = $database->query("SELECT id, item, date, latitude, longitude FROM list LIMIT 100") ) {

		echo("<table>");
		while ($row = $result->fetch_assoc()) {
			echo("<tr><td>" . $row['item'] . "</td><td class=\"date\">" . $row['date'] . "</td>");
			echo("<td><a target=\"_blank\" href=\"http://maps.google.com/maps?z=12&t=m&q=loc:" . $row['latitude'] . "+" . $row['longitude'] . "\">");
			echo("<image src=\"img/map.svg\" width=20></a></td>");
			echo("<td><form action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"");
			echo("method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"" . $row['id'] . "\">");
			echo("<input type=\"image\" alt=\"delete\" src=\"img/cancel.svg\" width=20></form></td></tr>\n");
		}
		echo("</table>");
	}
	else {
		echo "Query returned false: (" . $database->errno . ") " . $database->error;
	}
}

function close_db($database) {
	$database->close();
}

?>
