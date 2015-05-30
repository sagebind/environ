# Environment
[![License](https://img.shields.io/packagist/l/coderstephen/environment.svg)](https://packagist.org/packages/coderstephen/environment)

A simple package for discovering information about an execution environment and platform. Generic name, generic purpose.

Note that this package is a work-in-progress. See [Contributing](#contributing) below if you want to help out.

## Overview
The purpose of this package is to provide a simple interface for discovering information about a execution environment, like what operating system is installed, number of processors, or what PHP interpreter is being used. I created this because there wasn't something that already existed for PHP.

## Installation
Install with [Composer](http://getcomposer.org), obviously:

```sh
$ composer require coderstephen/environment
```

## Usage
Extremely simple usage; there are a few stateless classes that provide static methods for querying the system. Below is an example that displays various information:

```
<?php
use Environment\Platform;

printf('CPU architecture: %s\n', Platform::getArch());
printf('Number of CPU cores: %d\n', Platform::getCpuCount());
printf('Operating system: %s\n', Platform::getOS());
printf('Linux distro: %s\n', Platform::linuxDistribution());
```

## Contributing
Want to contribute? The best way to contribute is to test the code on a wide array of systems with varying setups and to verify the results are as expected. If they aren't, just [create a new issue](issues/new) here on GitHub and we will fix it. If you are adventurous, feel free to fork, patch & submit a pull request that fixes the issue as well.

## License
This library is licensed under the MIT license. See the [LICENSE](LICENSE) file for details.
