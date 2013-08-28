The private folder stores configuration and other sensitive files.

It is protected from web-access in two ways:
1. files have the .php extension and return an empty page
2a. If you run the Apache web server: the settings in the .htaccess file protect the folder.
2b. During setup, you can specify an alternatve private folder location that is inaccessible from the web.