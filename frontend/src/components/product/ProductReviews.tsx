import { useEffect, useState, useCallback } from 'react';
import { MessageSquare, Send, Edit3, X, ChevronLeft, ChevronRight, Shield } from 'lucide-react';
import { reviewsApi } from '../../services/api/reviews';
import { StarRating } from '../common/StarRating';
import { useAuth } from '../../hooks/useAuth';
import type { ProductReview, ReviewsResponse } from '../../types';

interface ProductReviewsProps {
  productId: string;
}

export function ProductReviews({ productId }: ProductReviewsProps) {
  const { user, isAuthenticated } = useAuth();
  const [data, setData] = useState<ReviewsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const fetchReviews = useCallback(async (p: number) => {
    setLoading(true);
    try {
      const res = await reviewsApi.getProductReviews(productId, p);
      setData(res.data.data);
      setError(null);
    } catch {
      setError('Failed to load reviews');
    } finally {
      setLoading(false);
    }
  }, [productId]);

  useEffect(() => {
    fetchReviews(page);
  }, [fetchReviews, page]);

  const handleReviewSubmitted = () => {
    fetchReviews(1);
    setPage(1);
  };

  const isAdmin = user?.role === 'ADMIN' || user?.role === 'SUPER_ADMIN';
  const userReview = data?.reviews.find((r) => r.user_id === user?.id);

  return (
    <div className="mt-16 border-t pt-12">
      <div className="flex items-center justify-between mb-8">
        <h2 className="text-2xl font-bold">Customer Reviews</h2>
      </div>

      {/* Summary */}
      {data && data.total_reviews > 0 && (
        <div className="flex items-center gap-6 mb-8 p-6 bg-surface rounded-2xl">
          <div className="text-center">
            <p className="text-4xl font-bold">{data.average_rating}</p>
            <StarRating rating={Math.round(data.average_rating)} size="md" />
            <p className="text-sm text-gray-500 mt-1">{data.total_reviews} review{data.total_reviews !== 1 ? 's' : ''}</p>
          </div>
          <div className="flex-1">
            {[5, 4, 3, 2, 1].map((star) => {
              const count = data.reviews.filter((r) => r.rating === star).length;
              const pct = data.total_reviews > 0 ? (count / data.total_reviews) * 100 : 0;
              return (
                <div key={star} className="flex items-center gap-2 text-sm">
                  <span className="w-8 text-right text-gray-500">{star}★</span>
                  <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div className="h-full bg-yellow-400 rounded-full" style={{ width: `${pct}%` }} />
                  </div>
                  <span className="w-8 text-gray-400">{count}</span>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Write / Edit Review Form */}
      {isAuthenticated && !isAdmin && (
        <ReviewForm
          productId={productId}
          existingReview={userReview || null}
          onSubmitted={handleReviewSubmitted}
        />
      )}

      {!isAuthenticated && (
        <p className="text-sm text-gray-500 mb-8">
          <a href="/login" className="text-accent hover:underline">Sign in</a> to leave a review.
        </p>
      )}

      {/* Review List */}
      {loading && !data ? (
        <div className="text-center py-10 text-gray-400">Loading reviews...</div>
      ) : error ? (
        <div className="text-center py-10 text-red-500">{error}</div>
      ) : data && data.reviews.length === 0 ? (
        <div className="text-center py-16 text-gray-400">
          <MessageSquare className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p>No reviews yet. Be the first to review this product!</p>
        </div>
      ) : (
        <>
          <div className="space-y-6">
            {data?.reviews.map((review) => (
              <ReviewCard
                key={review.id}
                review={review}
                isAdmin={isAdmin}
                onReplied={handleReviewSubmitted}
              />
            ))}
          </div>

          {/* Pagination */}
          {data && data.pagination.last_page > 1 && (
            <div className="flex items-center justify-center gap-4 mt-8">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <ChevronLeft className="w-4 h-4" /> Previous
              </button>
              <span className="text-sm text-gray-500">
                Page {data.pagination.current_page} of {data.pagination.last_page}
              </span>
              <button
                onClick={() => setPage((p) => Math.min(data.pagination.last_page, p + 1))}
                disabled={page === data.pagination.last_page}
                className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Next <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
}

/* ── Review Form ───────────────────────────────────────────────── */

function ReviewForm({
  productId,
  existingReview,
  onSubmitted,
}: {
  productId: string;
  existingReview: ProductReview | null;
  onSubmitted: () => void;
}) {
  const isEditing = !!existingReview;
  const [open, setOpen] = useState(false);
  const [rating, setRating] = useState(existingReview?.rating || 0);
  const [comment, setComment] = useState(existingReview?.comment || '');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async () => {
    if (rating === 0) {
      setError('Please select a rating');
      return;
    }
    setSaving(true);
    setError(null);
    try {
      if (isEditing && existingReview) {
        await reviewsApi.updateReview(existingReview.id, { rating, comment: comment || undefined });
      } else {
        await reviewsApi.createReview(productId, { rating, comment: comment || undefined });
      }
      setOpen(false);
      onSubmitted();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e.response?.data?.message || 'Failed to submit review');
    } finally {
      setSaving(false);
    }
  };

  if (!open) {
    return (
      <div className="mb-8">
        <button
          onClick={() => setOpen(true)}
          className="flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-xl font-medium hover:bg-accent/90 transition-colors shadow-sm"
        >
          {isEditing ? <Edit3 className="w-4 h-4" /> : <MessageSquare className="w-4 h-4" />}
          {isEditing ? 'Edit Your Review' : 'Write a Review'}
        </button>
      </div>
    );
  }

  return (
    <div className="mb-8 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
      <h3 className="font-semibold mb-4">{isEditing ? 'Edit Your Review' : 'Write a Review'}</h3>

      {error && <p className="text-sm text-red-500 mb-3">{error}</p>}

      <div className="mb-4">
        <label className="block text-sm text-gray-500 mb-2">Your Rating *</label>
        <StarRating rating={rating} size="lg" interactive onChange={setRating} />
      </div>

      <div className="mb-4">
        <label className="block text-sm text-gray-500 mb-2">Your Review (optional)</label>
        <textarea
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          rows={4}
          maxLength={2000}
          placeholder="Share your experience with this product..."
          className="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none resize-none text-sm"
        />
        <p className="text-xs text-gray-400 mt-1">{comment.length}/2000</p>
      </div>

      <div className="flex items-center gap-3">
        <button
          onClick={handleSubmit}
          disabled={saving || rating === 0}
          className="flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-xl font-medium hover:bg-accent/90 disabled:opacity-50 transition-colors"
        >
          <Send className="w-4 h-4" /> {saving ? 'Submitting...' : isEditing ? 'Update Review' : 'Submit Review'}
        </button>
        <button
          onClick={() => setOpen(false)}
          className="flex items-center gap-2 text-gray-500 hover:text-gray-700 px-4 py-2.5"
        >
          <X className="w-4 h-4" /> Cancel
        </button>
      </div>
    </div>
  );
}

/* ── Review Card ───────────────────────────────────────────────── */

function ReviewCard({
  review,
  isAdmin,
  onReplied,
}: {
  review: ProductReview;
  isAdmin: boolean;
  onReplied: () => void;
}) {
  const [replying, setReplying] = useState(false);
  const [replyText, setReplyText] = useState(review.admin_reply || '');
  const [saving, setSaving] = useState(false);

  const handleReply = async () => {
    if (!replyText.trim()) return;
    setSaving(true);
    try {
      await reviewsApi.adminReply(review.id, replyText);
      setReplying(false);
      onReplied();
    } catch {
      // silent
    } finally {
      setSaving(false);
    }
  };

  const initials = `${review.user.first_name?.[0] || ''}${review.user.last_name?.[0] || ''}`.toUpperCase();

  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
      <div className="flex items-start gap-4">
        {/* Avatar */}
        <div className="flex-shrink-0">
          {review.user.profile_picture_url ? (
            <img
              src={review.user.profile_picture_url}
              alt={review.user.first_name}
              className="w-10 h-10 rounded-full object-cover"
            />
          ) : (
            <div className="w-10 h-10 rounded-full bg-accent/10 text-accent flex items-center justify-center font-semibold text-sm">
              {initials}
            </div>
          )}
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-sm">
                {review.user.first_name} {review.user.last_name}
              </p>
              <div className="flex items-center gap-2 mt-0.5">
                <StarRating rating={review.rating} size="sm" />
                <span className="text-xs text-gray-400">
                  {new Date(review.created_at).toLocaleDateString()}
                </span>
              </div>
            </div>
          </div>

          {review.comment && (
            <p className="text-sm text-gray-600 mt-3 leading-relaxed">{review.comment}</p>
          )}

          {/* Admin Reply */}
          {review.admin_reply && (
            <div className="mt-4 ml-4 pl-4 border-l-2 border-accent/30 bg-accent/5 rounded-r-xl p-4">
              <div className="flex items-center gap-2 mb-1">
                <Shield className="w-4 h-4 text-accent" />
                <span className="text-xs font-semibold text-accent">Store Owner Reply</span>
                {review.admin_replied_at && (
                  <span className="text-xs text-gray-400">
                    · {new Date(review.admin_replied_at).toLocaleDateString()}
                  </span>
                )}
              </div>
              <p className="text-sm text-gray-600 leading-relaxed">{review.admin_reply}</p>
            </div>
          )}

          {/* Admin Reply Button */}
          {isAdmin && !replying && (
            <button
              onClick={() => setReplying(true)}
              className="mt-3 text-xs text-accent hover:underline flex items-center gap-1"
            >
              <MessageSquare className="w-3 h-3" />
              {review.admin_reply ? 'Edit Reply' : 'Reply'}
            </button>
          )}

          {/* Admin Reply Form */}
          {isAdmin && replying && (
            <div className="mt-4 space-y-3">
              <textarea
                value={replyText}
                onChange={(e) => setReplyText(e.target.value)}
                rows={3}
                maxLength={2000}
                placeholder="Write your reply..."
                className="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none resize-none text-sm"
              />
              <div className="flex items-center gap-2">
                <button
                  onClick={handleReply}
                  disabled={saving || !replyText.trim()}
                  className="flex items-center gap-1 bg-accent text-white text-xs px-3 py-1.5 rounded-lg hover:bg-accent/90 disabled:opacity-50"
                >
                  <Send className="w-3 h-3" /> {saving ? 'Sending...' : 'Send Reply'}
                </button>
                <button
                  onClick={() => setReplying(false)}
                  className="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5"
                >
                  Cancel
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
