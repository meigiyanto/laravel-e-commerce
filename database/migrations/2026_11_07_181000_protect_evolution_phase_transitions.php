<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'evolution_phase_transitions';

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->createSqliteTriggers(),
            'mysql', 'mariadb' => $this->createMysqlTriggers(),
            'pgsql' => $this->createPostgresTriggers(),
            'sqlsrv' => $this->createSqlServerTrigger(),
            default => throw new RuntimeException(
                "Unsupported database driver: {$driver}"
            ),
        };
    }

    private function createSqliteTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_insert
            BEFORE INSERT ON {$this->table}
            WHEN NEW.status IS NOT 'pending'
                OR NEW.decided_by IS NOT NULL
                OR NEW.decision_rationale IS NOT NULL
                OR NEW.decided_at IS NOT NULL
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Phase transition must start as pending'
                );
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_update
            BEFORE UPDATE ON {$this->table}
            WHEN OLD.status IS NOT 'pending'
                OR NEW.status NOT IN ('approved', 'rejected')
                OR NEW.program_id IS NOT OLD.program_id
                OR NEW.from_phase_id IS NOT OLD.from_phase_id
                OR NEW.to_phase_id IS NOT OLD.to_phase_id
                OR NEW.assessment_id IS NOT OLD.assessment_id
                OR NEW.requested_by IS NOT OLD.requested_by
                OR NEW.rationale IS NOT OLD.rationale
                OR NEW.created_at IS NOT OLD.created_at
                OR NEW.decided_by IS NULL
                OR NEW.decided_by = NEW.requested_by
                OR NEW.decision_rationale IS NULL
                OR LENGTH(TRIM(NEW.decision_rationale)) = 0
                OR NEW.decided_at IS NULL
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Invalid phase transition decision'
                );
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_delete
            BEFORE DELETE ON {$this->table}
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Phase transitions cannot be deleted'
                );
            END
        ");
    }

    private function createMysqlTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_insert
            BEFORE INSERT ON {$this->table}
            FOR EACH ROW
            BEGIN
                IF NEW.status <> 'pending'
                    OR NEW.decided_by IS NOT NULL
                    OR NEW.decision_rationale IS NOT NULL
                    OR NEW.decided_at IS NOT NULL
                THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Phase transition must start as pending';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_update
            BEFORE UPDATE ON {$this->table}
            FOR EACH ROW
            BEGIN
                IF OLD.status <> 'pending'
                    OR NEW.status NOT IN ('approved', 'rejected')
                    OR NOT (NEW.program_id <=> OLD.program_id)
                    OR NOT (NEW.from_phase_id <=> OLD.from_phase_id)
                    OR NOT (NEW.to_phase_id <=> OLD.to_phase_id)
                    OR NOT (NEW.assessment_id <=> OLD.assessment_id)
                    OR NOT (NEW.requested_by <=> OLD.requested_by)
                    OR NOT (NEW.rationale <=> OLD.rationale)
                    OR NOT (NEW.created_at <=> OLD.created_at)
                    OR NEW.decided_by IS NULL
                    OR NEW.decided_by = NEW.requested_by
                    OR NEW.decision_rationale IS NULL
                    OR CHAR_LENGTH(TRIM(NEW.decision_rationale)) = 0
                    OR NEW.decided_at IS NULL
                THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Invalid phase transition decision';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_delete
            BEFORE DELETE ON {$this->table}
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Phase transitions cannot be deleted'
        ");
    }

    private function createPostgresTriggers(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION
                evolution_protect_phase_transition()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    IF NEW.status IS DISTINCT FROM 'pending'
                        OR NEW.decided_by IS NOT NULL
                        OR NEW.decision_rationale IS NOT NULL
                        OR NEW.decided_at IS NOT NULL
                    THEN
                        RAISE EXCEPTION
                            'Phase transition must start as pending';
                    END IF;

                    RETURN NEW;
                END IF;

                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION
                        'Phase transitions cannot be deleted';
                END IF;

                IF OLD.status IS DISTINCT FROM 'pending'
                    OR NEW.status NOT IN ('approved', 'rejected')
                    OR NEW.program_id IS DISTINCT FROM OLD.program_id
                    OR NEW.from_phase_id IS DISTINCT FROM OLD.from_phase_id
                    OR NEW.to_phase_id IS DISTINCT FROM OLD.to_phase_id
                    OR NEW.assessment_id IS DISTINCT FROM OLD.assessment_id
                    OR NEW.requested_by IS DISTINCT FROM OLD.requested_by
                    OR NEW.rationale IS DISTINCT FROM OLD.rationale
                    OR NEW.created_at IS DISTINCT FROM OLD.created_at
                    OR NEW.decided_by IS NULL
                    OR NEW.decided_by = NEW.requested_by
                    OR NEW.decision_rationale IS NULL
                    OR LENGTH(TRIM(NEW.decision_rationale)) = 0
                    OR NEW.decided_at IS NULL
                THEN
                    RAISE EXCEPTION
                        'Invalid phase transition decision';
                END IF;

                RETURN NEW;
            END;
            $$
        ");

        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_protection
            BEFORE INSERT OR UPDATE OR DELETE
            ON {$this->table}
            FOR EACH ROW
            EXECUTE FUNCTION evolution_protect_phase_transition()
        ");
    }

    private function createSqlServerTrigger(): void
    {
        DB::unprepared("
            CREATE TRIGGER trg_evp_transition_protection
            ON {$this->table}
            AFTER INSERT, UPDATE, DELETE
            AS
            BEGIN
                SET NOCOUNT ON;

                IF EXISTS (SELECT 1 FROM deleted)
                    AND NOT EXISTS (SELECT 1 FROM inserted)
                BEGIN
                    THROW 51000,
                        'Phase transitions cannot be deleted', 1;
                END;

                IF NOT EXISTS (SELECT 1 FROM deleted)
                    AND EXISTS (
                        SELECT 1
                        FROM inserted
                        WHERE status <> 'pending'
                            OR decided_by IS NOT NULL
                            OR decision_rationale IS NOT NULL
                            OR decided_at IS NOT NULL
                    )
                BEGIN
                    THROW 51000,
                        'Phase transition must start as pending', 1;
                END;

                IF EXISTS (SELECT 1 FROM deleted)
                    AND EXISTS (SELECT 1 FROM inserted)
                    AND EXISTS (
                        SELECT 1
                        FROM inserted i
                        INNER JOIN deleted d ON d.id = i.id
                        WHERE d.status <> 'pending'
                            OR i.status NOT IN ('approved', 'rejected')
                            OR i.program_id <> d.program_id
                            OR i.from_phase_id <> d.from_phase_id
                            OR i.to_phase_id <> d.to_phase_id
                            OR i.assessment_id <> d.assessment_id
                            OR i.requested_by <> d.requested_by
                            OR i.rationale <> d.rationale
                            OR i.created_at <> d.created_at
                            OR i.decided_by IS NULL
                            OR i.decided_by = i.requested_by
                            OR i.decision_rationale IS NULL
                            OR LEN(LTRIM(RTRIM(i.decision_rationale))) = 0
                            OR i.decided_at IS NULL
                    )
                BEGIN
                    THROW 51000,
                        'Invalid phase transition decision', 1;
                END;
            END
        ");
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared("
                DROP TRIGGER IF EXISTS
                    trg_evp_transition_protection
                ON {$this->table}
            ");

            DB::unprepared("
                DROP FUNCTION IF EXISTS
                    evolution_protect_phase_transition()
            ");

            return;
        }

        if ($driver === 'sqlsrv') {
            DB::unprepared("
                DROP TRIGGER IF EXISTS
                    trg_evp_transition_protection
            ");

            return;
        }

        foreach ([
            'trg_evp_transition_insert',
            'trg_evp_transition_update',
            'trg_evp_transition_delete',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }
    }
};
