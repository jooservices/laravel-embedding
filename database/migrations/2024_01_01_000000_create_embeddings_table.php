<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     * Reads from the package config, defaulting to 'pgsql'.
     */
    public function getConnection(): ?string
    {
        return config('embedding.database.connection', 'pgsql');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = config('embedding.database.table', 'embeddings');
        $connection = Schema::connection($this->getConnection())->getConnection();
        $isPgsql = $connection->getDriverName() === 'pgsql';

        if ($isPgsql && (bool) config('embedding.database.pgvector.ensure_extension', false)) {
            $connection->statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::connection($this->getConnection())->create($table, static function (Blueprint $table) use ($isPgsql): void {
            $table->id();

            // Polymorphic relationship: link embeddings to any Eloquent model.
            // Both fields are nullable to support purely text-based (non-model) embeddings.
            $table->nullableMorphs('embeddable');

            // Package-level target reference for both Eloquent and non-Eloquent sources.
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();

            // Provider and model metadata.
            $table->string('provider', 64);
            $table->string('model', 128);

            // Vector dimension — used for integrity checks on load.
            $table->unsignedInteger('dimension');

            // Chunk position within the original text.
            $table->unsignedInteger('chunk_index')->default(0);

            // The original text chunk that was embedded.
            $table->text('content');

            // SHA-256 hash of `content` for fast deduplication / change detection.
            $table->char('content_hash', 64)->index();

            // PostgreSQL supports pgvector search.
            // Other drivers may persist vectors, but not search them.
            if ($isPgsql) {
                $table->addColumn('vector', 'embedding');
            } else {
                $table->json('embedding');
            }

            $table->string('namespace')->nullable();

            // Arbitrary metadata (e.g., source file, language, tags).
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(
                ['embeddable_type', 'embeddable_id', 'provider', 'model', 'content_hash', 'chunk_index'],
                'embeddings_identity_idx',
            );
            $table->index(
                ['target_type', 'target_id', 'namespace', 'provider', 'model'],
                'embeddings_target_lookup_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = config('embedding.database.table', 'embeddings');

        Schema::connection($this->getConnection())->dropIfExists($table);
    }
};
