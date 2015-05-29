<?php

/**
 * Provides information about the current system platform.
 */
abstract class Platform
{
    /**
     * @var int Indicates an unrecognized operating system.
     */
    const UNKNOWN = 0;

    /**
     * @var int Indicates a Unix-based or Unix-like operating system.
     */
    const UNIX = 0b0001;

    /**
     * @var int Indicates a Microsoft Windows operating system.
     */
    const WINDOWS = 0b0010;

    /**
     * @var int Indicates a Linux operating system or Linux distribution.
     */
    const LINUX = 0b0101;

    /**
     * @var int Indicates a Darwin-based system, such as Mac OS X.
     */
    const DARWIN = 0b1001;

    /**
     * @var int Indicates a FreeBSD operating system.
     */
    const FREEBSD = 0b10001;

    /**
     * @var int Indicates a Sun Solaris/SunOS or OpenSolaris operating system.
     */
    const SOLARIS = 0b100001;

    /**
     * Gets the operating system the environment is currently running on.
     *
     * @return int Bitmask representing the operating system constant.
     *
     * This method has not been tested on all platforms, so it may not detect
     * untested platforms correctly.
     */
    public static function getOS()
    {
        // what does PHP say we are on?
        $uname = strtolower(php_uname('s'));

        // generic linux or linux distro (we don't care which)
        if ($uname == 'linux' || file_exists('/proc/version')) {
            return self::LINUX;
        }

        // freebsd system
        elseif ($uname == 'freebsd') {
            return self::FREEBSD;
        }

        // some solaris return as 'SunOS'
        elseif ($uname == 'sunos' || $uname == 'solaris') {
            return self::SOLARIS;
        }

        // darwin-based; no way to tell for sure if it is OS X
        elseif ($uname == 'darwin') {
            return self::DARWIN;
        }

        // some unknown unix-based system
        elseif ($uname == 'unix') {
            return self::UNIX;
        }

        // some name of win-something-or-other
        elseif (substr($uname, 0, 3) === 'win') {
            return self::WINDOWS;
        }

        // we really have no idea
        return self::UNKNOWN;
    }

    /**
     * Checks if the system is a given platform.
     *
     * Supports derivative operating systems. For example, if the platform is Linux, checking for Unix
     * will also return true.
     *
     * @param int $platform The platform to check.
     *
     * @return bool True if the system is the given OS or a derivative.
     */
    public static function isOS($os)
    {
        return (self::getOS() & $os) === $os;
    }

    /**
     * Gets the platform version.
     *
     * Note that the version returned varies widely depending on platform
     * specifics. You will need to determine the platform first before the
     * version can be used in any sort of meaningful way.
     *
     * @return string A version string.
     */
    public static function version()
    {
        // OS X has its own way of doing things.
        if (self::isOS(self::DARWIN)) {
            return self::getOSXVersion();
        }

        // On Linux systems, return the version of the Linux kernel being used.
        if (self::isOS(self::LINUX)) {
            return php_uname('r');
        }

        // Just return whatever the version string is for everyone else.
        return php_uname('v');
    }

    /**
     * Gets the Linux distribution information.
     *
     * @return array An associative array of all possible information about the
     *               distribution that could be determined.
     */
    public static function linuxDistribution()
    {
        $info = [];

        // Nothing to do if we're not on Linux.
        if (!self::isOS(self::LINUX)) {
            return $info;
        }

        // If we're on a modern distro, we can use the "os-release" file that is
        // being standardized (thankfully) to find distro info. More info at
        // http://www.freedesktop.org/software/systemd/man/os-release.html
        // and http://0pointer.de/blog/projects/os-release.
        if (is_file('/etc/os-release')) {
            $release = self::parseReleaseFile('/etc/os-release');

            $info['name'] = $release['id'];
            if (isset($release['version_id'])) {
                $info['version'] = $release['version_id'];
            }
            if (isset($release['pretty_name'])) {
                $info['pretty_name'] = $release['pretty_name'];
            }

            // There really isn't more to learn.
            return $info;
        }

        if (is_file('/etc/arch-release')) {
            $info['name'] = 'arch';
        }

        if (is_file('/etc/debian_version')) {
            $info['name'] = 'debian';
        }

        if (is_file('/etc/SuSE-release')) {
            $release = self::parseReleaseFile('/etc/SuSE-release');

            $info['name'] = 'suse';

            if (isset($release['version'])) {
                $info['version'] = $release['version'].'.'.$release['patchlevel'];
            }
        }

        if (is_file('/etc/fedora-release')) {
            $info['name'] = 'fedora';
        }

        if (is_file('/etc/lsb-release')) {
            $release = self::parseReleaseFile('/etc/lsb-release');

            $info['name'] = 'ubuntu';
            $info['version'] = $release['distrib_release'];
            $info['codename'] = $release['distrib_codename'];
            $info['pretty_name'] = $release['distrib_description'];
        }

        if (is_file('/etc/redhat-release')) {
            $release = file_get_contents('/etc/redhat-release');

            $info['name'] = 'redhat';

            if (preg_match('/\d+(\.\d+)+/', $release, $matches) === 1) {
                $info['version'] = $matches[0];
            }
        }

        if (is_file('/etc/centos-release')) {
            $release = file_get_contents('/etc/centos-release');

            $info['name'] = 'centos';

            if (preg_match('/\d+(\.\d+)+/', $release, $matches) === 1) {
                $info['version'] = $matches[0];
            }
        }

        return $info;
    }

    /**
     * Parses a Linux distribution release file and returns the data it contains.
     *
     * @param string $filename The path to the release file.
     *
     * @return array A hash of keys and values.
     */
    private static function parseReleaseFile($filename)
    {
        $contents = file_get_contents($filename);

        // Parse all of the variables in the release file.
        if (preg_match_all('/(\w+)\s*=\s*["\']?([^"\'\r\n]*)["\']?[\r\n]*/', $contents, $matches, PREG_SET_ORDER) === false) {
            throw new \Exception();
        }

        $vars = [];
        foreach ($matches as $match) {
            $vars[strtolower($match[1])] = $match[2];
        }

        return $vars;
    }

    /**
     * Gets the Mac OS X version.
     *
     * @return string
     */
    private static function getOSXVersion()
    {
        $systemVersion = new \DOMDocument();
        $systemVersion->loadXML('/System/Library/CoreServices/SystemVersion.plist');

        $plistNodes = $systemVersion->documentElement->childNodes->item(0)->childNodes;

        for ($i = 0; $i < $plistNodes->length; $i++) {
            if ($plistNodes->item($i)->nodeValue == 'ProductVersion') {
                return $plistNodes->item($i + 1)->nodeValue;
            }
        }

        return '';
    }
}
