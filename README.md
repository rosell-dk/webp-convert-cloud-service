# WebPConvert Cloud Service

This library allows you to set up your own WebP conversion cloud service. This way you can have a cloud service for free. You won't have to worry about licenses expiring. And you will be completely in control of it (and downtime)


## Installation

- Install with composer
- Rename `wpc-config.yaml.example` to `wpc-config.yaml`
- Move `wpc-config.yaml` to a parent folder (outside of webroot)
- Edit `wpc-config.yaml` (it is self-documented)

## Usage


### API
The cloud converter can be used with [webp-convert](https://github.com/rosell-dk/webp-convert/). In that case, you don't need to know the API.

Conversion is done by calling `wpc.php`. The following is expected in the POST:

#### file
The file to be converted

#### options
A json encoded array of [webp-convert](https://github.com/rosell-dk/webp-convert/) options

#### hash
A hash build up from file hash and a secret string, which is known in both ends. It must be calculated like this: `md5(md5_file($source) . $secret)`. This mechanism protects the service from exploitation. As the secret itself is not transmitted, eavesdropping on the transmission will not reveal the secret (the eavesdropper will only be able to use the service for converting that specific file)

### Usage example (command line)
You can test the service with the following command. It is a little complex, because it must create the `hash` argument. Make sure to change 'my dog is white' to match the secret you have set up in `config.yaml`.

```
md5sum logo.jpg | cut -c 1-32 | echo -n "$(cat -)my dog is white" | md5sum | cut -c 1-32 | xargs curl --form hash="$(cat -)" --form file=@logo.jpg http://example.com/wpc.php > optimized.jpg
```


### Usage example (PHP)

```php

$secret = 'my dog is white';
$url = 'http://example.com/wpc.php';
$source = __DIR__ . 'test.jpg';
$destination = __DIR__ . 'test.jpg.webp';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://example.com/wpc.php',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => [
        'file' => curl_file_create($source),
        'hash' => md5(md5_file($source) . $secret),
        'options' => json_encode(array(
            'quality' => 80,
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

## Roadmap
I'm pretty satisfied with how it works now. I expect a 0.1.0 release soon.
