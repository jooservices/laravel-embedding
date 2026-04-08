<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use JOOservices\LaravelEmbedding\Facades\Embedding;
use JOOservices\LaravelEmbedding\Models\Embedding as EmbeddingModel;

// 1. Configure real PostgreSQL DB (container connected on port 54321)
config([
    'database.default' => 'pgsql',
    'database.connections.pgsql' => [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 54321,
        'database' => 'embedding_test',
        'username' => 'embedding',
        'password' => 'secret',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],
    'embedding.database.connection' => 'pgsql',
    'embedding.database.table' => 'embeddings',
    'embedding.database.enabled' => true,

    // Real Ollama Config
    'embedding.default_provider' => 'ollama',
    'embedding.providers.ollama.base_url' => 'http://localhost:11434/api',
    // We use mistral:latest for testing as it was locally pulled
    'embedding.providers.ollama.model' => 'mistral:latest',

    // Tiny chunk size just to trigger many vectors during this demo
    'embedding.chunking.chunk_size' => 30,
    'embedding.chunking.chunk_overlap' => 5,
]);

echo "--------------------------------------------------------\n";
echo "1. Prepare Database\n";
echo "--------------------------------------------------------\n";
Artisan::call('migrate:fresh', ['--force' => true]);
echo "✅ Migrations complete (DB: PostgreSQL 54321).\n\n";

echo "--------------------------------------------------------\n";
echo "2. CASE 1: HAPPY PATH (SINGLE TEXT)\n";
echo "--------------------------------------------------------\n";
try {
    $result = Embedding::embedText('This is a single happy sentence.');
    echo "✅ Single embed success!\n";
    echo "   - Provider: {$result->provider}\n";
    echo "   - Model: {$result->model}\n";
    echo "   - Vector dimension: {$result->dimension}\n";
    echo '   - Total DB records: '.EmbeddingModel::count()."\n\n";
} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n\n";
}

echo "--------------------------------------------------------\n";
echo "3. CASE 2: HAPPY PATH (CHUNK & EMBED BATCH, WITH META)\n";
echo "--------------------------------------------------------\n";
try {
    $largeText = 'Đây là một đoạn test batch. Vì chunk_size được config chỉ là 30 ký tự, nên đoạn văn bản này chắc chắn sẽ bị cắt thành ít nhất ba hoặc bốn chunks. Hệ thống sẽ call batch để nhúng tất cả.';
    $batch = Embedding::chunkAndEmbed($largeText, ['source' => 'demo-script']);

    echo "✅ Batch chunk & embed success!\n";
    echo "   - Total chunks generated: {$batch->count()}\n";
    echo "   - DB records expected to increase by: {$batch->count()}\n";
    echo '   - New Total DB records: '.EmbeddingModel::count()."\n";
    echo "   - Inspecting first chunk hash & metadata: \n";
    $firstRecord = EmbeddingModel::latest('id')->first();
    echo '     * DB Meta source: '.($firstRecord->meta['source'] ?? 'null')."\n";
    echo '     * DB Hash: '.$firstRecord->content_hash."\n\n";
} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n\n";
}

echo "--------------------------------------------------------\n";
echo "4. CASE 3: UNHAPPY PATH (EMPTY INPUT)\n";
echo "--------------------------------------------------------\n";
try {
    Embedding::embedText('   ');
    echo "❌ Failed: Expected an exception but none was thrown.\n\n";
} catch (Exception $e) {
    echo "✅ Caught expected exception (Empty text):\n";
    echo '   - Exception: '.get_class($e)."\n";
    echo '   - Msg: '.$e->getMessage()."\n\n";
}

echo "--------------------------------------------------------\n";
echo "5. CASE 4: UNHAPPY PATH (OLLAMA CONNECTION FAILED)\n";
echo "--------------------------------------------------------\n";
try {
    config(['embedding.providers.ollama.base_url' => 'http://localhost:9999/api']);

    // Clear the resolved instances from Container to force Laravel to read the new overloaded config!
    app()->forgetInstance(JOOservices\LaravelEmbedding\Contracts\EmbeddingManager::class);
    app()->forgetInstance(JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider::class);
    app()->forgetInstance(JOOservices\LaravelEmbedding\Contracts\Chunker::class);
    Embedding::clearResolvedInstances();

    Embedding::embedText('This should fail.');
    echo "❌ Failed: Expected an exception but none was thrown.\n\n";
} catch (Exception $e) {
    echo "✅ Caught expected exception (Bad URL):\n";
    echo '   - Exception: '.get_class($e)."\n";
    echo '   - Msg: '.$e->getMessage()."\n\n";
}
