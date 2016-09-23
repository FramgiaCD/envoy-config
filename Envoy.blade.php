@include('envoy.config.php');
@include('envoy.helpers.php');

@setup
    if (!isset($config) || !isset($remote) || !isset($scm)) {
        throw new Exception('Lack of envoy.config.php file');
    }

    if (empty($config['application_name']) ) {
        throw new Exception('App Name is not set');
    }

    if (empty($config['connections']) ) {
        throw new Exception('Server connections(SSH login username/host is not set)');
    }
    if (empty($scm['repository'])){
        throw new Exception('VCS Source repository is not set');
    }

    if (empty($remote['root_directory'])) {
        throw new Exception('Root directory is not set');
    }

    if (empty($remote['keep_releases']) || intval($remote['keep_releases']) < 1) {
        throw new Exception('Release Keep Count must greater than 1');
    }

    if (substr($remote['root_directory'], 0, 1) !== '/') {
        throw new Exception('Careful - your root directory does not begin with /');
    }

    $now = new DateTime();
    $dateDisplay = $now->format('Y-m-d H:i:s');
    $release = $now->format('YmdHis');
    $branch = isset($branch) ? $branch : $scm['branch'];
    $rollbackVersion = isset($rollback_version) ? $rollback_version : '123';

    $rootDirectory = rtrim($remote['root_directory'], '/');
    $appDir = $rootDirectory . '/' . $config['application_name'];
    $releaseDir = $appDir . '/releases';
    $sharedDir = $appDir . '/shared';
    $currentDir = $appDir . '/current';

    $serverLabelArr = isset($on) ? explode(',', $on) : $config['default'];

    if (empty($serverLabelArr)) {
        throw new Exception('No remote connection to execute');
    }

    $remoteServers = [];
    foreach ($config['connections'] as $remoteLabel => $remoteHost) {
        if (in_array($remoteLabel, $serverLabelArr)) {
            $remoteServers[$remoteLabel] = $remoteHost;
        }
    }

    if (empty($remoteServers)) {
        throw new Exception('No servers to deploy');
    }

    $sourceRepo = $scm['repository'];
    $currentReleaseDir = $releaseDir . '/' . $release;

@endsetup

@servers($remoteServers)

@story('setup')
    setup_folder
@endstory

@task('setup_folder', ['parallel' => true])
    echo "Setup folder ...";

    if [ ! -d {{ $currentDir }} ]; then
        {{ executeHook($hooks, 'before', 'setup') }}

        if [ ! -d {{ $appDir }} ]; then
            {{ runCommand("mkdir -p $appDir") }}
        fi

        if [ ! -d {{ $releaseDir }} ]; then
            {{ runCommand("mkdir -p $releaseDir") }}
        fi

        if [ ! -d {{ $sharedDir }} ]; then
            {{ runCommand("mkdir -p $sharedDir") }}
        fi

        {{ runCommand("mkdir -p $currentDir") }}

        {{ executeHook($hooks, 'after', 'setup') }}
    fi

    echo "Setup folder done.";
@endtask

@story('deploy')
    show_env
    setup_folder
    before_deploy
    create_release
    clone
    submodules
    dependencies_before
    dependencies
    dependencies_after
    permissions
    sharing
    symlink_before
    symlink
    symlink_after
    after_deploy
    cleanup
@endstory

@story('rollback')
    rollback_version
@endstory

@task('show_env', ['parallel' => true])
    echo "...[execute at remote]"
    echo -e "Current Release Name: {{ formatMessage($release, 'bcyan') }}"
    echo -e "Current Branch is {{ formatMessage($branch, 'bcyan') }}"
    echo -e "Deployment Start at {{ formatMessage($dateDisplay, 'bcyan') }}"
    echo "----"
@endtask

@task('create_release', ['parallel' => true])
    echo -e "Create current {{ formatMessage('release folder') }} ..."

    {{ runCommand("mkdir -p $currentReleaseDir") }}

    echo -e "Create current {{ formatMessage('release folder') }} done."
@endtask

@task('clone', ['parallel' => true])
    echo -e "Clone {{ formatMessage('repository') }} ..."

    {{ runCommand("git clone $sourceRepo $currentReleaseDir --branch=$branch --depth=1") }}

    echo -e "Clone {{ formatMessage('repository') }} done."
@endtask

@task('submodules', ['parallel' => true])
    echo -e "Init {{ formatMessage('submodules') }} if any ..."

    if [ {{ intval($scm['submodules']) }} -eq 1 ]; then
        {{ runCommand("cd $currentReleaseDir") }}
        {{ runCommand("git submodule update --init --recursive") }}

        echo -e "Init {{ formatMessage('submodules') }} done."
    fi
@endtask

@task('dependencies', ['parallel' => true])
    echo -e "Install {{ formatMessage('dependencies') }} ..."

    @if (isset($remote['dependencies']['components']) && !empty($remote['dependencies']['components']))
        @foreach ($remote['dependencies']['components'] as $component => $isRun)
            @if ($isRun && !empty($remote['dependencies']['commands'][$component]))
                echo -e "-- {{ formatMessage('Dependencies/' . ucfirst($component)) }} ..."

                {{ runCommand("cd $currentReleaseDir") }}
                {{ runCommand($remote['dependencies']['commands'][$component]) }}
            @endif
        @endforeach
    @endif

    echo -e "Install {{ formatMessage('dependencies') }} done."
