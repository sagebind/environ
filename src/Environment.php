<?php

/**
 * Provides methods for getting information about the current application environment.
 *
 * @author Stephen Coakley <me@stephencoakley.com>
 */
abstract class Environment
{
    /**
     * @var string String of bytes used for a new line.
     */
    const NewLine = PHP_EOL;

    /**
     * @var int Indicates an unrecognized operating system.
     */
    const UnknownPlatform = 0;

    /**
     * @var int Indicates a Unix or Unix-based operating system.
     */
    const Unix = 1;

    /**
     * @var int Indicates a Windows operating system.
     */
    const Windows = 2;

    /**
     * @var int Indicates a Linux operating system or Linux distro.
     */
    const Linux = 5;

    /**
     * @var int Indicates a Mac OS X 10 or newer operating system.
     */
    const OSX = 9;

    /**
     * @var int Indicates a FreeBSD operating system.
     */
    const FreeBSD = 17;

    /**
     * @var int Indicates a Sun Solaris or OpenSolaris operating system.
     */
    const Solaris = 33;

    /**
     * Gets the version of the currently executing PHP interpreter.
     *
     * @return Version
     */
    public static function getVersion()
    {
        return new Version(PHP_VERSION);
    }

    /**
     * Gets the operating system the environment is currently running on.
     *
     * @return int Bitmask representing the operating system constant.
     *
     * This method has not been tested on all platforms, so it may not detect untested platforms correctly.
     */
    public static function getPlatform()
    {
        // what does PHP say we are on?
        $uname = strtolower(php_uname('s'));

        // generic linux or linux distro (we don't care which)
        if ($uname == 'linux' || file_exists('/proc/version')) {
            return self::Linux;
        }

        // freebsd system
        elseif ($uname == 'freebsd') {
            return self::FreeBSD;
        }

        // some solaris return as 'SunOS'
        elseif ($uname == 'sunos' || $uname == 'solaris') {
            return self::Solaris;
        }

        // darwin is mac server name
        elseif ($uname == 'darwin') {
            return self::OSX;
        }

        // some unknown unix-based system
        elseif ($uname == 'unix') {
            return self::Unix;
        }

        // some name of win-something-or-other
        elseif (substr($uname, 0, 3) === 'win') {
            return self::Windows;
        }

        // we really have no idea
        return self::UnknownPlatform;
    }

    /**
     * Checks if the system is a given platform.
     *
     * Supports derivative operating systems. For example, if the platform is Linux, checking for Unix
     * will also return true.
     *
     * @param int $platform The platform to check.
     *
     * @return bool True if the system is the given platform or a derivative.
     */
    public static function isPlatform($platform)
    {
        return self::getPlatform() & $platform;
    }

    /**
     * Gets the current working directory.
     *
     * @return string
     *                The path of the current directory.
     *
     * @throws Exception Thrown if the current directory could not be determined.
     *
     * On some Unix variants, this method will fail if any one of the parent directories does not have the readable or search mode set, even if the current directory does.
     */
    public static function getCurrentDirectory()
    {
        // try to get the directory
        $cd = getcwd();

        // getcwd() failed
        if ($cd === false) {
            throw new Exception('Failed to determine the current working directory.');
        }

        // return the path
        return $cd;
    }

    /**
     * Changes the current working directory.
     *
     * @param string $path The path of the directory.
     *
     * @throws Exception Thrown if the given path does not exist.
     */
    public static function changeDirectory($path)
    {
        if (!chdir($path)) {
            // chdir() failed
            throw new Exception("Directory '{$path}' does not exist.");
        }
    }

    /**
     * Checks if a given environment variable exists.
     *
     * @param string $name The name of the environment variable.
     *
     * @return bool True if the variable exists; otherwise false.
     */
    public static function hasVariable($name)
    {
        return is_string(getenv($name));
    }

    /**
     * Gets the value of a given environment variable.
     *
     * @param string $name The name of the environment variable.
     *
     * @return string|null The value of the environment variable, or null if it doesn't exist.
     */
    public static function getVariable($name)
    {
        if (self::hasVariable($name)) {
            return getenv($name);
        }

        return;
    }

    /**
     * Sets the value of an environemnt variable.
     *
     * @param string $name  The name of the environment variable.
     * @param string $value The new value to set the environment variable to.
     *
     * @throws Exception Thrown if setting the environment variable failed.
     */
    public static function setVariable($name, $value)
    {
        if (!putenv("$name=$value")) {
            throw new Exception("Failed to set environment variable '{$name}' to '{$value}'.");
        }
    }

    /**
     * Replaces the name of each environment variable embedded in the specified string with the string equivalent
     * of the value of the variable, then returns the resulting string.
     *
     * @param string $string The string to operate on.
     *
     * @return string The parsed version of the given string.
     */
    public static function expandVariables($string)
    {
        // replace windows and unix variables ('$var' and '%var%')
        preg_replace_callback('#(\$(\w+)|%(\w+)%)#', function ($matches) {
            // replace with the value of the variable
            return (string)self::getVariable($matches[1]);
        }, $string);

        // formatted string
        return $string;
    }

