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

			if (isset($_POST["category"])) {
				if ($_POST["category"]) {
					$category = (int)$_POST["category"];
				}
				else {
					$category = 1;
				}
			}
			else {
				$category = 1;
			}

			if (!$database->query("INSERT INTO list (item, date, catid, latitude, longitude) VALUES ('$item', '" . time() . "','$category','$mylat','$mylong')")) {
				echo "Insert failed: (" . $database->errno . ") " . $database->error;
			}
		}
		
		# If there's a new category, validate it and add it.
		if (isset($_POST["new_cat"])) {
			# Validate category
			$new_cat = trim($_POST["new_cat"]);
			$new_cat = substr($new_cat, 0, 50);
			$new_cat = stripslashes($new_cat);
			$new_cat = htmlspecialchars($new_cat);
			$new_cat = $database->real_escape_string($new_cat);
			
			if (!$database->query("INSERT INTO category (name) VALUES ('$new_cat')")) {
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
		
		# If there's a category to delete, take a deep breath and delete it.
		if (isset($_POST["delcat"])) {
			# Validate delete
			$delcat = (int)$_POST["delcat"];
			
			# Delete all the list items with this category
			if (!$database->query("DELETE FROM list WHERE catid = $delcat")) {
				echo "Delete failed: (" . $database->errno . ") " . $database->error;
			}

			# Delete the category
			if (!$database->query("DELETE FROM category WHERE id = $delcat")) {
				echo "Delete failed: (" . $database->errno . ") " . $database->error;
			}
		}
	}
}

# Populate a category drop-down from the database
function pop_cat_select($database) {
	if( $result = $database->query("SELECT id, name FROM category LIMIT 100") ) {
		if ($result->num_rows > 0) {
			echo ("<form class=\"input\" id=\"main_input\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\" method=\"post\">\n");
			echo ("New item: <input type=\"text\" size=40 name=\"item\">\n");
			echo ("Category:"); 
			echo("<select name=\"category\">\n");
			while ($row = $result->fetch_assoc()) {
				echo("<option value=\"" . $row['id'] . "\">" . $row['name'] . "</option>\n");
			}
			echo("</select>\n");
			echo("<input id=\"mylat\" type=\"hidden\" name=\"latitude\">\n");
			echo("<input id=\"mylong\" type=\"hidden\" name=\"longitude\">\n");
			echo("<button onclick=\"getLocation()\" type=\"button\">Submit</button>\n");
			echo("</form>\n");
		}
	}
	else {
		echo "Query returned false: (" . $database->errno . ") " . $database->error;
	}
}

# Render category headers and populate them
function pop_list($database) {
	if( $result = $database->query("SELECT id, name FROM category LIMIT 100") ) {
		while ($row = $result->fetch_assoc()) {
			# If there are any matching list items, render it.
			if( $other_result = $database->query("SELECT catid FROM list WHERE catid='" . $row['id'] . "' LIMIT 100") ) {
				if ($other_result->num_rows > 0) {
					echo("<div class=\"category\">" . $row['name'] . "\n");
					echo("<form class=\"delcat\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"");
					echo("method=\"post\"><input type=\"hidden\" name=\"delcat\" value=\"" . $row['id'] . "\">");
					echo("<input type=\"image\" alt=\"delete\" src=\"img/cancel.svg\" onclick=\"return confirm('Are you sure?')\" width=20></form>\n");
					echo("</div>\n");
					pop_cat($database, $row['id']);
				}
			}
			else {
				echo "Query returned false: (" . $database->errno . ") " . $database->error;
			}
		}
	}
	else {
		echo "Query returned false: (" . $database->errno . ") " . $database->error;
	}
}

# Populate a category table from the database
function pop_cat($database, $category) {
	if( $result = $database->query("SELECT id, item, date, latitude, longitude FROM list WHERE catid='$category' LIMIT 100") ) {

		echo("<table>");
		while ($row = $result->fetch_assoc()) {
			echo("<tr><td>" . $row['item'] . "</td><td class=\"date\">" . $row['date'] . "</td>");
			echo("<td><a target=\"_blank\" href=\"http://maps.google.com/maps?z=12&t=m&q=loc:" . $row['latitude'] . "+" . $row['longitude'] . "\">");
			echo("<image src=\"img/map.svg\" width=20></a></td>");
			echo("<td><form action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"");
			echo("method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"" . $row['id'] . "\">");
			echo("<input type=\"image\" alt=\"delete\" src=\"img/cancel.svg\" width=20></form></td></tr>\n");
		}
		echo("</table>\n");
	}
	else {
		echo "Query returned false: (" . $database->errno . ") " . $database->error;
	}
}

function close_db($database) {
	$database->close();
}

?>
