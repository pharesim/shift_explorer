<?php
date_default_timezone_set('UTC');

if (DIRECTORY_SEPARATOR == '/') {
	define('DS','/');
}

if (DIRECTORY_SEPARATOR == '\\') {
	define('DS','\\');
}