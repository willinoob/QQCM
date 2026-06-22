<?php
$id = mysqli_connect("localhost", "root", "root", "QQCM");
if (!$id) {
	die('DB connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($id, "utf8");
?>