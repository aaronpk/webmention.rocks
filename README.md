Webmention Rocks!
=================

Webmention Rocks! is a validator to help you test your Webmention implementation. Several kinds of tests are available on the site.


## Developing

* Clone the source code 
 * `git clone https://github.com/aaronpk/webmention.rocks.git`
* Install dependencies
 * `composer install`
* Copy the config file and fill in your settings
 * `cp config.template.php config.php`
* Make sure the web server can write to the `data` folder
 * `chmod 777 data`

This software uses Redis to cache responses, so make sure you have Redis installed locally, or point it to a remote Redis server in the config file.


## License

Copyright 2016 by Aaron Parecki

Available under the Apache 2.0 license. See [LICENSE](LICENSE).
