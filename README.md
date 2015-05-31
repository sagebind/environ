# Environ
[![License](https://img.shields.io/packagist/l/coderstephen/environ.svg)](https://packagist.org/packages/coderstephen/environ)

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

## Reference
Below is a list of available classes and methods.

### `Environ\Platform`
Provides information about the current system platform.

- `getMachineName(): string`

  Gets the NetBIOS name or hostname of the current machine.

- `getArch(): string`

  Gets the architecture name of the CPU processor.

- `getCpuCount(): int`

  Gets the number of logical processor cores available on the system.

- `getOSName(): string`

  Gets the name of the running operating system, suitable for display.

- `getOS(): int`

  Gets one of several flags that indicates a particular operating system. Below is a list of possible values:

  - `Platform::UNIX`
  - `Platform::LINUX`
  - `Platform::FREEBSD`
  - `Platform::DARWIN`
  - `Platform::WINDOWS`
  - `Platform::SOLARIS`
  - `Platform::HP_UX`
  - `Platform::AIX`

- `isOS(int $os): bool`

  Checks if the current operating system is a given operating system. This returns true if the running OS matches the one given, or if it is a derivative. For example, if you call `Platform::isOS(Platform::UNIX)` on a FreeBSD system, it will return true, since FreeBSD is UNIX-like.

  - `int $os`: One or more OS flag constants.
  
  Note that you can check if the running OS is one of a list of OSes by passing multiple values. For example:

  ```php
  // Check if we are on either FreeBSD or Solaris
  if (Platform::isOS(Platform::FREEBSD, Platform::SOLARIS)) {
      echo 'We are on FreeBSD or Solaris!' . PHP_EOL;
  }
  ```

- `release(): string`

  Gets the release version of the operating system.

- `version(): string`

  Gets the build version of the operating system.

- `linuxDistribution(): array`
  
  Gets an associative array of strings containing information about the Linux distribution if the current OS is Linux. If the OS is not Linux, or if no known distribution was detected, an empty array is returned.

  Below is a list of possible keys returned:

  - `name`: The name of the distribution.
  - `release`: The release version.
  - `codename`: A codename for the current release.
  - `pretty_name`: A formatted name suitable for display.

## Contributing
Want to contribute? The best way to contribute is to test the code on a wide array of systems with varying setups and to verify the results are as expected. If they aren't, just [create a new issue](https://github.com/coderstephen/environ/issues/new) here on GitHub and we will fix it. If you are adventurous, feel free to fork, patch & submit a pull request that fixes the issue as well.

## License
This library is licensed under the MIT license. See the [LICENSE](LICENSE) file for details.
