<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('embedding.database.connection', 'pgsql');
    }

    public function up(): void
    {
        $connection = $this->getConnection();
        $embeddingTable = config('embedding.database.table', 'embeddings');
        $batchTable = config('embedding.database.batch_table', 'embedding_batches');

        Schema::connection($connection)->table($embeddingTable, static function (Blueprint $table): void {
            $table->string('batch_token')->nullable()->after('content_hash');
            $table->boolean('is_active')->default(true)->after('batch_token');
            $table->index(['target_type', 'target_id', 'namespace', 'is_active'], 'embeddings_active_target_idx');
            $table->index(['batch_token', 'is_active'], 'embeddings_batch_token_idx');
        });

        Schema::connection($connection)->create($batchTable, static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('namespace')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('completed_chunks')->default(0);
            $table->unsignedInteger('failed_chunks')->default(0);
            $table->boolean('replace_existing')->default(false);
            $table->boolean('skip_if_unchanged')->default(false);
            $table->string('staged_batch_token')->nullable();
            $table->string('source_format', 32)->nullable();
            $table->string('source_path')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id', 'namespace', 'status'], 'embedding_batches_target_status_idx');
        });
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);
        $embeddingTable = config('embedding.database.table', 'embeddings');
        $batchTable = config('embedding.database.batch_table', 'embedding_batches');

        $schema->dropIfExists($batchTable);

        if (! $schema->hasTable($embeddingTable)) {
            return;
        }

        $driver = $schema->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $schema->dropIfExists($embeddingTable);

            return;
        }

        $schema->table($embeddingTable, static function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn($table->getTable(), 'batch_token')) {
                $table->dropIndex('embeddings_batch_token_idx');
            }

            if ($schema->hasColumn($table->getTable(), 'is_active')) {
                $table->dropIndex('embeddings_active_target_idx');
            }

            if ($schema->hasColumn($table->getTable(), 'batch_token')) {
                $table->dropColumn('batch_token');
            }

            if ($schema->hasColumn($table->getTable(), 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
