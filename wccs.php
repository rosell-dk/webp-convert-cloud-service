<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require 'vendor/autoload.php';

use WebPConvert\WebPConvert;

$options = Spyc::YAMLLoad('wccs.yaml');



//print_r($_POST);
//print_r($_FILES);

$uploaddir = realpath('./') . '/' . $options['upload-dir'] . '/';
$uploadfile = $uploaddir . $_FILES['file']['name'];

createWritableFolder($uploadfile);

if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    // File is valid, and was successfully uploaded

    $source = $uploadfile;
    $destination = $uploadfile . '.webp';

    try {
        if (WebPConvert::convert($source, $destination, $options['convert-options'])) {
            header('Content-type: application/octet-stream');
            echo file_get_contents($destination);

            // TODO: delete file again...
        }
    } catch (\Exception $e) {
        echo 'failed!';
        echo $e->getMessage();
    }
} else {
    // Possible file upload attack!
}

function createWritableFolder($filePath)
{
    $folder = pathinfo($filePath, PATHINFO_DIRNAME);
    if (!file_exists($folder)) {
        // TODO: what if this is outside open basedir?
        // see http://php.net/manual/en/ini.core.php#ini.open-basedir

        // First, we have to figure out which permissions to set.
        // We want same permissions as parent folder
        // But which parent? - the parent to the first missing folder

        $parentFolders = explode('/', $folder);
        $poppedFolders = [];

        while (!(file_exists(implode('/', $parentFolders))) && count($parentFolders) > 0) {
            array_unshift($poppedFolders, array_pop($parentFolders));
        }

        // Retrieving permissions of closest existing folder
        $closestExistingFolder = implode('/', $parentFolders);
        $permissions = fileperms($closestExistingFolder) & 000777;

        // Trying to create the given folder
        // Notice: mkdir emits a warning on failure. It would be nice to suppress that, if possible
        if (!mkdir($folder, $permissions, true)) {
            throw new \Exception('Failed creating folder: ' . $folder);
        }


        // `mkdir` doesn't respect permissions, so we have to `chmod` each created subfolder
        foreach ($poppedFolders as $subfolder) {
            $closestExistingFolder .= '/' . $subfolder;
            // Setting directory permissions
            chmod($folder, $permissions);
        }
    }

    // Checks if there's a file in $filePath & if writing permissions are correct
    if (file_exists($filePath) && !is_writable($filePath)) {
        throw new \Exception('Cannot overwrite ' . basename($filePath) . ' - check file permissions.');
    }

    // There's either a rewritable file in $filePath or none at all.
    // If there is, simply attempt to delete it
    if (file_exists($filePath) && !unlink($filePath)) {
        throw new \Exception('Existing file cannot be removed: ' . basename($filePath));
    }

    return true;
}
