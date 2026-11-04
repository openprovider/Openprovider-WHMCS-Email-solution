<?php

namespace Module\Server\emailSolution\MigrationRunner;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

/**
 * Runs pending migrations from ../migrations once each.
 *
 * Uses the same modwMigrations table and layout as the registrar module,
 * scoped by module + type, so modules can share the table safely.
 */
class MigrationRunner
{
    const TABLE = 'modwMigrations';
    const MODULE = 'email_solution';
    const TYPE = 'server';

    public function run()
    {
        $this->createRepositoryIfNotExist();

        $ran = Capsule::table(self::TABLE)
            ->where('module', self::MODULE)
            ->where('type', self::TYPE)
            ->pluck('migration');
        // WHMCS 8 returns a Collection, WHMCS 7 an array.
        $ran = is_object($ran) ? $ran->all() : $ran;

        $files = glob(__DIR__ . '/../migrations/*_*.php') ?: [];
        sort($files);

        $batch = null;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }

            if ($batch === null) {
                $batch = $this->getLastBatchNumber() + 1;
            }

            require_once $file;
            $class = $this->getClassName($name);
            (new $class())->up();

            // Only recorded after up() succeeds, so a failed migration is retried on the next run.
            Capsule::table(self::TABLE)->insert([
                'module' => self::MODULE,
                'type' => self::TYPE,
                'migration' => $name,
                'batch' => $batch,
            ]);
        }
    }

    private function createRepositoryIfNotExist()
    {
        if (Capsule::schema()->hasTable(self::TABLE)) {
            return;
        }

        Capsule::schema()->create(self::TABLE, function ($table) {
            $table->increments('id');
            $table->string('module');
            $table->string('type');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    private function getLastBatchNumber()
    {
        return (int) Capsule::table(self::TABLE)
            ->where('module', self::MODULE)
            ->where('type', self::TYPE)
            ->max('batch');
    }

    // 2026_11_04_000000_mask_email_solution_credentials_in_logs => MaskEmailSolutionCredentialsInLogs
    private function getClassName($name)
    {
        $words = array_slice(explode('_', $name), 4);
        return str_replace(' ', '', ucwords(implode(' ', $words)));
    }
}
