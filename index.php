<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

ini_set("display_errors", 1);

include "lib.php";

$db = open_db();

process_form($db);
?>
<!DOCTYPE HTML>
<html>
	<head>
		<link async="async" href="list.css" media="all" rel="stylesheet" type="text/css" />
		<title>List</title>
	</head>
	<body onload="getLocation()">
		<div class="wrapper">
			<h1>A persistent list</h1>
			<form class="input" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
				New item: <input type="text" size=40 name="item">
				Category: <?php pop_cat_select($db); ?>
				<input id="mylat" type="hidden" name="latitude">
				<input id="mylong" type="hidden" name="longitude">
				<input type="submit">
			</form>
			<form class="input" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
				New category: <input type="text" size=20 name="new_cat">
				<input type="submit">
			</form>
			<script>
				var mylat = document.getElementById("mylat");
				var mylong = document.getElementById("mylong");
				
				function getLocation() {
				    if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(showPosition);
				    } else { 
					mylat.value = "39.0458";
					mylong.value = "76.6413";
				    }
				}

				function showPosition(position) {
				    mylat.value = position.coords.latitude;
				    mylong.value = position.coords.longitude;

				    localize();
				}

				function localize() {
					fields = document.getElementsByClassName("date");
					for (i = 0; i < fields.length; i++) {
						var dt = new Date(1000 * fields[i].innerHTML);
						fields[i].innerHTML = dt.toLocaleString();
					}
				}

			</script>
			<p><?php

pop_list($db);

close_db($db);

?></p>

<div class="attrib">Icons made by <a href="https://www.flaticon.com/authors/vectors-market" title="Vectors Market">Vectors Market</a> and <a href="https://www.flaticon.com/authors/maxim-basinski" title="Maxim Basinski">Maxim Basinski</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a> is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>
	</body>
</html>