    /**
     * Checks if the execution environment is running as a server module.
     *
     * @return bool True if the environment is running as a server module, otherwise false.
     */
    public static function isServerModule()
    {
        return PHP_SAPI !== 'cli';
    }

    /**
     * Gets the name of the local computer.
     *
     * @return string The NetBIOS name or hostname of the local computer.
     */
    public static function getDeviceName()
    {
        return php_uname('n');
    }

    /**
     * Gets the name of the user currently logged on.
     *
     * @return string|null The name of the current user, or null if the information is unavailable.
     */
    public static function getUserName()
    {
        // windows usually has this variable, sometimes linux
        if (self::hasVariable('USERNAME')) {
            return self::getVariable('USERNAME');
        }

        // gnu and unix
        elseif (self::hasVariable('LOGNAME')) {
            return self::getVariable('LOGNAME');
        }

        // linux
        elseif (self::hasVariable('USER')) {
            return self::getVariable('USER');
        }

        // dunno
        return;
    }

    /**
     * Gets the type of processor.
     *
     * @return string The type of processor.
     */
    public static function getProcessor()
    {
        return php_uname('m');
    }

    /**
     * Checks if the environment is running in a 64-bit environment.
     *
     * Note that PHP currently does not support 64-bit on Windows, even if the build and the system
     * are both 64-bit. As a result, this method will always return false on Windows.
     *
     * @return bool True if the environment is 64-bit, otherwise false.
     */
    public static function is64Bit()
    {
        return PHP_INT_MAX > 2147483647;
    }

    /**
     * Gets the total amount of accessible memory the system has.
     *
     * @return int The amount of memory in bytes, or -1 if the information is unavailable.
     */
    public static function getTotalMemory()
    {
        // the linux way
        if (self::isPlatform(self::Linux)) {
            // memory info file
            $data = file_get_contents('/proc/meminfo');

            // look for total memory
            if (preg_match('#MemTotal:\s*(\d+)#i', $data, $matches)) {
                // get the size in bytes
                return intval($matches[1]) * 1024;
            }
        }

        // windows system
        elseif (self::isPlatform(self::Windows) && self::extensionAvailable('COM_DOTNET')) {
            // use the WMI
            $wmi = new \COM('winmgmts:');

            // query system info
            $result = $wmi->ExecQuery('SELECT * FROM Win32_OperatingSystem');

            // get the first (and only) item
            foreach ($result as $item) {
                // get the size in bytes
                return $item->TotalVisibleMemorySize * 1024;
            }
        }

        // no obvious way to get memory info
        return -1;
    }

    /**
     * Gets the amount of free memory the system has.
     *
     * @return int The amount of memory in bytes, or -1 if the information is unavailable.
     */
    public static function getAvailableMemory()
    {
        // linux system
        if (self::isPlatform(self::Linux)) {
            // memory info file
            $data = file_get_contents('/proc/meminfo');

            // look for free memory
            if (preg_match('#MemFree:\s*(\d+)#i', $data, $matches)) {
                // get the size in bytes
                return intval($matches[1]) * 1024;
            }
        }

        // windows system
        elseif (self::isPlatform(self::Windows) && self::extensionAvailable('COM_DOTNET')) {
            // use the WMI
            $wmi = new \COM('winmgmts:');

            // query system info
            $result = $wmi->ExecQuery('SELECT * FROM Win32_OperatingSystem');

            // get the first (and only) item
            foreach ($result as $item) {
                // get the size in bytes
                return $item->FreePhysicalMemory * 1024;
            }
        }

        // no obvious way to get memory info
        return -1;
    }

    /**
     * Gets the amount of memory allocated to the execution environment.
     *
     * @return int The amount of memory in bytes.
     */
    public static function getMemoryUsage()
    {
        return memory_get_usage(true);
    }

    /**
     * Checks if a given extension is available and ready to use.
     *
     * @return bool True if the extension is available and loaded, otherwise false.
     */
    public static function extensionAvailable($extensionName)
    {
        // not loaded by system
        if (!extension_loaded($extensionName)) {
            try {
                // load the extension
                self::loadExtension($extensionName);
            } catch (Exception $exception) {
                // extension doesn't exist
                return false;
            }
        }

        // ready to use
        return true;
    }

    /**
     * Loads a runtime extension.
     *
     * @param string $extensionName The name of the extension to load.
     */
    public static function loadExtension($extensionName)
    {
        // make sure we are able to load extensions
        if (ini_get('enable_dl') !== 1 || ini_get('safe_mode') === 1 || !function_exists('dl')) {
            throw new Exception('Permission to load extensions is disabled.');
        }

        // generate the extension file name
        if (self::isPlatform(self::Windows)) {
            $fileName = strtolower("php_{$extensionName}.dll");
        } else {
            $fileName = strtolower("{$extensionName}.so");
        }

        // attempt to dynamically load the extension
        if (!@dl($fileName)) {
            throw new Exception("Failed to load extension '$extensionName'; the extension does not exist or is corrupt.");
        }
    }
}
