SSL Generator
=============

This tool wraps around [ACME PHP](https://acmephp.github.io/) in order to automagically generate SSL certificates.
This does it through interfacing with your DNS provider in order to create the necessary TXT record.

* [Youtube demonstration video](https://youtu.be/N2sDOMFGyLk).

## Getting Started

* [Install composer](https://blog.programster.org/ubuntu-install-composer) if you haven't got it already.
* [Install ACME PHP](https://blog.programster.org/acme-php-installation) to your machine.
* Use the tool to [register](https://blog.programster.org/acme-php-registration) (you only have to do this once).
* Clone this repository: `git clone https://github.com:programster/SSL-generator.git`
* Go into the `/src` folder.
* Run `composer install` to install the necessary packages.
* Fill in the `Settings.php.tmpl` file.
    * Right now there is only one supported driver ([Route53](https://aws.amazon.com/route53/)), so you just need to plug in the path to your ACME PHP installation, and plug in your AWS key and secret.
* Rename the `Settings.php.tmpl` file to `Settings.php`
* Execute the `main.php` file directly
* Answer any/all questions truthfully.
* You should see your newly generated certificate files in the directory you ran the command from.

### Use an Alias
Rather than executing `main.php` directly every time, it would be advisable to create an alias in your `.bashrc` file for executing the script.
That way you don't have go to that folder when you want to run the tool, or type a long path in every time.
For example:
```
alias ssl-generator="/usr/bin/php $HOME/SSL-generator/src/main.php"
```


## Contributing
It would be great to get some drivers for other DNS providers. Its as easy as writing a class that implements the `AcmeDnsDriverInterface` which has just requires one method:

```php
/**
 * Add a TXT record
 * @param string $name - the TXT record FQDN. E.g. "test.mydomin.org"
 * @param string $value - the value for the TXT record.
 * @return void - throw exception if anything goes wrong.
 */
public function addTxtRecord(string $name, string $value);
```
