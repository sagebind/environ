# Environ
[![Version](https://img.shields.io/packagist/v/coderstephen/environ.svg)](https://packagist.org/packages/coderstephen/environ)
[![License](https://img.shields.io/packagist/l/coderstephen/environ.svg)](https://packagist.org/packages/coderstephen/environ)
[![Downloads](https://img.shields.io/packagist/dt/coderstephen/environ.svg)](https://packagist.org/packages/coderstephen/environ)

A simple package for discovering information about an execution environment and platform. Generic name, generic purpose.

Note that this package is a work-in-progress. See [Contributing](#contributing) below if you want to help out.

## Overview
The purpose of this package is to provide a simple interface for discovering information about a execution environment, like what operating system is installed, number of processors, or what PHP interpreter is being used. I created this because there wasn't something that already existed for PHP.

## Installation
Install with [Composer](http://getcomposer.org), obviously:

```sh
$ composer require coderstephen/environ
```

## Usage
Extremely simple usage; there are a few stateless classes that provide static methods for querying the system. Below is a very simple example:

```php
use Environ\Platform;

printf("CPU architecture: %s\n", Platform::getArch());
printf("Number of CPU cores: %d\n", Platform::getCpuCount());
printf("Operating system: %s\n", Platform::getOSName());
printf("Linux distro: %s\n", Platform::linuxDistribution());
```

You can also test some of the results environ gives for your current environment with a provided script:

```sh
$ vendor/bin/environ-info

-- Platform --
Machine name         : myboss-laptop
Operating system     : Linux
OS release           : 3.19.0-23-generic
OS version           : #24-Ubuntu SMP Tue Jul 7 18:52:55 UTC 2015
CPU architecture     : x86_64
64-bit               : yes
Number of processors : 8

-- Linux distribution --
name                 : ubuntu
release              : 15.04
codename             : vivid
pretty_name          : Ubuntu 15.04

-- Runtime --
Interpreter binary   : /usr/bin/php5
Version              : 5.6.4-4ubuntu6.2
64-bit               : yes
Thread safe          : no
HHVM                 : no
JPHP                 : no
Server module        : no
```

## Reference
You can view a very detailed reference online [here](http://coderstephen.github.io/environ/api).

## Contributing
Want to contribute? The best way to contribute is to test the code on a wide array of systems with varying setups and to verify the results are as expected. If they aren't, just [create a new issue](https://github.com/coderstephen/environ/issues/new) here on GitHub and we will fix it. If you are adventurous, feel free to fork, patch & submit a pull request that fixes the issue as well.

## License
This library is licensed under the MIT license. See the [LICENSE](LICENSE) file for details.
