<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    // max downloads for guests per month
    private $guestDownloadLimit = 5;

    public function download(Request $request, Product $product)
    {
        $user = Auth::user();
        $ip = $request->ip();
        $deviceId = $request->header('Device-Id');

        // Ensure guest has a persistent ID
        $guestId = $request->cookie('guest_id');
        if (!$guestId) {
            $guestId = (string) Str::uuid();
            cookie()->queue('guest_id', $guestId, 60 * 24 * 30); // 30 days
        }

        // Free assets → unlimited
        if ($product->type === 'free') {
            return $this->serveFile($product, $user, $ip, $deviceId, $guestId);
        }

        // Pro assets
        if (!$user) {
            // Guest download limit + caching
            $cacheKey = "downloads_count:product_{$product->id}:guest_{$guestId}:" . now()->format('Ym');
            $downloadsCount = Cache::get($cacheKey, 0);

            if ($downloadsCount >= $this->guestDownloadLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guest download limit reached. Please register to continue downloading Pro assets.'
                ], 403);
            }

            // Rate limiting per guest
            $limitKey = 'guest-download:' . $guestId;
            if (RateLimiter::tooManyAttempts($limitKey, 10)) { // 10 downloads/min
                return response()->json([
                    'success' => false,
                    'message' => 'Too many download attempts. Try again later.'
                ], 429);
            }
            RateLimiter::hit($limitKey, 60);

            // Increment cache for monthly downloads
            Cache::increment($cacheKey);
            Cache::put($cacheKey, Cache::get($cacheKey), now()->endOfMonth());

            return $this->serveFile($product, null, $ip, $deviceId, $guestId);
        }

        // Logged-in user → atomic credit deduction
        $success = $user->where('id', $user->id)
            ->where('credit', '>=', $product->credit_cost)
            ->decrement('credit', $product->credit_cost);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough credits to download this asset.'
            ], 403);
        }

        return $this->serveFile($product, $user, $ip, $deviceId, $guestId);
    }

    private function serveFile(Product $product, $user = null, $ip = null, $deviceId = null, $guestId = null)
    {
        $filePath = $product->file_path ?? null;

        // Security check: prevent path traversal
        if (!$filePath || !str_starts_with($filePath, 'products/') || str_contains($filePath, '..')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file path.'
            ], 400);
        }

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        }

        // Record download in DB
        Download::create([
            'user_id' => $user?->id,
            'product_id' => $product->id,
            'ip_address' => $ip,
            'guest_id' => $guestId,
            'device_id' => $deviceId,
        ]);

        // Serve the file
        return Storage::disk('public')->download($filePath);
    }
}
