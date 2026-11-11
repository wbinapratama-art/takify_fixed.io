<?php
require __DIR__ . '/bootstrap.php';
UserSession::logout();
redirect('login.php');