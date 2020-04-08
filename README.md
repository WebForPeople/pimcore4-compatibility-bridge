# Pimcore 4 Compatibility Bridge 

## !UNMAINTAINED! Use at your own risk! 

This package contains the Pimcore 4 compatibilty bridge, which adds the ZF1 dependency and some classes to 
provide a fallback/bridge onto the ZF environment in Pimcore 5 projects. By using this package, you can gradually
migrate your Pimcore 4 projects (Zend Framework) to Pimcore 5 (Symfony).
  
## Installation

Install the compatibility bridge package:

```
$ composer require web4people/pimcore4-compatibility-bridge --no-scripts
$ composer update
```

Follow the [Installation docs](https://www.pimcore.org/docs/5.0.0/Installation_and_Upgrade/Updating_Pimcore/Upgrade_from_4_to_5/Migrate_for_Compatibility_Bridge.html)
for further steps.

## License
[GPLv3 & PEL](./LICENSE.md)
