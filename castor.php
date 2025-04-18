<?php

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\guard_min_version;
use function Castor\import;
use function Castor\io;
use function Castor\notify;
use function Castor\variable;
use function docker\about;
use function docker\build;
use function docker\docker_compose_run;
use function docker\generate_certificates;
use function docker\up;

// use function docker\workers_start;
// use function docker\workers_stop;

guard_min_version('0.18.0');

import(__DIR__ . '/.castor');

/**
 * @return array{project_name: string, root_domain: string, extra_domains: string[], php_version: string}
 */
function create_default_variables(): array
{
    $projectName = 'redirectionio-url-shortener';
    $tld = 'test';

    return [
        'project_name' => $projectName,
        'root_domain' => "{$projectName}.{$tld}",
        'extra_domains' => [
            "www.{$projectName}.{$tld}",
        ],
        'php_version' => $_SERVER['DS_PHP_VERSION'] ?? '8.3',
    ];
}

#[AsTask(description: 'Builds and starts the infrastructure, then install the application (composer, yarn, ...)')]
function start(): void
{
    io()->title('Starting the stack');

    // workers_stop();
    generate_certificates(force: false);
    build();
    up(profiles: ['default']); // We can't start worker now, they are not installed
    cache_clear();
    install();
    front_build();
    cache_clear();
    // workers_start();

    notify('The stack is now up and running.');
    io()->success('The stack is now up and running.');

    about();
}

#[AsTask(description: 'Installs the application (composer, yarn, ...)', namespace: 'app', aliases: ['install'])]
function install(): void
{
    io()->title('Installing the application');

    io()->section('Installing PHP dependencies');
    docker_compose_run('composer install -n --prefer-dist --optimize-autoloader');

    io()->section('Installing importmap');
    docker_compose_run('bin/console importmap:install');

    qa\install();
}

#[AsTask(description: 'Builds the front-end assets', namespace: 'app', aliases: ['front-build'])]
function front_build(): void
{
    io()->title('Building the front-end assets');

    docker_compose_run('bin/console tailwind:build');
    docker_compose_run('bin/console asset-map:compile');
}

#[AsTask(description: 'Clear the application cache', namespace: 'app', aliases: ['cache-clear'])]
function cache_clear(): void
{
    io()->title('Clearing the application cache');

    docker_compose_run('rm -rf var/cache/');

    // On the very first run, the vendor does not exist yet
    if (is_dir(variable('root_dir') . '/application/vendor')) {
        docker_compose_run('bin/console cache:warmup', c: context()->withAllowFailure());
    }
}
