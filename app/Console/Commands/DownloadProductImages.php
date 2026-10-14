<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images
                            {--limit= : Batasi jumlah produk yang diproses}
                            {--dry-run : Periksa URL tanpa mengunduh atau mengubah database}';

    protected $description =
        'Unduh gambar produk dari URL eksternal ke storage lokal';

    private const ALLOWED_HOSTS = [
        'images.unsplash.com',
    ];

    private const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    private array $totals = [
        'success' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    public function handle(): int
    {
        $limit = $this->option('limit');

        if ($limit !== null && (! is_numeric($limit) || (int) $limit < 1)) {
            $this->error('Opsi --limit harus berupa bilangan bulat positif.');

            return self::FAILURE;
        }

        $this->info('Memulai pemeriksaan gambar produk...');

        $query = Product::query()
            ->select(['id', 'name', 'image'])
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('Tidak ada produk dengan kolom image yang terisi.');

            return self::SUCCESS;
        }

        $this->info("Jumlah produk yang diperiksa: {$products->count()}");

        foreach ($products as $product) {
            $this->processProduct($product);
        }

        $this->newLine();
        $this->info('========== LAPORAN GAMBAR ==========');
        $this->line('Berhasil : ' . $this->totals['success']);
        $this->line('Dilewati : ' . $this->totals['skipped']);
        $this->line('Gagal    : ' . $this->totals['failed']);
        $this->line('Diperiksa: ' . array_sum($this->totals));
        $this->info('====================================');

        return $this->totals['failed'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function processProduct(Product $product): void
    {
        $originalImage = trim((string) $product->image);

        $this->newLine();
        $this->line("[Produk #{$product->id}] {$product->name}");

        // Gambar lokal yang sudah tersedia tidak perlu diunduh ulang.
        if (! Str::startsWith($originalImage, ['http://', 'https://'])) {
            if (Storage::disk('public')->exists($originalImage)) {
                $this->skip("Gambar lokal sudah tersedia: {$originalImage}");

                return;
            }

            $this->recordFailure("Path lokal tidak ditemukan: {$originalImage}");

            return;
        }

        $url = parse_url($originalImage);
        $host = strtolower($url['host'] ?? '');

        // Batasi sumber unduhan ke host gambar yang dikenal.
        if (
            ! in_array($host, self::ALLOWED_HOSTS, true)
            || ! in_array(strtolower($url['scheme'] ?? ''), ['http', 'https'], true)
        ) {
            $this->recordFailure('Host URL tidak diizinkan: ' . $host);

            return;
        }

        if ($this->option('dry-run')) {
            $this->skip('URL lolos pemeriksaan awal; dry-run tidak mengunduh file.');

            return;
        }

        $createdFile = false;
        $path = null;

        try {
            $response = Http::timeout(25)
                ->connectTimeout(10)
                ->withOptions([
                    'allow_redirects' => false,
                ])
                ->get($originalImage);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Server gambar memberikan HTTP ' . $response->status()
                );
            }

            $contents = $response->body();

            if ($contents === '' || strlen($contents) > self::MAX_IMAGE_BYTES) {
                throw new \RuntimeException(
                    'Gambar kosong atau melebihi batas 10 MB.'
                );
            }

            // Periksa format berdasarkan isi file, bukan ekstensi URL.
            $imageInfo = @getimagesizefromstring($contents);

            if (! is_array($imageInfo)) {
                throw new \RuntimeException('Respons bukan file gambar yang valid.');
            }

            $mime = $imageInfo['mime'] ?? '';

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (! isset($extensions[$mime])) {
                throw new \RuntimeException(
                    'Format gambar tidak didukung: ' . ($mime ?: 'tidak diketahui')
                );
            }

            $filename = sprintf(
                'imported-product-%d-%s.%s',
                $product->id,
                substr(hash('sha256', $originalImage), 0, 12),
                $extensions[$mime]
            );

            $path = 'products/' . $filename;

            if (! Storage::disk('public')->exists($path)) {
                $stored = Storage::disk('public')->put($path, $contents);

                if (! $stored) {
                    throw new \RuntimeException('Gagal menyimpan file ke disk public.');
                }

                $createdFile = true;
            }

            DB::transaction(function () use (
                $product,
                $originalImage,
                $path
            ): void {
                $lockedProduct = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($product->id);

                // Jangan menimpa perubahan gambar yang dibuat proses lain.
                if ($lockedProduct->image !== $originalImage) {
                    throw new \RuntimeException(
                        'URL gambar produk berubah selama proses berlangsung.'
                    );
                }

                $images = $lockedProduct->images();

                $imageRecord = $images
                    ->where('path', $path)
                    ->first();

                $primaryImage = $images
                    ->where('is_primary', true)
                    ->first();

                if (! $imageRecord) {
                    $imageRecord = $images->create([
                        'product_variant_id' => null,
                        'path' => $path,
                        'alt_text' => $lockedProduct->name,
                        'sort_order' => ((int) $images->max('sort_order')) + 1,
                        'is_primary' => false,
                    ]);
                }

                // Jika produk belum mempunyai gambar utama, gunakan
                // gambar hasil impor sebagai gambar utama.
                if (! $primaryImage) {
                    $images->update(['is_primary' => false]);

                    $imageRecord->update([
                        'is_primary' => true,
                    ]);

                    $primaryPath = $path;
                } else {
                    // Pertahankan gambar utama yang sudah dipilih.
                    $primaryPath = $primaryImage->path;
                }

                $lockedProduct->update([
                    'image' => $primaryPath,
                ]);
            });

            if ($createdFile) {
                $this->totals['success']++;
                $this->info("Berhasil diunduh: {$path}");
            } else {
                $this->totals['skipped']++;
                $this->comment("File sudah ada; referensi diperiksa: {$path}");
            }
        } catch (Throwable $exception) {
            // Hapus file baru hanya jika tidak ada referensi database.
            if ($createdFile && $path !== null) {
                $isReferencedByProducts = Product::query()
                    ->where('image', $path)
                    ->exists();

                $isReferencedByImages = ProductImage::query()
                    ->where('path', $path)
                    ->exists();

                if (! $isReferencedByProducts && ! $isReferencedByImages) {
                    Storage::disk('public')->delete($path);
                }
            }

            $this->recordFailure($exception->getMessage());
        }
    }

    private function skip(string $message): void
    {
        $this->totals['skipped']++;
        $this->comment('Dilewati: ' . $message);
    }

    private function recordFailure(string $message): void
    {
        $this->totals['failed']++;
        $this->error('Gagal: ' . $message);
    }
}
