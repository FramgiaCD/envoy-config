# Configuration for deploying Laravel Project using Envoy

## Introduction

- Inspired by [Rocketeer](http://rocketeer.autopergamene.eu/) and [Envoy](https://laravel.com/docs/5.3/envoy)
- Configuration defined in `envoy.config.php`.
- Remote folder structure:
```
| home
|-- deploy
  |-- project_envoy
    |-- current -> /home/deploy/project_envoy/releases/20160923070018
    |-- releases
    |   |-- 20160923022158
    |   |-- 20160923024741
    |   |-- 20160923025123
    |   |-- 20160923030315
    |   |-- 20160923070018
    |   |   |-- storage => /home/deploy/project_envoy/shared/storage
    |   |   |-- .env => /home/deploy/project_envoy/shared/.env
    |-- shared
    |   |-- storage
    |   |-- .env
```

- `/home/deploy` defined in `$remote['root_directory']`
- `project_envoy` defined in `$config['application_name']`
- `current`, `releases` and `shared` folder created by default

## Workflow
We have 3 main stories (AKA commands):

- `setup`: setup main folders on remote
    - Commans setting in `$hook['before']['setup']` and `$hooks['after']['setup']` take effect here
- `deploy`: Group of commands
```
show_env            // Show current state before deploying
setup_folder        // Setup folder if any
before_deploy       // $hooks['before']['deploy']
create_release      // Create current release folder
clone               // Clone code from github ($scm['repository'])
submodules          // Init submodules if any ($scm['submodules'])
dependencies_before // $hooks['before']['dependencies']
dependencies        // Run commands setting in $remote['dependencies']
dependencies_after  // $hooks['after']['dependencies']
permissions         // Setting permissions ($remote['permissions'])
sharing             // Setup sharing folders or files ($remote['shared'])
symlink_before      // $hooks['before']['symlink']
symlink             // Symlink current release folder to current folder
symlink_after       // $hooks['after']['symlink']
after_deploy        // $hooks['after']['deploy']
cleanup             // Cleanup old releases only keep $remote['keep_releases']
```
- `rollback`: Only symlink, you have to rollback migration manually if need. 2 ways:
    - `envoy run rollback`: rollback to previous version
    - `envoy run rollback --rollback_version=20160923022158`: rollback to custom version

**Note:** By default, all tasks run with default connection (`$config['default']`) and default branch (`$scm['branch']`). You can manually define your `connection` and `branch` like this:
```
envoy run deploy --on=local,staging --branch=develop
// OR
envoy run deploy --on=production --branch=master
```

## Integration with Laravel Project
- Download 3 `php` files (`Envoy.blade.php`, `envoy.config.php`, `envoy.helpers.php`) and move to the root project folder
- Change some default configuration in `envoy.config.php`
- Run `envoy run setup` first to create folders in remote server
- SSH into server, add configuration to `.env` file inside `shared` folder
- Run `deploy` command and see the result.
