<?php

        
function connect() {
    $localhost = 'localhost';
    $password = '';
    $username = 'root';
    $db = 'ufhec_academic_system';

    $conn = new mysqli($localhost, $username, $password, $db);
    return $conn;
}

# CONFIGURATION




?>