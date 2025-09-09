<?php
/**
 * Legacy connection file - now redirects to new database config
 * This maintains compatibility with existing includes
 */

// Include the new database configuration
require_once __DIR__ . '/config/database.php';
