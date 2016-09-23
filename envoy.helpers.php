<?php
function runCommand($command) {
    return implode(PHP_EOL, [
        "echo -e '" . formatMessage($command, 'blue') . "'",
        $command,
    ]) . PHP_EOL;
}

function executeHook($hooks, $eventName, $taskName, $releaseFolder = '') {
    $commandArr = [];
    $commandArr[] = "echo -e 'Hook " . formatMessage($taskName . '.' . $eventName, 'bgreen') . " ...'";

    if (!empty($hooks[$eventName][$taskName])) {
        if (!empty($releaseFolder)) {
            $cdCommand = "cd $releaseFolder";
            $commandArr[] = "echo -e '" . formatMessage($cdCommand, 'blue') . "'";
            $commandArr[] = $cdCommand;
        }

        foreach ($hooks[$eventName][$taskName] as $command) {
            $commandArr[] = "echo -e '" . formatMessage($command, 'blue') . "'";
            $commandArr[] = $command;
        }
    }

    $commandArr[] = "echo -e 'Hook " . formatMessage($taskName . '.' . $eventName, 'bgreen') . " done.'";

    return arrayToEnvoyCommands($commandArr);
}

function arrayToEnvoyCommands($commandArr) {
    return implode(PHP_EOL, $commandArr) . PHP_EOL;
}

function formatMessage($message, $color = 'bgreen') {
    $colorSymbolArr = colorSymbolArray();
    $color = strtolower($color);

    if (isset($colorSymbolArr[$color])) {
        $message = $colorSymbolArr[$color] . $message . $colorSymbolArr['off'];
    }

    return $message;
}

function colorSymbolArray() {
    return [
        'off' => '\e[0m',

        // Regular Colors
        'black' => '\e[0;30m',
        'red' => '\e[0;31m',
        'green' => '\e[0;32m',
        'yellow' => '\e[0;33m',
        'blue' => '\e[0;34m',
        'purple' => '\e[0;35m',
        'cyan' => '\e[0;36m',
        'white' => '\e[0;37m',

        // Bold
        'bblack' => '\e[1;30m',
        'bred' => '\e[1;31m',
        'bgreen' => '\e[1;32m',
        'byellow' => '\e[1;33m',
        'bblue' => '\e[1;34m',
        'bpurple' => '\e[1;35m',
        'bcyan' => '\e[1;36m',
        'bwhite' => '\e[1;37m',

        // Underline
        'ublack' => '\e[4;30m',
        'ured' => '\e[4;31m',
        'ugreen' => '\e[4;32m',
        'uyellow' => '\e[4;33m',
        'ublue' => '\e[4;34m',
        'upurple' => '\e[4;35m',
        'ucyan' => '\e[4;36m',
        'uwhite' => '\e[4;37m',

        // Background
        'bgblack' => '\e[40m',
        'bgred' => '\e[41m',
        'bggreen' => '\e[42m',
        'bgyellow' => '\e[43m',
        'bgblue' => '\e[44m',
        'bgpurple' => '\e[45m',
        'bgcyan' => '\e[46m',
        'bgwhite' => '\e[47m',
    ];
}
