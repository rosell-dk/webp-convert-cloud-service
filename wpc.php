<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require 'vendor/autoload.php';

use WebPConvert\WebPConvert;

const ERROR_SERVER_SETUP = 0;
const ERROR_NOT_ALLOWED = 1;

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

$configDir = __DIR__;

$parentFolders = explode('/', $configDir);
$poppedFolders = [];

while (!(file_exists(implode('/', $parentFolders) . '/wpc-config.yaml')) && count($parentFolders) > 0) {
    array_unshift($poppedFolders, array_pop($parentFolders));
}
if (count($parentFolders) == 0) {
    exitWithError(ERROR_SERVER_SETUP, 'wpc-config.yaml not found in any parent folders.');
}
$configFilePath = implode('/', $parentFolders) . '/wpc-config.yaml';

try {
    $options = \Spyc::YAMLLoad($configFilePath);
} catch (\Exception $e) {
    exitWithError(ERROR_SERVER_SETUP, 'Error parsing wpc-config.yaml.');
}

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

$uploaddir = $options['destination-dir'] ;
if ((substr($uploaddir, 0, 1) != '/')) {
    $uploaddir = realpath('./') . '/' . $options['destination-dir'];
}

if (!file_exists($uploaddir)) {
    // First, we have to figure out which permissions to set.
    // We want same permissions as parent folder
    // But which parent? - the parent to the first missing folder

    $parentFolders = explode('/', $uploaddir);
    $poppedFolders = [];

    while (!(file_exists(implode('/', $parentFolders))) && count($parentFolders) > 0) {
        array_unshift($poppedFolders, array_pop($parentFolders));
    }

    // Retrieving permissions of closest existing folder
    $closestExistingFolder = implode('/', $parentFolders);
    $permissions = fileperms($closestExistingFolder) & 000777;

    // Trying to create the given folder
    // Notice: mkdir emits a warning on failure. It would be nice to suppress that, if possible
    if (!mkdir($uploaddir, $permissions, true)) {
        exitWithError(ERROR_SERVER_SETUP, 'Destination folder does not exist and cannot be created: ' . $uploaddir);
    }

    // `mkdir` doesn't respect permissions, so we have to `chmod` each created subfolder
    foreach ($poppedFolders as $subfolder) {
        $closestExistingFolder .= '/' . $subfolder;
        // Setting directory permissions
        chmod($uploaddir, $permissions);
    }
}

if (!isset($_POST['hash'])) {
    exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Hash required, but missing');
}

$uploadfile = $uploaddir . '/' . $_FILES['file']['name'];

if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    // File is valid, and was successfully uploaded

    $source = $uploadfile;


    if (isset($options['access']['secret'])) {
         $hash = md5(md5_file($source) . $options['access']['secret']);

        if ($hash != $_POST['hash']) {
            exitWithError(ERROR_NOT_ALLOWED, 'Hash is incorrect. Perhaps the secrets does not match?. Hash was:' . $_POST['hash']);
        }
    }

    $destination = $uploadfile . '.webp';

    // Merge in options in $_POST, overwriting those in config.yaml
    $convertOptionsInPost = (array) json_decode($_POST['options']);
    $convertOptions = array_merge($options['webp-convert'], $convertOptionsInPost);

    try {
        if (WebPConvert::convert($source, $destination, $convertOptions)) {
            header('Content-type: application/octet-stream');
            echo file_get_contents($destination);

            unlink($source);
            unlink($destination);
        }
    } catch (\Exception $e) {
        echo 'failed!';
        echo $e->getMessage();
    }
} else {
    // Possible file upload attack!
}
