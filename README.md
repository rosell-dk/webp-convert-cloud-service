# WebPConvert Cloud Service

This library allows you to set up your own WebP conversion cloud service.
This way you can have a cloud service for free. You won't have to worry about licenses expiring. And you will be completely in control of it (and downtime)

The actual conversion is handled by [webp-convert](https://github.com/rosell-dk/webp-convert/)


The script is functional, but is under construction. I expect a lot of progress the following weeks.

Upcoming: Protection against exploitation

- *Allowed hosts* option
- Optional crypt and decrypt of file checksom with secret salt. The secret must be set on both the client and the web service. The secret is thus not transmitted. Eardropping on the HTTP request will only allow the eardropper to use the service for converting that specific file.

Upcoming:
Error handling (JSON responses)


## Configuration

Rename `config.yaml.example` to `config.yaml`. Then edit.

If you install via composer, the file is renamed automatically upon install.

This approach is, btw, similar to how Laravel handles configutation files [1](https://laravel.com/docs/5.6/configuration) (although Laravel uses dotenv)
