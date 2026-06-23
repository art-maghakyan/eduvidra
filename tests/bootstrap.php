<?php

require __DIR__.'/../vendor/autoload.php';

// PHPUnit's <env> tags call putenv() and update $_ENV, but never touch $_SERVER.
// Laravel's dotenv uses ServerConstAdapter as its first reader (it checks $_SERVER
// before $_ENV / getenv), so a Docker container env var like APP_ENV=local sitting
// in $_SERVER will win over any <env> override in phpunit.xml.
// Syncing $_ENV into $_SERVER here (after PHPUnit has applied its overrides) fixes
// that priority inversion without touching the Docker or phpunit.xml configuration.
foreach ($_ENV as $key => $value) {
    $_SERVER[$key] = $value;
}
