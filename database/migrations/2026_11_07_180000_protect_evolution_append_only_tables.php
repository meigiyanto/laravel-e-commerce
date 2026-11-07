<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'evolution_findings',
        'evolution_finding_resolutions',
        'evolution_evidence_assets',
        'evolution_reviews',
        'evolution_approvals',
        'evolution_events',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->createSqliteTriggers(),
            'mysql', 'mariadb' => $this->createMysqlTriggers(),
            'pgsql' => $this->createPostgresTriggers(),
            'sqlsrv' => $this->createSqlServerTriggers(),
            default => throw new RuntimeException(
                "Unsupported database driver: {$driver}"
            ),
        };
    }

    private function createSqliteTriggers(): void
    {
        foreach ($this->tables as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $this->triggerName($table, $operation);

                DB::unprepared("
                    CREATE TRIGGER {$name}
                    BEFORE {$operation} ON {$table}
                    BEGIN
                        SELECT RAISE(
                            ABORT,
                            'Evolution records are append-only'
                        );
                    END
                ");
            }
        }
    }

    private function createMysqlTriggers(): void
    {
        foreach ($this->tables as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $this->triggerName($table, $operation);

                DB::unprepared("
                    CREATE TRIGGER {$name}
                    BEFORE {$operation} ON {$table}
                    FOR EACH ROW
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Evolution records are append-only'
                ");
            }
        }
    }

    private function createPostgresTriggers(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION
                evolution_reject_append_only_mutation()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION
                    'Evolution records are append-only';
            END;
            $$
        ");

        foreach ($this->tables as $table) {
            $name = $this->triggerName($table, 'MUTATION');

            DB::unprepared("
                CREATE TRIGGER {$name}
                BEFORE UPDATE OR DELETE ON {$table}
                FOR EACH ROW
                EXECUTE FUNCTION
                    evolution_reject_append_only_mutation()
            ");
        }
    }

    private function createSqlServerTriggers(): void
    {
        foreach ($this->tables as $table) {
            $name = $this->triggerName($table, 'MUTATION');

            DB::unprepared("
                CREATE TRIGGER [{$name}]
                ON [{$table}]
                AFTER UPDATE, DELETE
                AS
                BEGIN
                    SET NOCOUNT ON;
                    THROW 51000,
                        'Evolution records are append-only',
                        1;
                END
            ");
        }
    }

    private function triggerName(
        string $table,
        string $operation
    ): string {
        $table = str_replace('evolution_', 'evp_', $table);

        return strtolower(
            "trg_{$table}_{$operation}"
        );
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        foreach ($this->tables as $table) {
            if ($driver === 'pgsql') {
                $name = $this->triggerName($table, 'MUTATION');

                DB::unprepared(
                    "DROP TRIGGER IF EXISTS {$name} ON {$table}"
                );

                continue;
            }

            if ($driver === 'sqlsrv') {
                $name = $this->triggerName($table, 'MUTATION');

                DB::unprepared("
                    IF OBJECT_ID(N'{$name}', N'TR') IS NOT NULL
                        DROP TRIGGER [{$name}]
                ");

                continue;
            }

            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $this->triggerName($table, $operation);

                DB::unprepared(
                    "DROP TRIGGER IF EXISTS {$name}"
                );
            }
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                'DROP FUNCTION IF EXISTS
                evolution_reject_append_only_mutation()'
            );
        }
    }
};
