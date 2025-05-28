<?php

namespace HeadlessChromium\Communication\Socket;

interface WaitForDataInterface
{
    public function waitForData(float $maxSeconds): bool;
}
