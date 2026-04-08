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

        if ($isPgsql) {
            $connection->statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::connection($this->getConnection())->create($table, static function (Blueprint $table) use ($isPgsql): void {
            $table->id();

            // Polymorphic relationship: link embeddings to any Eloquent model.
            // Both fields are nullable to support purely text-based (non-model) embeddings.
            $table->nullableMorphs('embeddable');

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

            // The embedding vector storage. Uses pgvector on Postgres, fallback to JSON
            if ($isPgsql) {
                // Allows up to 4096 dimensions
                $table->addColumn('vector', 'embedding');
            } else {
                $table->json('embedding');
            }

            // Arbitrary metadata (e.g., source file, language, tags).
            $table->json('meta')->nullable();

            $table->timestamps();

            // Deduplication index: same content chunk for the same target should not
            // be stored twice unless the hash changes.
            $table->unique(['embeddable_type', 'embeddable_id', 'content_hash', 'chunk_index'], 'embeddings_dedup_unique');
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
