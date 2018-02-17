#!/usr/bin/env php
<?php

use Marquis\Facades\Configuration;
use Marquis\Facades\Nginx;
use Marquis\Facades\DnsMasq;
use Marquis\Facades\Site;
use Marquis\Facades\Filesystem;
use Marquis\Facades\CommandLine;
use Marquis\Facades\Marquis;
use Marquis\Facades\Brew;

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    require __DIR__.'/../../../autoload.php';
}
use Silly\Application;
use Illuminate\Container\Container;

/**
 * Create the application.
 */
Container::setInstance(new Container);
$version = '1.0.0';
$app = new Application('Elder Marquis', $version);


/**
 * Allow Marquis to be run more conveniently by allowing the Node proxy to run password-less sudo.
 */
$app->command('install', function () {
    Nginx::stop();
    Configuration::install();
    Nginx::install();
    DnsMasq::install(Configuration::read()['domain']);
    Nginx::restart();
    //Marquis::symlinkToUsersBin();
    output(PHP_EOL.'<info>Marquis installed successfully!</info>');
})->descriptions('Install the Marquis services');

/**
 * Most commands are available only if Marquis is installed.
 */
if (is_dir(MARQUIS_HOME_PATH)) {
    /**
     * Get or set the domain currently being used by Marquis.
     */
    $app->command('domain [domain]', function ($domain = null) {
        if ($domain === null) {
            return info(Configuration::read()['domain']);
        }
        DnsMasq::updateDomain(
            $oldDomain = Configuration::read()['domain'],
            $domain = trim($domain, '.')
        );
        Configuration::updateKey('domain', $domain);
        Site::resecureForNewDomain($oldDomain, $domain);
        PhpFpm::restart();
        Nginx::restart();
        info('Your Marquis domain has been updated to ['.$domain.'].');
    })->descriptions('Get or set the domain used for Marquis sites');
    
    
    $app->command('site [domain] [scheme] [target] [listen]', function ($domain, $scheme, $target, $listen) {
        Site::create($domain.'.'.Configuration::read()['domain'], $scheme, $target, $listen);
        Nginx::restart();
    })->descriptions("Register a port mapping");

    
    /**
     * Start the daemon services.
     */
    $app->command('start', function () {
        PhpFpm::restart();
        Nginx::restart();
        info('Marquis services have been started.');
    })->descriptions('Start the Marquis services');
    /**
     * Restart the daemon services.
     */
    $app->command('restart', function () {
        PhpFpm::restart();
        Nginx::restart();
        info('Marquis services have been restarted.');
    })->descriptions('Restart the Marquis services');
    /**
     * Stop the daemon services.
     */
    $app->command('stop', function () {
        PhpFpm::stop();
        Nginx::stop();
        info('Marquis services have been stopped.');
    })->descriptions('Stop the Marquis services');
    /**
     * Uninstall Marquis entirely.
     */
    $app->command('uninstall', function () {
        Nginx::uninstall();
        info('Marquis has been uninstalled.');
    })->descriptions('Uninstall the Marquis services');
    /**
     * Determine if this is the latest release of Marquis.
     */
    $app->command('on-latest-version', function () use ($version) {
        if (Marquis::onLatestVersion($version)) {
            output('YES');
        } else {
            output('NO');
        }
    })->descriptions('Determine if this is the latest version of Marquis');
    /**
     * Install the sudoers.d entries so password is no longer required.
     */
    $app->command('trust', function () {
        Brew::createSudoersEntry();
        Marquis::createSudoersEntry();
        info('Sudoers entries have been added for Brew and Marquis.');
    })->descriptions('Add sudoers files for Brew and Marquis to make Marquis commands run without passwords');
}


$app->run();
