<?php

namespace Marquis;

use DomainException;

class Nginx
{
    public $brew;
    public $cli;
    public $files;
    public $configuration;
    public $site;
    const NGINX_CONF = '/usr/local/etc/nginx/nginx.conf';

    /**
     * Create a new Nginx instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration  $configuration
     * @param  Site  $site
     * @return void
     */
    public function __construct(
        Brew $brew,
        CommandLine $cli,
        Filesystem $files,
                         Configuration $configuration,
        Site $site
    ) {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->site = $site;
        $this->files = $files;
        $this->configuration = $configuration;
    }

    /**
     * Install the configuration files for Nginx.
     *
     * @return void
     */
    public function install()
    {
        if (!$this->brew->hasInstalledNginx()) {
            $this->brew->installOrFail('nginx', ['--with-http2']);
        }

        $this->installConfiguration();
        // $this->installServer();
        $this->installNginxDirectory();
    }

    /**
     * Install the Nginx configuration file.
     *
     * @return void
     */
    public function installConfiguration()
    {
        info('Installing nginx configuration...');

        $this->files->appendAsUser(
            static::NGINX_CONF,
            PHP_EOL.str_replace(['MARQUIS_USER', 'MARQUIS_HOME_PATH'], [user(), MARQUIS_HOME_PATH], "http {\n   include MARQUIS_HOME_PATH/Nginx/*;\n}").PHP_EOL
        );
    }

    /**
     * Install the valet Nginx server configuration file.
     *
     * @return void
     */
    public function installServer()
    {
        // $this->files->ensureDirExists('/usr/local/etc/nginx/marquis');

        // $this->files->putAsUser(
        //     '/usr/local/etc/nginx/marquis/marquis.conf',
        //     str_replace(
        //         ['MARQUIS_HOME_PATH', 'MARQUIS_SERVER_PATH', 'MARQUIS_STATIC_PREFIX'],
        //         [MARQUIS_HOME_PATH, MARQUIS_SERVER_PATH, MARQUIS_STATIC_PREFIX],
        //         $this->files->get(__DIR__.'/../stubs/marquis.conf')
        //     )
        // );

        // $this->files->putAsUser(
        //     '/usr/local/etc/nginx/fastcgi_params',
        //     $this->files->get(__DIR__.'/../stubs/fastcgi_params')
        // );
    }

    /**
     * Install the Nginx configuration directory to the ~/.valet directory.
     *
     * This directory contains all site-specific Nginx servers.
     *
     * @return void
     */
    public function installNginxDirectory()
    {
        info('Installing nginx directory...');

        if (! $this->files->isDir($nginxDirectory = MARQUIS_HOME_PATH.'/Nginx')) {
            $this->files->mkdirAsUser($nginxDirectory);
        }

        $this->files->putAsUser($nginxDirectory.'/.keep', "\n");

        $this->rewriteSecureNginxFiles();
    }

    /**
     * Check nginx.conf for errors.
     */
    private function lint()
    {
        $this->cli->quietly(
            'sudo nginx -c '.static::NGINX_CONF.' -t',
            function ($exitCode, $outputMessage) {
                throw new DomainException("Nginx cannot start, please check your nginx.conf [$exitCode: $outputMessage].");
            }
        );
    }

    /**
     * Generate fresh Nginx servers for existing secure sites.
     *
     * @return void
     */
    public function rewriteSecureNginxFiles()
    {
        $domain = $this->configuration->read()['domain'];

        $this->site->resecureForNewDomain($domain, $domain);
    }

    /**
     * Restart the Nginx service.
     *
     * @return void
     */
    public function restart()
    {
        $this->lint();

        $this->brew->restartService($this->brew->nginxServiceName());
    }

    /**
     * Stop the Nginx service.
     *
     * @return void
     */
    public function stop()
    {
        info('Stopping nginx...');

        $this->cli->quietly('sudo brew services stop '. $this->brew->nginxServiceName());
    }

    /**
     * Prepare Nginx for uninstallation.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->stop();
    }
}
