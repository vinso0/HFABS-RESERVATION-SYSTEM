<?php

require_once __DIR__ . ('/../config/config.php');
require_once __DIR__ . ('/Router.php');
require_once __DIR__ . ('/Controller.php');
require_once __DIR__ . ('/Database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}