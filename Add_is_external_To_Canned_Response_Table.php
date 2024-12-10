<?php
// Ensure that this script only runs as part of an upgrade
if (!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

// Add new column to the canned_response table
function addNewColumnToCannedResponseTable() {
    $sql = "ALTER TABLE `canned_response` ADD `is_external` VARCHAR(255) NOT NULL DEFAULT ''";
    if (db_query($sql)) {
        echo "New column added successfully.";
    } else {
        echo "Failed to add new column.";
    }
}

// Run the function to add the new column
addNewColumnToCannedResponseTable();
?>
