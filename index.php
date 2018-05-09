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
		<script src="moment.js"></script>
		<meta name="format-detection" content="telephone=no">
	</head>
	<body onload="localize()">
		<div class="wrapper">
			<h1>A persistent list</h1>
			<?php new_category(); ?>
			<script>
				function openclose(label) {
					var panel = document.getElementById(label);

					if (panel.style.display === "block") {
					    panel.style.display = "none";
					} else {
					    panel.style.display = "block";
					}
				}

				var activeForm = false;

				function doForm(formid) {
					activeForm = document.getElementById(formid);

					if (navigator.geolocation) {
						navigator.geolocation.getCurrentPosition(showPosition);
					} else { 
						var latval = "39.0458";
						var longval = "76.6413";
					}
				}

				function showPosition(position) {
					var latval = position.coords.latitude;
					var longval = position.coords.longitude;

					latitudes = document.getElementsByClassName("mylat");
					for (i = 0; i < latitudes.length; i++) {
						latitudes[i].value = latval;
					}
					longitudes = document.getElementsByClassName("mylong");
					for (i = 0; i < longitudes.length; i++) {
						longitudes[i].value = longval;
					}

					activeForm.submit();
				}

				function localize() {
					fields = document.getElementsByClassName("date");
					for (i = 0; i < fields.length; i++) {
						var dt = moment.unix(fields[i].innerHTML);
						fields[i].innerHTML = dt.format('LLL');
					}
				}

			</script>
			<p><?php

pop_list($db);

close_db($db);

?></p>

<div class="attrib">Icons made by <a href="https://www.flaticon.com/authors/vectors-market" title="Vectors Market">Vectors Market</a> and <a href="https://www.flaticon.com/authors/maxim-basinski" title="Maxim Basinski">Maxim Basinski</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a> are licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>
	</body>
</html>
