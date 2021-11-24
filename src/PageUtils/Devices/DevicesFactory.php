<?php

namespace HeadlessChromium\PageUtils\Devices;

use HeadlessChromium\PageUtils\Devices\Device;
use HeadlessChromium\PageUtils\Devices\Resolution;
use HeadlessChromium\PageUtils\Devices\Viewport;

/** @package HeadlessChromium\PageUtils\Devices */
final class DevicesFactory
{
    /**
     * @var array<string, Device> $devicesMap
     */
    private array $devices = [];

    /** 
     * @return void
    */
    public function __construct()
    {
        foreach ($this->devices() as $device) {
            $deviceViewport = $device['viewport'];

            $resolution = new class(
                $deviceViewport['width'], 
                $deviceViewport['height'], 
                $deviceViewport['deviceScaleFactor']
            ) extends Resolution {};
        
            $viewport = new class(
                $resolution, 
                $deviceViewport['isMobile'], 
                $deviceViewport['hasTouch'], 
                $deviceViewport['isLandscape']
            ) extends Viewport {};
        
            $device = new class(
                $device['name'], 
                $device['user-agent'], 
                $viewport
            ) extends Device {};
        
            $this->devices[$device['name']] = $device;
        }
    }

    /**
     * @param string $name 
     * 
     * @return Device 
     */
    public function getDevice(string $name): Device
    {
        return $this->devices[$name];
    }

