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

			if (isset($_POST["parent"])) {
				if ($_POST["parent"]) {
					$parent = (int)$_POST["parent"];
				}
				else {
					$parent = 0;
				}
			}
			else {
				$parent = 0;
			}

			if (!$database->query("INSERT INTO list (item, date, catid, latitude, longitude, parent) VALUES ('$item', '" . time() . "','$category','$mylat','$mylong', '$parent')")) {
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
			
			# Delete all children
			if( $result = $database->query("SELECT id, parent FROM list LIMIT 100") ) {
				$idmap = array();
				$delmap = array();
				while ($row = $result->fetch_assoc()) {
					$idmap[$row['id']] = $row['parent'];
				}

				$delmap = $delmap + find_children($idmap, $delete, 0);
			}
			else {
				echo "Query returned false: (" . $database->errno . ") " . $database->error;
			}

			$dellist = "";
			foreach ($delmap as $delid=>$rubbish) {
				$dellist .= "'$delid', ";
			}
			$dellist .= "'$delete'";

			if (!$database->query("DELETE FROM list WHERE id IN ($dellist)")) {
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

function new_category() {
	echo("<button class=\"accordion\" onclick=\"openclose('catmaster')\"><div class=\"tooltip\">\n");
	echo("<image src=\"img/add.svg\" width=40><span class=\"tooltiptext\">New Category</span></div></button>\n");
	echo("<form class=\"input\" id=\"catmaster\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\" method=\"post\">");
	echo("<input type=\"text\" size=20 name=\"new_cat\">");
	echo("<input type=\"submit\">");
	echo("</form>\n");
}

# Render a (hidden) form for entering a new item
function new_item_form($mycategory, $myparent, $myid) {
	echo ("<form class=\"input\" id=\"$myid\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\" method=\"post\">");
	echo ("<input type=\"text\" size=40 name=\"item\">\n");
	echo ("<input type=\"hidden\" name=\"category\" value=\"$mycategory\">\n");
	echo ("<input type=\"hidden\" name=\"parent\" value=\"$myparent\">\n");
	echo ("<input class=\"mylat\" type=\"hidden\" name=\"latitude\">\n");
	echo ("<input class=\"mylong\" type=\"hidden\" name=\"longitude\">\n");
	echo ("<button onclick=\"doForm('$myid')\" type=\"button\">Submit</button>\n");
	echo ("</form>\n");
}

# Render category headers and populate them
function pop_list($database) {
	if( $result = $database->query("SELECT id, name FROM category LIMIT 100") ) {
		while ($row = $result->fetch_assoc()) {
			# If there are any matching list items, render it.
			if( $other_result = $database->query("SELECT catid FROM list WHERE catid='" . $row['id'] . "' LIMIT 100") ) {
				if ($other_result->num_rows > 0) {
					echo("<div class=\"category\">" . $row['name'] . "</div>\n");
					echo("<div class=\"catbuttons\"><form class=\"delcat\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"");
					echo("method=\"post\"><input type=\"hidden\" name=\"delcat\" value=\"" . $row['id'] . "\"><div class=\"tooltip\">\n");
					echo("<input type=\"image\" alt=\"delete\" src=\"img/cancel.svg\" onclick=\"return confirm('Are you sure?')\" width=20>\n");
					echo("<span class=\"tooltiptext\">Delete category</span></div></form>\n");
					echo("<div class=\"tooltip\"><button class=\"accordion\" onclick=\"openclose('cat" . $row['id'] . "')\">\n");
					echo("<image src=\"img/add.svg\" width=20></button><span class=\"tooltiptext\">New Item</span></div>\n");
					new_item_form($row['id'], 0, "cat" . $row['id']);
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

# For an assoc array mapping ID to Parent ID and a given ID, find children, recursively
function find_children($map, $parent_id, $depth) {
	$result = array();
	foreach($map as $row_id => $row_parent_id)
	{
		if ( $row_parent_id == $parent_id )
		{
			$result[ $row_id ] = $depth;
			# Not using array_merge here because it destroys numeric associative arrays
			$result = $result + find_children( $map, $row_id, $depth + 1 );
		}
	}
	return $result;
}

# Populate a category table from the database
function pop_cat($database, $category) {
	if( $result = $database->query("SELECT id, parent FROM list WHERE catid='$category' LIMIT 100") ) {
		$idmap = array();
		$depthmap = array();
		while ($row = $result->fetch_assoc()) {
			$idmap[$row['id']] = $row['parent'];
		}

		$depthmap = $depthmap + find_children($idmap, 0, 0);
	}
	else {
		echo "Query returned false: (" . $database->errno . ") " . $database->error;
	}

	if( $result = $database->query("SELECT id, item, date, latitude, longitude, parent FROM list WHERE catid='$category' LIMIT 100") ) {

		echo("<table>");
		while ($myrow = $result->fetch_assoc()) {
			$master[] = $myrow;
		}

		foreach ($depthmap as $row_id=>$depth) {
			foreach ($master as $myrow) {
				if ($myrow['id'] == $row_id) {
					$row = $myrow;
					break;
				}
			}

			echo("<tr><td class=\"depth$depth\">" . $row['item'] . "</td><td class=\"date\">" . $row['date'] . "</td>");
			echo("<td><div class=\"tooltip\"><button class=\"accordion\" onclick=\"openclose('item" . $row['id'] . "')\">\n");
			echo("<image src=\"img/chat.svg\" width=20></button><span class=\"tooltiptext\">Reply</span></div></td>\n");
			echo("<td><a class=\"tooltip\" target=\"_blank\" href=\"http://maps.google.com/maps?z=12&t=m&q=loc:" . $row['latitude'] . "+" . $row['longitude'] . "\">");
			echo("<image src=\"img/map.svg\" width=20><span class=\"tooltiptext\">Location submitted</span></a></td>");
			echo("<td><form class=\"tooltip\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"");
			echo("method=\"post\"><input type=\"hidden\" name=\"delete\" value=\"" . $row['id'] . "\">");
			echo("<input type=\"image\" alt=\"delete\" src=\"img/cancel.svg\" width=20><span class=\"tooltiptext\">Delete item</span></form></td>\n");
			echo("</tr><tr><td class=\"hidden\">\n");
			new_item_form($category, $row['id'], "item" . $row['id']);
			echo("</td></tr>\n");
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
