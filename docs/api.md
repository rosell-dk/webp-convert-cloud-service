Conversion is done by requesting the script file you created over http(s) (ie `https://wpc.example.com/wpc.php`).
The following is expected in the POST:


## Get the api version
Getting api version bypasses the access-checks.
```
curl --form action="api-version" http://wpc.example.com/wpc.php
```

It returns the major version of the current release. For release 1.1, it will return '1'.
The method was not available in v0.1.


## Check access
If you have set *require-api-key-to-be-crypted-in-transfer* to `false`, you can test access like this:

```
curl --form action="check-access" --form api-key="my dog is white" http://wpc.example.com/wpc.php
```

Otherwise, it gets more complicated. You will then have to provide "api-key-crypted" and "salt". "api-key-crypted" must be crypted with blowfish, and with salt stripped off. In php, it is calculated like this:

```php
$apiKey = 'my dog is white';
$salt = "whatever-but-make-it-unique-for-each-request";
$cryptedKey = substr(crypt($apiKey, '$2y$10$' . $salt . '$'), 28);
```

## Converting an image
To convert an image, you must set action to "convert", and supply a file.
The method also accepts an "options" argument (json format), which overrides *webp-convert* options set in the configuration.

Example:
```
curl --form action="convert" --form api-key="my dog is white" --form file=@test.jpg http://wpc.example.com/wpc.php > test.webp
```

If you get a corrupt file, then it is probably because the output contains an error message. To see it, run the above command again, but remove the piping of the output to a file.


### Usage example (PHP)

```php

function createRandomSaltForBlowfish() {
    $salt = '';
    $validCharsForSalt = array_merge(
        range('A', 'Z'),
        range('a', 'z'),
        range('0', '9'),
        ['.', '/']
    );

    for ($i=0; $i<22; $i++) {createRandomSaltForBlowfish
        $salt .= $validCharsForSalt[array_rand($validCharsForSalt)];
    }
    return $salt;
}

$source = __DIR__ . 'test.jpg';
$destination = __DIR__ . 'test.jpg.webp';
$url = 'http://example.com/wpc.php';
$apiKey = 'my dog is white';

$salt = createRandomSaltForBlowfish();
// Strip off the first 28 characters (the first 6 are always "$2y$10$". The next 22 is the salt)
$apiKeyCrypted = substr(crypt($apiKey, '$2y$10$' . $salt . '$'), 28);


$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://example.com/wpc.php',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => [
        'action' => 'convert',
        'file' => curl_file_create($source),
        'salt' => $salt,
        'api-key-crypted' => $apiKeyCrypted,
        'options' => json_encode(array(
            'quality' => 'auto',
        ))
    ],
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);

// The WPC cloud service either returns an image or an error message
// Verify that we got an image back.
if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/octet-stream') {
    $success = file_put_contents($destination, $response);
}
else {
    // show error response
    echo $response;
}

curl_close($ch);
```