    /** 
     * @return array<string, string|array<string, int|bool>>
    */
    private function devices(): array
    {
        return [
            [
                'name' => 'Blackberry PlayBook',
                'user-agent' => 
                'Mozilla/5.0 (PlayBook; U; RIM Tablet OS 2.1.0; en-US) AppleWebKit/536.2+ (KHTML like Gecko) Version/7.2.1.0 Safari/536.2+',
                'viewport' => [
                    'width' => 600,
                    'height' =>  1024,
                    'deviceScaleFactor' => 1,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'BlackBerry Z30',
                'user-agent' => 
                'Mozilla/5.0 (BB10; Touch) AppleWebKit/537.10+ (KHTML, like Gecko) Version/10.0.9.2372 Mobile Safari/537.10+',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy Note 3',
                'user-agent' => 
                'Mozilla/5.0 (Linux; U; Android 4.3; en-us; SM-N900T Build/JSS15J) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy Note II',
                'user-agent' => 
                'Mozilla/5.0 (Linux; U; Android 4.1; en-us; GT-N7100 Build/JRO03C) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy S III',
                'user-agent' => 
                'Mozilla/5.0 (Linux; U; Android 4.0; en-us; GT-I9300 Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy S5',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy S8',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 7.0; SM-G950U Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 360,
                    'height' =>  740,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy S9+',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.0.0; SM-G965U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.111 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 320,
                    'height' =>  658,
                    'deviceScaleFactor' => 4.5,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Galaxy Tab S4',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.1.0; SM-T837A) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.80 Safari/537.36',
                'viewport' => [
                    'width' => 712,
                    'height' =>  1138,
                    'deviceScaleFactor' => 2.25,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPad',
                'user-agent' => 
                'Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1',
                'viewport' => [
                    'width' => 768,
                    'height' =>  1024,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPad Mini',
                'user-agent' => 
                'Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1',
                'viewport' => [
                    'width' => 768,
                    'height' =>  1024,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPad Pro',
                'user-agent' => 
                'Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1',
                'viewport' => [
                    'width' => 1024,
                    'height' =>  1366,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 4',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D257 Safari/9537.53',
                'viewport' => [
                    'width' => 320,
                    'height' =>  480,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 5',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_1 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.0 Mobile/14E304 Safari/602.1',
                'viewport' => [
                    'width' => 320,
                    'height' =>  568,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 6',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 375,
                    'height' =>  667,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 6 Plus',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  736,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 7',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 375,
                    'height' =>  667,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 7 Plus',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  736,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 8',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 375,
                    'height' =>  667,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 8 Plus',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  736,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone SE',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_1 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.0 Mobile/14E304 Safari/602.1',
                'viewport' => [
                    'width' => 320,
                    'height' =>  568,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone X',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'viewport' => [
                    'width' => 375,
                    'height' =>  812,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone XR',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  896,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 11',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 13_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  828,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 11 Pro',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 13_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
                'viewport' => [
                    'width' => 375,
                    'height' =>  812,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'iPhone 11 Pro Max',
                'user-agent' => 
                'Mozilla/5.0 (iPhone; CPU iPhone OS 13_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
                'viewport' => [
                    'width' => 414,
                    'height' =>  896,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'JioPhone 2',
                'user-agent' => 
                'Mozilla/5.0 (Mobile; LYF/F300B/LYF-F300B-001-01-15-130718-i;Android; rv:48.0) Gecko/48.0 Firefox/48.0 KAIOS/2.5',
                'viewport' => [
                    'width' => 240,
                    'height' =>  320,
                    'deviceScaleFactor' => 1,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Kindle Fire HDX',
                'user-agent' => 
                'Mozilla/5.0 (Linux; U; en-us; KFAPWI Build/JDQ39) AppleWebKit/535.19 (KHTML, like Gecko) Silk/3.13 Safari/535.19 Silk-Accelerated=true',
                'viewport' => [
                    'width' => 800,
                    'height' =>  1280,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'LG Optimus L70',
                'user-agent' => 
                'Mozilla/5.0 (Linux; U; Android 4.4.2; en-us; LGMS323 Build/KOT49I.MS32310c) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 384,
                    'height' =>  640,
                    'deviceScaleFactor' => 1.25,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Microsoft Lumia 550',
                'user-agent' => 
                'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 550) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/14.14263',
                'viewport' => [
                    'width' => 640,
                    'height' =>  360,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Microsoft Lumia 950',
                'user-agent' => 
                'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/14.14263',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 4,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 10',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 10 Build/MOB31T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Safari/537.36',
                'viewport' => [
                    'width' => 800,
                    'height' =>  1280,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 4',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 384,
                    'height' =>  640,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 5',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 360,
                    'height' =>  640,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 5X',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.0.0; Nexus 5X Build/OPR4.170623.006) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 412,
                    'height' =>  732,
                    'deviceScaleFactor' => 2.625,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 6',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 7.1.1; Nexus 6 Build/N6F26U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 412,
                    'height' =>  732,
                    'deviceScaleFactor' => 3.5,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 6P',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.0.0; Nexus 6P Build/OPP3.170518.006) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 412,
                    'height' =>  732,
                    'deviceScaleFactor' => 3.5,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nexus 7',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 7 Build/MOB30X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Safari/537.36',
                'viewport' => [
                    'width' => 600,
                    'height' =>  960,
                    'deviceScaleFactor' => 2,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nokia Lumia 520',
                'user-agent' => 
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch; NOKIA; Lumia 520)',
                'viewport' => [
                    'width' => 320,
                    'height' =>  533,
                    'deviceScaleFactor' => 1.5,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Nokia N9',
                'user-agent' => 
                'Mozilla/5.0 (MeeGo; NokiaN9) AppleWebKit/534.13 (KHTML, like Gecko) NokiaBrowser/8.5.0 Mobile Safari/534.13',
                'viewport' => [
                    'width' => 480,
                    'height' =>  854,
                    'deviceScaleFactor' => 1,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Pixel 2',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.0; Pixel 2 Build/OPD3.170816.012) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 411,
                    'height' =>  731,
                    'deviceScaleFactor' => 2.625,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Pixel 2 XL',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 8.0.0; Pixel 2 XL Build/OPD1.170816.004) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3765.0 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 411,
                    'height' =>  823,
                    'deviceScaleFactor' => 3.5,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Pixel 3',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 9; Pixel 3 Build/PQ1A.181105.017.A1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.158 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 393,
                    'height' => 786,
                    'deviceScaleFactor' => 2.75,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
            [
                'name' => 'Pixel 4',
                'user-agent' => 
                'Mozilla/5.0 (Linux; Android 10; Pixel 4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36',
                'viewport' => [
                    'width' => 353,
                    'height' =>  745,
                    'deviceScaleFactor' => 3,
                    'isMobile' => true,
                    'hasTouch' => true,
                    'isLandscape' => false,
                ],
            ],
        ];
    }
}