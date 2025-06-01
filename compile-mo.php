<?php
// Simple script to compile PO to MO file
$po_file = __DIR__ . '/languages/lilac-learning-manager-he_IL.po';
$mo_file = __DIR__ . '/languages/lilac-learning-manager-he_IL.mo';

// Check if PO file exists
if (!file_exists($po_file)) {
    die("PO file not found: " . $po_file);
}

// Create MO file
$mo = new MO();
$result = $mo->import_from_file($po_file);

if (!$result) {
    die("Failed to parse PO file");
}

// Save MO file
if ($mo->export_to_file($mo_file)) {
    echo "Successfully compiled MO file to: " . $mo_file . "\n";
} else {
    die("Failed to save MO file");
}
