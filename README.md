# WebPConvert Cloud Service

This library allows you to set up your own WebP conversion cloud service.
This way you can have a cloud service for free. You won't have to worry about licenses expiring. And you will be completely in control of it (and downtime)

The actual conversion is handled by [webp-convert](https://github.com/rosell-dk/webp-convert/)

You can configure the library in the yaml file.

The script is functional, but is under construction. I expect a lot of progress the following weeks.

Upcoming: Protection against exploitation

- *Allowed hosts* option
- Optional crypt and decrypt of file checksom with secret salt. The secret must be set on both the client and the web service. The secret is thus not transmitted. Eardropping on the HTTP request will only allow the eardropper to use the service for converting that specific file.

Upcoming:
Error handling (JSON responses)
