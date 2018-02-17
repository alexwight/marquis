<?php

namespace Marquis;

class Marquis
{
    public $cli;
    public $files;

    public $marquisBin = '/usr/local/bin/marquis';

    /**
     * Create a new Marquis instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Symlink the Marquis Bash script into the user's local bin.
     *
     * @return void
     */
    public function symlinkToUsersBin()
    {
        $this->cli->quietlyAsUser('rm '.$this->marquisBin);

        $this->cli->runAsUser('ln -s '.realpath(__DIR__.'/../../marquis').' '.$this->marquisBin);
    }

    /**
     * Get the paths to all of the Valet extensions.
     *
     * @return array
     */
    public function extensions()
    {
        if (! $this->files->isDir(MARQUIS_HOME_PATH.'/Extensions')) {
            return [];
        }

        return collect($this->files->scandir(MARQUIS_HOME_PATH.'/Extensions'))
                    ->reject(function ($file) {
                        return is_dir($file);
                    })
                    ->map(function ($file) {
                        return MARQUIS_HOME_PATH.'/Extensions/'.$file;
                    })
                    ->values()->all();
    }

    /**
     * Determine if this is the latest version of Valet.
     *
     * @param  string  $currentVersion
     * @return bool
     */
    public function onLatestVersion($currentVersion)
    {
        $response = \Httpful\Request::get('https://api.github.com/repos/alexwight/marquis/releases/latest')->send();

        return version_compare($currentVersion, trim($response->body->tag_name, 'v'), '>=');
    }

    /**
     * Create the "sudoers.d" entry for running Valet.
     *
     * @return void
     */
    public function createSudoersEntry()
    {
        $this->files->ensureDirExists('/etc/sudoers.d');

        $this->files->put('/etc/sudoers.d/marquis', 'Cmnd_Alias MARQUIS = /usr/local/bin/marquis *
%admin ALL=(root) NOPASSWD: MARQUIS'.PHP_EOL);
    }
}
