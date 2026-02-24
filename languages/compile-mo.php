<?php
/**
 * Simple PO to MO Compiler
 * Run this script to compile .po files to .mo files
 *
 * Usage: php compile-mo.php
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

function po_to_mo($po_file, $mo_file) {
    $hash = array();
    $content = file_get_contents($po_file);

    // Parse PO file
    preg_match_all('/msgid\s+"(.*)"\s+msgstr\s+"(.*)"/sU', $content, $matches);

    for ($i = 0; $i < count($matches[1]); $i++) {
        $msgid = stripcslashes($matches[1][$i]);
        $msgstr = stripcslashes($matches[2][$i]);
        if ($msgid && $msgstr) {
            $hash[$msgid] = $msgstr;
        }
    }

    // Also handle multi-line strings
    preg_match_all('/msgid\s+""\s+"(.*)"\s+msgstr\s+""\s+"(.*)"/sU', $content, $matches2);
    for ($i = 0; $i < count($matches2[1]); $i++) {
        $msgid = stripcslashes($matches2[1][$i]);
        $msgstr = stripcslashes($matches2[2][$i]);
        if ($msgid && $msgstr) {
            $hash[$msgid] = $msgstr;
        }
    }

    if (empty($hash)) {
        echo "No translations found in $po_file\n";
        return false;
    }

    // Build MO file
    $offsets = array();
    $ids = '';
    $strings = '';

    foreach ($hash as $id => $str) {
        $offsets[] = array(
            strlen($ids), strlen($id),
            strlen($strings), strlen($str)
        );
        $ids .= $id . "\0";
        $strings .= $str . "\0";
    }

    $key_start = 28 + 8 * count($offsets);
    $value_start = $key_start + strlen($ids);

    $mo = pack('Iiiiiii',
        0x950412de,     // magic
        0,              // version
        count($offsets),// number of strings
        28,             // offset of original strings table
        28 + 8 * count($offsets) - 8 * count($offsets) + 8 * count($offsets), // actually 28 + count*8
        0,              // size of hash table
        0               // offset of hash table
    );

    // Correct calculation
    $mo = pack('I', 0x950412de);  // magic
    $mo .= pack('I', 0);          // version
    $mo .= pack('I', count($offsets));  // number of strings
    $mo .= pack('I', 28);         // offset of original strings table
    $mo .= pack('I', 28 + count($offsets) * 8);  // offset of translation strings table
    $mo .= pack('I', 0);          // size of hash table
    $mo .= pack('I', 0);          // offset of hash table

    // Original strings table (length, offset pairs)
    $strings_offset = 28 + count($offsets) * 16;
    foreach ($offsets as $offset) {
        $mo .= pack('II', $offset[1], $strings_offset + $offset[0]);
    }

    // Translation strings table (length, offset pairs)
    $trans_offset = $strings_offset + strlen($ids);
    foreach ($offsets as $offset) {
        $mo .= pack('II', $offset[3], $trans_offset + $offset[2]);
    }

    // Strings
    $mo .= $ids;
    $mo .= $strings;

    file_put_contents($mo_file, $mo);
    echo "Created: $mo_file (" . count($offsets) . " translations)\n";
    return true;
}

// Compile all PO files in current directory
$dir = __DIR__;
$po_files = glob($dir . '/*.po');

if (empty($po_files)) {
    echo "No .po files found in $dir\n";
    exit(1);
}

foreach ($po_files as $po_file) {
    $mo_file = preg_replace('/\.po$/', '.mo', $po_file);
    echo "Compiling: $po_file\n";
    po_to_mo($po_file, $mo_file);
}

echo "\nDone!\n";
