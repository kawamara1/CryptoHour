<?php

/**
 * A Twitter bot application intented to have hourly updates of the top 10 cryptocurrencies
 *
 */

use CryptoBot\CryptoBot;

$application = new CryptoBot;

//initializing application - means that the instances of the other classes will be created
$application -> init();

//run app

$application -> run();
