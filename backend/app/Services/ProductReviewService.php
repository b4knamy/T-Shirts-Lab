<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductReviewService
{
    public function getForProduct(string $productId): array
    {
        $product = Product::findOrFail($productId);

        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(10);

        $stats = ProductReview::where('product_id', $productId)
            ->selectRaw('ROUND(AVG(rating), 1) as average_rating, COUNT(*) as total_reviews')
            ->first();

        return compact('reviews', 'stats');
    }

    /**
     * @return ProductReview|string  Returns the review on success or an error string.
     */
    public function create(string $productId, User $user, array $data): ProductReview|string
    {
        $product = Product::findOrFail($productId);

        $existing = ProductReview::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return 'You have already reviewed this product';
        }

        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return $review;
    }

    public function update(string $reviewId, User $user, array $data): ProductReview
    {
        $review = ProductReview::where('id', $reviewId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $review->update($data);
        $review->load('user');

        return $review->fresh();
    }

    public function addAdminReply(string $reviewId, string $reply): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);

        $review->update([
            'admin_reply' => $reply,
            'admin_replied_at' => now(),
        ]);

        $review->load('user');

        return $review->fresh();
    }

    public function adminList(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ProductReview::with(['user', 'product'])->latest();

        if (! empty($filters['unreplied'])) {
            $query->whereNull('admin_reply');
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function delete(string $reviewId): void
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->delete();
    }
}
