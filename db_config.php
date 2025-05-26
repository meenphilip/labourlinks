<?php
// SQLite Database Configuration
define('DB_FILE', __DIR__.'/../db/labourlinks.db');

// Ensure database directory exists
if (!file_exists(dirname(DB_FILE))) {
    mkdir(dirname(DB_FILE), 0755, true);
}
?>