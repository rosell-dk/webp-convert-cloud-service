<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require 'vendor/autoload.php';

use WebPConvert\WebPConvert;

//define("ERROR_CONFIGURATION", 1);
const ERROR_SERVER_SETUP = 0;
const ERROR_NOT_ALLOWED = 1;


// TODO: Ensure that config.yaml will not be removed on composer update

// TODO: read up on https://getcomposer.org/doc/06-config.md

function exitWithError($errorCode, $msg)
{
    $returnObject = array(
        'success' => 0,
        'errorCode' => $errorCode,
        'errorMessage' => $msg,
    );
    echo json_encode($returnObject);
    exit;
}


try {
    $options = \Spyc::YAMLLoad('config.yaml');
} catch (\Exception $e) {
    exitWithError(ERROR_SERVER_SETUP, 'config.yaml not found. Copy config.yaml.example and edit it');
}

// TODO: read this: https://cloudinary.com/blog/file_upload_with_php
// https://www.tutorialspoint.com/php/php_file_uploading.htm
// https://www.sitepoint.com/file-uploads-with-php/ (read "security considerations")
// TODO: PHP upload limits etc

//print_r($_POST);
//print_r($_FILES);

//echo $_SERVER['REQUEST_METHOD'];

//echo $_SERVER['REMOTE_HOST'];
//exit;


if (isset($options['access']['allowed-ips']) && count($options['access']['allowed-ips']) > 0) {
    $ipCheckPassed = false;
    foreach ($options['access']['allowed-ips'] as $ip) {
        if ($ip == $_SERVER['REMOTE_ADDR']) {
            $ipCheckPassed = true;
            break;
        }
    }
    if (!$ipCheckPassed) {
        exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Not on IP whitelist');
    }
}

if (isset($options['access']['allowed-hosts']) && count($options['access']['allowed-hosts']) > 0) {
    $h = $_SERVER['REMOTE_HOST'];
    if ($h == '') {
        // Alternatively, we could catch the notice...
        exitWithError(ERROR_SERVER_SETUP, 'WPC is configured with allowed-hosts option. But the server is not set up to resolve host names. For example in Apache you will need HostnameLookups On inside httpd.conf. See also PHP documentation on gethostbyaddr().');
    }
    $hostCheckPassed = false;
    foreach ($options['access']['allowed-hosts'] as $hostName) {
        if ($hostName == $_SERVER['REMOTE_HOST']) {
            $hostCheckPassed = true;
            break;
        }
    }
    if (!$hostCheckPassed) {
        exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Hostname is not on whitelist');
    }
}

if (!isset($_POST['hash'])) {
    exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Hash required, but missing');
}
//exitWithError(ERROR_SERVER_SETUP, 'config.yaml not found');


$uploaddir = realpath('./') . '/' . $options['upload-dir'] . '/';
$uploadfile = $uploaddir . $_FILES['file']['name'];

createWritableFolder($uploadfile);

if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    // File is valid, and was successfully uploaded

    $source = $uploadfile;


    if (isset($options['access']['secret'])) {
         // $options['access']['secret']

        $hash = md5(md5_file($source) . $options['access']['secret']);

        if ($hash != $_POST['hash']) {
            exitWithError(ERROR_NOT_ALLOWED, 'Secrets does not match');
        }
    }

    $destination = $uploadfile . '.webp';

    // Merge in options in $_POST, overwriting those in config.yaml
    $convertOptionsInPost = (array) json_decode($_POST['options']);
//    print_r($_POST['options']);

//    print_r($convertOptionsInPost);

    $convertOptions = array_merge($options['webp-convert'], $convertOptionsInPost);
//print_r($options['webp-convert']);
    try {
        if (WebPConvert::convert($source, $destination, $convertOptions)) {
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
