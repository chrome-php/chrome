<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium;

class AutoDiscover
{
    public function getChromeBinaryPath(): string
    {
        if (\array_key_exists('CHROME_PATH', $_SERVER)) {
            return $_SERVER['CHROME_PATH'];
        }

        if ($this->isMac() === true) {
            return '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
        }

        if ($this->isWindows() === true) {
            return self::windows();
        }

        return 'chrome';
    }

    private static function windows(): string
    {
        try {
            // accessing the registry can be costly, but this specific key is likely to be already cached in memory
            $registryKey = \shell_exec(
                'reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe" /ve'
            );

            \preg_match('/.:(?!.*:).*/', $registryKey, $matches);

            return $matches[0];
        } catch (\Throwable $e) {
            // try to guess the correct path in case the reg query fails
            return '%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe';
        }
    }

    public function isMac(): bool
    {
        if ($this->getOS() === 'Darwin') {
            return true;
        }

        return false;
    }

    public function isWindows(): bool
    {
        switch ($this->getOS()) {
            case 'WIN32':
            case 'WINNT':
            case 'Windows':
                return true;
        }

        return false;
    }

    public function getOS(): string
    {
        return \PHP_OS;
    }
}