@endtask

@task('permissions', ['parallel' => true])
    echo -e "Setting {{ formatMessage('permissions') }} ..."

    @if (!empty($remote['permissions']['files']))
        {{ runCommand("cd $currentReleaseDir") }}

        @foreach ($remote['permissions']['files'] as $file)
            @if (!empty($remote['permissions']['callback']($file)))
                @foreach ($remote['permissions']['callback']($file) as $command)
                    {{ runCommand($command) }}
                @endforeach
            @else
                {{ runCommand("chmod -R 755 $file") }}
            @endif
        @endforeach
    @endif

    echo -e "Setting {{ formatMessage('permissions') }} done."
@endtask

@task('sharing', ['parallel' => true])
    echo -e "{{ formatMessage('Sharing') }} folders or files ..."

    @if (!empty($remote['shared']))
        @foreach ($remote['shared'] as $share)
            if [ ! -e {{ $sharedDir . '/' . $share }} ]; then
                {{ runCommand("mv $currentReleaseDir/$share $sharedDir/$share") }}
            fi

            if [ ! -L {{ $currentReleaseDir . '/' . $share }} ]; then
                {{ runCommand("rm -rf $currentReleaseDir/$share") }}
            fi

            {{ runCommand("ln -s $sharedDir/$share $currentReleaseDir/$share-temp") }}
            {{ runCommand("mv -Tf $currentReleaseDir/$share-temp $currentReleaseDir/$share") }}
        @endforeach
    @endif

    echo -e "{{ formatMessage('Sharing') }} folders or files done."
@endtask

@task('symlink', ['parallel' => true])
    echo -e "{{ formatMessage('Symbolic link') }} ..."

    if [ ! -e {{ $currentReleaseDir }} ]; then
        if [ ! -e {{ $currentDir }} ]; then
            echo "Nothing to symlink!!!"
            exit 1
        fi

        {{ runCommand("mv $currentDir $currentReleaseDir") }}
    fi

    if [ -e {{ $currentDir }} ] && [ ! -L {{ $currentDir }} ]; then
        {{ runCommand("rm -rf $currentDir") }}
    fi

    {{ runCommand("ln -s $currentReleaseDir $currentDir-temp") }}
    {{ runCommand("mv -Tf $currentDir-temp $currentDir") }}

    echo -e "{{ formatMessage('Symbolic link') }} done."
@endtask

@task('cleanup', ['parallel' => true])
    echo -e "{{ formatMessage('Cleanup') }} up old releases ...";
    cd {{ $releaseDir }};
    (ls -rd {{ $releaseDir }}/*|head -n {{ intval($remote['keep_releases'] + 1) }};ls -d {{ $releaseDir }}/*)|sort|uniq -u|xargs rm -rf;
    echo -e "{{ formatMessage('Cleanup') }} up old releases done.";
@endtask

@task('rollback_version', ['parallel' => true])
    echo -e "{{ formatMessage('Rollback') }} previous version ..."

    releases=($((ls -d {{ $releaseDir }}/*)|sort|uniq -u))
    currentRelease=$(readlink {{ $currentDir }})
    declare -i previousIndex=-1

    if [ ! -d $currentRelease ]; then
        exit 1
    fi

    if [ {{ intval(empty($rollbackVersion)) }} -eq 1 ]; then
        for i in ${!releases[@]}; do
            if [[ "${releases[$i]}" = "${currentRelease}" ]]; then
                if [ $i -eq 1 ]; then
                    previousIndex=0
                else
                    (( previousIndex=i-1 ))
                fi
                break
            fi
        done
    else
        for i in ${!releases[@]}; do
            if [[ "${releases[$i]}" = "{{ $releaseDir . '/' . $rollbackVersion }}" ]]; then
                (( previousIndex=i ))
                break
            fi
        done
    fi

    if [ $previousIndex -lt 0 ]; then
        echo -e "{{ formatMessage('No previous version found!!!', 'bred') }}"
    else
        ln -nfs ${releases[$previousIndex]} {{ $currentDir }}
        echo "Back to version ${releases[$previousIndex]}"
    fi

    echo -e "{{ formatMessage('Rollback') }} previous version done.";
@endtask

{{-- Hook events --}}
@task('before_deploy', ['parallel' => true])
    {{ executeHook($hooks, 'before', 'deploy') }}
@endtask

@task('after_deploy', ['parallel' => true])
    {{ executeHook($hooks, 'after', 'deploy') }}
@endtask

@task('dependencies_before', ['parallel' => true])
    {{ executeHook($hooks, 'before', 'dependencies', $currentReleaseDir) }}
@endtask

@task('dependencies_after', ['parallel' => true])
    {{ executeHook($hooks, 'after', 'dependencies', $currentReleaseDir) }}
@endtask

@task('symlink_before', ['parallel' => true])
    {{ executeHook($hooks, 'before', 'symlink', $currentReleaseDir) }}
@endtask

@task('symlink_after', ['parallel' => true])
    {{ executeHook($hooks, 'after', 'symlink', $currentReleaseDir) }}
@endtask
