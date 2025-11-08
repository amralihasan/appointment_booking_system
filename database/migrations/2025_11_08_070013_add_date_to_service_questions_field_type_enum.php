<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // For MySQL/MariaDB, we need to modify the enum using raw SQL
            DB::statement("ALTER TABLE service_questions MODIFY COLUMN field_type ENUM('text', 'email', 'number', 'textarea', 'select_one', 'select_multiple', 'date')");
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support modifying CHECK constraints directly
            // We need to recreate the table with the updated constraint
            DB::statement('CREATE TABLE service_questions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                service_id INTEGER NOT NULL,
                question_text VARCHAR(255) NOT NULL,
                field_type VARCHAR(255) NOT NULL CHECK (field_type IN (\'text\', \'email\', \'number\', \'textarea\', \'select_one\', \'select_multiple\', \'date\')),
                options TEXT,
                is_required INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            )');

            // Copy data from old table to new table
            DB::statement('INSERT INTO service_questions_new SELECT * FROM service_questions');

            // Drop old table
            DB::statement('DROP TABLE service_questions');

            // Rename new table
            DB::statement('ALTER TABLE service_questions_new RENAME TO service_questions');

            // Recreate indexes
            DB::statement('CREATE INDEX service_questions_service_id_index ON service_questions(service_id)');
            DB::statement('CREATE INDEX service_questions_sort_order_index ON service_questions(sort_order)');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL doesn't support ENUM directly, but if using a check constraint, we'd need to drop and recreate
            // For now, we'll assume it's using a VARCHAR with check constraint or just VARCHAR
            // This migration will work if the column is already VARCHAR (which is common in PostgreSQL)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // For MySQL/MariaDB, remove 'date' from enum
            DB::statement("ALTER TABLE service_questions MODIFY COLUMN field_type ENUM('text', 'email', 'number', 'textarea', 'select_one', 'select_multiple')");
        } elseif ($driver === 'sqlite') {
            // Recreate table without 'date' in the CHECK constraint
            DB::statement('CREATE TABLE service_questions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                service_id INTEGER NOT NULL,
                question_text VARCHAR(255) NOT NULL,
                field_type VARCHAR(255) NOT NULL CHECK (field_type IN (\'text\', \'email\', \'number\', \'textarea\', \'select_one\', \'select_multiple\')),
                options TEXT,
                is_required INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            )');

            // Copy data from old table to new table (excluding any 'date' values)
            DB::statement('INSERT INTO service_questions_new SELECT * FROM service_questions WHERE field_type != \'date\'');

            // Drop old table
            DB::statement('DROP TABLE service_questions');

            // Rename new table
            DB::statement('ALTER TABLE service_questions_new RENAME TO service_questions');

            // Recreate indexes
            DB::statement('CREATE INDEX service_questions_service_id_index ON service_questions(service_id)');
            DB::statement('CREATE INDEX service_questions_sort_order_index ON service_questions(sort_order)');
        }
    }
};
