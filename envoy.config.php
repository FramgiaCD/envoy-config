<?php

/**
 * Config block
 */
$config = [

    // application name
    'application_name' => 'project_envoy',

    // The default remote connection(s) to execute tasks on
    'default' => ['local'],

    /**
     * connection settings
     * @example row set: 'webserver1'=>['-p 2222 vagrant@127.0.0.1'],
     * @example row set: 'webserver2'=>['user@191.168.1.10 -p2222'],
     * @example row set: 'root@example.com',
     */
    'connections'      => [
        'local' => '-p 9999 deploy@127.0.0.1',
        'staging' => 'deploy@staginghost',
    ],
];

/**
 * Remote block
 */
$remote = [
    // Remote server
    //////////////////////////////////////////////////////////////////////

    // The number of releases to keep at all times
    'keep_releases' => 4,

    // Folders
    ////////////////////////////////////////////////////////////////////

    // The root directory where your applications will be deployed
    // This path *needs* to start at the root, ie. start with a /
    'root_directory' => '/home/deploy',

    // A list of folders/file to be shared between releases
    // Use this to list folders that need to keep their state, like
    // user uploaded data, file-based databases, etc.
    'shared' => [
        'storage',
        '.env'
    ],

    // Permissions
    ////////////////////////////////////////////////////////////////////

    'permissions' => [

        // The folders and files to set as web writable
        'files' => [
            'storage',
        ],

        // Here you can configure what actions will be executed to set
        // permissions on the folder above. The Closure can return
        // a single command as a string or an array of commands
        'callback' => function ($file) {
            return [
                sprintf('chmod -R 777 %s', $file),
            ];
        },

    ],

    // Dependencies
    ////////////////////////////////////////////////////////////////////

    'dependencies' => [
        // Which dependencies component will run after cloning code
        'components' => [
            'composer' => true,
            'npm' => true,
            'bower' => false,
            'gulp' => true,
        ],
        // Which commands run associate with components above
        'commands' => [
            'composer' => 'composer install --prefer-dist --no-scripts --no-interaction && composer dump-autoload --optimize',
            'npm' => 'npm install',
            'bower' => 'bower install',
            'gulp' => 'gulp',
        ]
    ],

];

/**
 * Scm block
 */
$scm = [
    // SCM repository
    //////////////////////////////////////////////////////////////////////

    // The SCM used (only supported "git")

    // The SSH address to your repository
    // Example: git@github.com:username/repository.git
    'repository' => 'git@github.com:nguyenthanhtung88/laravel53-cd-demo.git',

    // The branch to deploy
    'branch' => 'develop',

    // Recursively pull in submodules. Works only with GIT.
    'submodules' => true,
];

/**
 * Hooks block
 */
$hooks = [
    // Tasks to execute before the core Tasks
    'before' => [
        'setup' => [],
        'deploy' => [],
        'dependencies' => [],
        'symlink' => [
            'php artisan migrate --force'
        ],
    ],

    // Tasks to execute after the core Tasks
    'after' => [
        'setup' => [
            'touch ' . $remote['root_directory'] . '/' . $config['application_name'] . '/shared/.env',
        ],
        'deploy'  => [
            // 'sudo /etc/init.d/php7.0-fpm restart',
            // 'sudo /etc/init.d/nginx restart'
        ],
        'dependencies' => [],
        'symlink' => [],
    ],
];
