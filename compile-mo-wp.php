<?php
/**
 * WordPress MO file compiler
 */

define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

// Path to PO file
$po_file = __DIR__ . '/languages/lilac-learning-manager-he_IL.po';
$mo_file = __DIR__ . '/languages/lilac-learning-manager-he_IL.mo';

// Check if PO file exists
if (!file_exists($po_file)) {
    die("PO file not found: " . $po_file);
}

// Initialize MO class
$mo = new MO();

// Import PO file
$result = $mo->import_from_file($po_file);

if (!$result) {
    die("Failed to parse PO file");
}

// Ensure languages directory exists
if (!file_exists(dirname($mo_file))) {
    mkdir(dirname($mo_file), 0755, true);
}

// Export to MO file
if ($mo->export_to_file($mo_file)) {
    echo "Successfully compiled MO file to: " . $mo_file . "\n";
} else {
    die("Failed to save MO file to: " . $mo_file);
}

echo "Done!\n";
