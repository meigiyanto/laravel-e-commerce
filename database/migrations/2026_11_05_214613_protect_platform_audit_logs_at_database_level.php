<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MESSAGE = 'Platform audit logs are immutable.';

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->protectSqlite(),
            'mysql',
            'mariadb' => $this->protectMysql(),
            'pgsql' => $this->protectPostgres(),
            'sqlsrv' => $this->protectSqlServer(),
            default => throw new RuntimeException(
                "Unsupported database driver: {$driver}"
            ),
        };
        
        Schema::table('platform_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['tenant_id']);

            $table->foreign('user_id')
            ->references('id')->on('users')->restrictOnDelete();

            $table->foreign('tenant_id')
            ->references('id')->on('tenants')->restrictOnDelete();
        });
    }

    private function protectSqlite(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_update
            BEFORE UPDATE ON platform_audit_logs
            FOR EACH ROW
            BEGIN
                SELECT RAISE(ABORT, 'Platform audit logs are immutable.');
            END;
        SQL);
    
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_delete
            BEFORE DELETE ON platform_audit_logs
            FOR EACH ROW
            BEGIN
                SELECT RAISE(ABORT, 'Platform audit logs are immutable.');
            END;
        SQL);
    }
    
    private function protectMysql(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_update
            BEFORE UPDATE ON platform_audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Platform audit logs are immutable.';
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_delete
            BEFORE DELETE ON platform_audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Platform audit logs are immutable.';
        SQL);
    }

    private function protectPostgres(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_platform_audit_log_mutation()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION 'Platform audit logs are immutable.';
            END;
            $$;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_mutation
            BEFORE UPDATE OR DELETE ON platform_audit_logs
            FOR EACH ROW
            EXECUTE FUNCTION prevent_platform_audit_log_mutation();
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_truncate
            BEFORE TRUNCATE ON platform_audit_logs
            FOR EACH STATEMENT
            EXECUTE FUNCTION prevent_platform_audit_log_mutation();
        SQL);
    }

    private function protectSqlServer(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER platform_audit_logs_prevent_mutation
            ON platform_audit_logs
            INSTEAD OF UPDATE, DELETE
            AS
            BEGIN
                THROW 51000, 'Platform audit logs are immutable.', 1;
            END;
        SQL);
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->removeSqliteProtection(),
            'mysql', 'mariadb' => $this->removeMysqlProtection(),
            'pgsql' => $this->removePostgresProtection(),
            'sqlsrv' => $this->removeSqlServerProtection(),
            default => throw new RuntimeException(
                "Unsupported database driver: {$driver}"
            ),
        };
        
        Schema::table('platform_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['tenant_id']);
        
            $table->foreign('user_id')
                ->references('id')->on('users')->nullOnDelete();
        
            $table->foreign('tenant_id')
                ->references('id')->on('tenants')->nullOnDelete();
        });
    }

    private function removeSqliteProtection(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS platform_audit_logs_prevent_update'
        );
        DB::unprepared(
            'DROP TRIGGER IF EXISTS platform_audit_logs_prevent_delete'
        );
    }

    private function removeMysqlProtection(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS platform_audit_logs_prevent_update'
        );
        DB::unprepared(
            'DROP TRIGGER IF EXISTS platform_audit_logs_prevent_delete'
        );
    }

    private function removePostgresProtection(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS platform_audit_logs_prevent_mutation
            ON platform_audit_logs;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS platform_audit_logs_prevent_truncate
            ON platform_audit_logs;
        SQL);

        DB::unprepared(
            'DROP FUNCTION IF EXISTS prevent_platform_audit_log_mutation()'
        );
    }

    private function removeSqlServerProtection(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS platform_audit_logs_prevent_mutation'
        );
    }
};