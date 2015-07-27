<?php
namespace Environ;

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
    const UNIX = 0b00000001;

    /**
     * @var int Indicates a Microsoft Windows operating system.
     */
    const WINDOWS = 0b00000010;

    /**
     * @var int Indicates a Linux operating system or Linux distribution.
     */
    const LINUX = 0b00000101;

    /**
     * @var int Indicates a Darwin-based system, such as Mac OS X.
     */
    const DARWIN = 0b00001001;

    /**
     * @var int Indicates a FreeBSD operating system.
     */
    const FREEBSD = 0b00010001;

    /**
     * @var int Hewlett-Packard UNIX systems.
     */
    const HP_UX = 0b00100001;

    /**
     * @var int IBM AIX (Advanced Interactive eXecutive) systems.
     */
    const AIX = 0b01000001;

    /**
     * @var int Indicates a Sun Solaris/SunOS or OpenSolaris operating system.
     */
    const SOLARIS = 0b10000001;

    /**
     * Gets the number of logical processor cores available on the system.
     *
     * @return int The number of processors.
     */
    public static function getCpuCount()
    {
        // Most UNIX systems
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            return substr_count($cpuinfo, 'processor');
        }

        // Use WMI on Windows
        if (self::isOS(self::WINDOWS)) {
            if (@exec('wmic cpu get NumberOfCores', $output) !== false) {
                return intval($output[1]);
            }
        }

        // Use sysctl as a last resort on other systems, like old BSD releases
        $ncpu = @exec('sysctl -n hw.ncpu');
        if ($ncpu !== false) {
            return intval($ncpu);
        }

        return 1;
    }

    /**
     * Checks if the system platform is 64-bit capable.
     *
     * Note that this does not necessarily represent the architecture or 64-bit
     * capabilities of the processor(s); this merely checks if the platform or
     * operating system is 64-bit. These things may be different on, for example,
     * a 32-bit version of Windows on a 64-bit machine.
     *
     * In addition, even if this function returns true, the current PHP runtime
     * may not be 64-bit. To check if the runtime is also 64-bit, use
     * {@see Runtime::is64Bit()}.
     *
     * @return bool True if the platform is 64-bit, otherwise false.
     */
    public static function is64Bit()
    {
        return php_uname('m') === 'x86_64';
    }

    /**
     * Gets the architecture name of the CPU processor.
     *
     * @return string The CPU architecture name.
     */
    public static function getArch()
    {
        return php_uname('m');
    }

    /**
     * Gets the NetBIOS name or hostname of the current machine.
     *
     * @return string The name of the current machine.
     */
    public static function getMachineName()
    {
        return php_uname('n');
    }

    /**
     * Gets the operating system the environment is currently running on.
     *
     * Returns one of several flags that indicates a particular operating system.
     * Below is a list of possible values:
     *
     * - `Platform::UNIX`
     * - `Platform::LINUX`
     * - `Platform::FREEBSD`
     * - `Platform::DARWIN`
     * - `Platform::WINDOWS`
     * - `Platform::SOLARIS`
     * - `Platform::HP_UX`
     * - `Platform::AIX`
     *
     * This method has not been tested on all platforms, so it may not detect
     * untested platforms correctly.
     *
     * @return int Bitmask representing the operating system constant.
     */
    public static function getOS()
    {
        // what does PHP say we are on?
        $uname = strtolower(php_uname('s'));

        // generic linux or linux distro (we don't care which)
        if ($uname === 'linux' || file_exists('/proc/version')) {
            return self::LINUX;
        }

        // freebsd system
        if ($uname === 'freebsd') {
            return self::FREEBSD;
        }

        // some solaris return as 'SunOS'
        if ($uname === 'sunos' || $uname === 'solaris') {
            return self::SOLARIS;
        }

        // darwin-based; no way to tell for sure if it is OS X
        if ($uname === 'darwin') {
            return self::DARWIN;
        }

        // HP-UX system
        if ($uname === 'hp-ux') {
            return self::HP_UX;
        }

        // IBM AIX
        if ($uname === 'aix') {
            return self::AIX;
        }

        // some unknown unix-based system
        if ($uname === 'unix') {
            return self::UNIX;
        }

        // some name of win-something-or-other
        if (substr($uname, 0, 3) === 'win' || substr($uname, 0, 6) === 'cygwin') {
            return self::WINDOWS;
        }

        // we really have no idea
        return self::UNKNOWN;
    }

    /**
     * Gets an operating system name suitable for display purposes.
     *
     * This returns a string that corresponds to the operating system returned
     * by {@see Platform::getOS()}, not the name returned by the system itself.
     *
     * @return string The operating system name.
     */
    public static function getOSName()
    {
        switch (self::getOS()) {
            case self::LINUX:
                return 'Linux';
            case self::FREEBSD:
                return 'FreeBSD';
            case self::SOLARIS:
                return 'SunOS/Solaris';
            case self::DARWIN:
                return 'Mac OS X';
            case self::HP_UX:
                return 'HP-UX';
            case self::AIX:
                return 'IBM AIX';
            case self::WINDOWS:
                return 'Windows';
            case self::UNIX:
                return 'UNIX';
            default:
                return 'unknown';
        }
    }

    /**
     * Checks if the system is a given platform.
     *
     * This returns true if the running OS matches the one given, or if it is a
     * derivative. For example, if you call `Platform::isOS(Platform::UNIX)` on
     * a FreeBSD system, it will return true, since FreeBSD is UNIX-like.
     *
     * Note that you can check if the running OS is one of a list of OSes by
     * passing multiple values. For example:
     *
     * <code>
     * // Check if we are on either FreeBSD or Solaris
     * if (Platform::isOS(Platform::FREEBSD, Platform::SOLARIS)) {
     *     echo 'We are on FreeBSD or Solaris!' . PHP_EOL;
     * }
     * </code>
     *
     * @param int $os One or more OS flag constants to check.
     *
     * @return bool True if the system is the given OS or a derivative, otherwise false.
     */
    public static function isOS($os)
    {
        foreach (func_get_args() as $os) {
            if ((self::getOS() & $os) === $os) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the platform release as a version string.
     *
     * This method tries really hard to return an alphanumeric string, maybe
     * even in SemVer format, but there are no guarantees.
     *
     * {@see https://msdn.microsoft.com/library/windows/desktop/ms724832.aspx}
     * for a guide on what Windows release versions can indicate.
     *
     * @return string A release version string.
     */
    public static function release()
    {
        // OS X has its own way of doing things.
        if (self::isOS(self::DARWIN)) {
            return self::getOSXRelease();
        }

        // Solaris and SunOS store release info in a file, like a good UNIX.
        if (self::isOS(self::SOLARIS)) {
            return file_get_contents('/etc/release');
        }

        // For Windows, Linux and other UNIXes, just return the uname release.
        return php_uname('r');
    }

    /**
     * Gets the platform version; typically includes build information.
     *
     * Note that the version returned varies widely depending on platform
     * specifics. You will need to determine the platform first before the
     * version can be used in any sort of meaningful way.
     *
     * @return string A string that probably includes some version information.
     */
    public static function version()
    {
        return php_uname('v');
    }

    /**
     * Gets the Linux distribution information.
     *
     * Returns an associative array of strings containing information about the
     * Linux distribution if the current OS is Linux. If the OS is not Linux, or
     * if no known distribution was detected, an empty array is returned.
     *
     * Below is a list of possible keys returned:
     *
     * - `name`: The name of the distribution.
     * - `release`: The release version.
     * - `codename`: A codename for the current release.
     * - `pretty_name`: A formatted name suitable for display.
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

        // Check for the uncommon LSB release file for release info. Only
        // Ubuntu and Linux Mint actually use this I think.
        if (is_file('/etc/lsb-release')) {
            $release = self::parseReleaseFile('/etc/lsb-release');

            // LSB release files are actually very tidy!
            $info['name'] = strtolower($release['DISTRIB_ID']);
            $info['release'] = $release['DISTRIB_RELEASE'];
            $info['codename'] = $release['DISTRIB_CODENAME'];
            $info['pretty_name'] = $release['DISTRIB_DESCRIPTION'];
        }

        // If we're on a modern distro, we can use the "os-release" file that is
        // being standardized (thankfully) to find distro info. More info at
        // http://www.freedesktop.org/software/systemd/man/os-release.html
        // and http://0pointer.de/blog/projects/os-release.
        elseif (is_file('/etc/os-release')) {
            $release = self::parseReleaseFile('/etc/os-release');

            $info['name'] = $release['ID'];
            if (isset($release['VERSION_ID'])) {
                $info['release'] = $release['VERSION_ID'];
            }
            if (isset($release['PRETTY_NAME'])) {
                $info['pretty_name'] = $release['PRETTY_NAME'];
            }
        }

        // We couldn't find any generic information, so now look for specific
        // distros by release files, issue info, anything. Add code below for
        // identifying unrecognized or old distros.

        // SuSE; usually SLES
        elseif (is_file('/etc/SuSE-release')) {
            $release = self::parseReleaseFile('/etc/SuSE-release');

            $info['name'] = 'suse';

            if (isset($release['VERSION'])) {
                $info['release'] = $release['VERSION'] . '.' . $release['PATCHLEVEL'];
            }
        }

        // Fedora
        elseif (is_file('/etc/fedora-release')) {
            $info['name'] = 'fedora';

            $release = file_get_contents('/etc/fedora-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Mandrake Linux
        elseif (is_file('/etc/mandrake-release')) {
            $info['name'] = 'mandrake';

            $release = file_get_contents('/etc/mandrake-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // CentOS and derivatives
        elseif (is_file('/etc/centos-release')) {
            $info['name'] = 'centos';

            $release = file_get_contents('/etc/centos-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Gentoo Linux
        elseif (is_file('/etc/gentoo-release')) {
            $info['name'] = 'gentoo';
        }

        // Slackware Linux
        elseif (is_file('/etc/slackware-version')) {
            $info['name'] = 'slackware';

            $release = file_get_contents('/etc/slackware-version');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Redhat and derivatives
        elseif (is_file('/etc/redhat-release')) {
            $info['name'] = 'redhat';

            $release = file_get_contents('/etc/redhat-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Check for Debian last to avoid false positives for the many Debian forks
        elseif (is_file('/etc/debian_version')) {
            $info['name'] = 'debian';

            $release = file_get_contents('/etc/debian_version');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // If we haven't found anything yet, see if there is any meaningful info
        // in /etc/issue, which is somewhat common, but doesn't strictly contain
        // distro info.
        elseif (is_file('/etc/issue')) {
            $issueMessage = file_get_contents('/etc/issue');

            // If the message doesn't match the expression, it probably isn't
            // a distro description anyway.
            if (preg_match('/([^0-9\r\n]+)\s+(?:release\s+)?(\d+(\.\d+)*)/i', $issueMessage, $matches) === 1) {
                $info['pretty_name'] = $matches[0];
                $info['name'] = strtolower($matches[1]);
                $info['release'] = $matches[2];
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
            $vars[strtoupper($match[1])] = $match[2];
        }

        return $vars;
    }

    /**
     * Gets the Mac OS X version string.
     *
     * @return string
     */
    private static function getOSXRelease()
    {
        $systemVersion = new \DOMDocument();
        $systemVersion->loadXML('/System/Library/CoreServices/SystemVersion.plist');

        $plistNodes = $systemVersion->documentElement->childNodes->item(0)->childNodes;

        for ($i = 0; $i < $plistNodes->length; ++$i) {
            if ($plistNodes->item($i)->nodeValue == 'ProductVersion') {
                return $plistNodes->item($i + 1)->nodeValue;
            }
        }

        return '';
    }
}
