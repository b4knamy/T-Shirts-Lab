import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { MessageSquare, Send, Trash2, Star, Filter, ChevronLeft, ChevronRight, Shield } from 'lucide-react';
import { StarRating } from '../../components/common/StarRating';
import { reviewsApi } from '../../services/api/reviews';
import apiClient from '../../services/api/client';
import type { ProductReview } from '../../types';

interface AdminReview extends ProductReview {
  product?: {
    id: string;
    name: string;
    slug: string;
  };
}

interface AdminReviewsResponse {
  data: AdminReview[];
  meta: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}

export function AdminReviews() {
  const [reviews, setReviews] = useState<AdminReview[]>([]);
  const [meta, setMeta] = useState({ total: 0, page: 1, limit: 15, total_pages: 1 });
  const [loading, setLoading] = useState(true);
  const [filterUnreplied, setFilterUnreplied] = useState(false);
  const [page, setPage] = useState(1);

  const fetchReviews = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<{ data: AdminReviewsResponse }>('/api/v1/reviews', {
        params: {
          page,
          limit: 15,
          ...(filterUnreplied ? { unreplied: '1' } : {}),
        },
      });
      setReviews(res.data.data.data);
      setMeta(res.data.data.meta);
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, [page, filterUnreplied]);

  useEffect(() => {
    fetchReviews();
  }, [fetchReviews]);

  const handleDelete = async (id: string) => {
    if (!confirm('Delete this review permanently?')) return;
    try {
      await reviewsApi.deleteReview(id);
      fetchReviews();
    } catch {
      // silent
    }
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold">Reviews</h1>
          <p className="text-sm text-gray-500 mt-1">{meta.total} total review{meta.total !== 1 ? 's' : ''}</p>
        </div>
        <button
          onClick={() => { setFilterUnreplied((f) => !f); setPage(1); }}
          className={`flex items-center gap-2 px-4 py-2 text-sm rounded-lg border transition-colors ${
            filterUnreplied
              ? 'bg-accent text-white border-accent'
              : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
          }`}
        >
          <Filter className="w-4 h-4" />
          {filterUnreplied ? 'Showing Unreplied' : 'Filter Unreplied'}
        </button>
      </div>

      {loading ? (
        <div className="text-center py-16 text-gray-400">Loading reviews...</div>
      ) : reviews.length === 0 ? (
        <div className="text-center py-16 text-gray-400">
          <MessageSquare className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p>{filterUnreplied ? 'All reviews have been replied to!' : 'No reviews yet.'}</p>
        </div>
      ) : (
        <>
          <div className="space-y-4">
            {reviews.map((review) => (
              <AdminReviewCard
                key={review.id}
                review={review}
                onReplied={fetchReviews}
                onDelete={handleDelete}
              />
            ))}
          </div>

          {/* Pagination */}
          {meta.total_pages > 1 && (
            <div className="flex items-center justify-center gap-4 mt-8">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40"
              >
                <ChevronLeft className="w-4 h-4" /> Previous
              </button>
              <span className="text-sm text-gray-500">Page {meta.page} of {meta.total_pages}</span>
              <button
                onClick={() => setPage((p) => Math.min(meta.total_pages, p + 1))}
                disabled={page === meta.total_pages}
                className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40"
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

/* ── Admin Review Card ─────────────────────────────────────────── */

function AdminReviewCard({
  review,
  onReplied,
  onDelete,
}: {
  review: AdminReview;
  onReplied: () => void;
  onDelete: (id: string) => void;
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
    <div className="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
      <div className="flex items-start gap-4">
        {/* Avatar */}
        {review.user.profile_picture_url ? (
          <img src={review.user.profile_picture_url} alt="" className="w-10 h-10 rounded-full object-cover flex-shrink-0" />
        ) : (
          <div className="w-10 h-10 rounded-full bg-accent/10 text-accent flex items-center justify-center font-semibold text-sm flex-shrink-0">
            {initials}
          </div>
        )}

        <div className="flex-1 min-w-0">
          {/* Header */}
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="font-medium text-sm">
                {review.user.first_name} {review.user.last_name}
              </p>
              <div className="flex items-center gap-3 mt-0.5 flex-wrap">
                <StarRating rating={review.rating} size="sm" />
                <span className="text-xs text-gray-400">{new Date(review.created_at).toLocaleDateString()}</span>
                {review.product && (
                  <Link
                    to={`/products/${review.product.slug}`}
                    className="text-xs text-accent hover:underline"
                  >
                    {review.product.name}
                  </Link>
                )}
              </div>
            </div>

            <div className="flex items-center gap-2 flex-shrink-0">
              {review.admin_reply ? (
                <span className="flex items-center gap-1 text-xs bg-green-50 text-green-600 px-2 py-1 rounded-full">
                  <Shield className="w-3 h-3" /> Replied
                </span>
              ) : (
                <span className="flex items-center gap-1 text-xs bg-yellow-50 text-yellow-600 px-2 py-1 rounded-full">
                  <Star className="w-3 h-3" /> Pending
                </span>
              )}
              <button
                onClick={() => onDelete(review.id)}
                className="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                title="Delete review"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Comment */}
          {review.comment && (
            <p className="text-sm text-gray-600 mt-2 leading-relaxed">{review.comment}</p>
          )}

          {/* Existing Reply */}
          {review.admin_reply && !replying && (
            <div className="mt-3 ml-4 pl-4 border-l-2 border-accent/30 bg-accent/5 rounded-r-lg p-3">
              <p className="text-xs font-semibold text-accent mb-1">Your Reply</p>
              <p className="text-sm text-gray-600">{review.admin_reply}</p>
            </div>
          )}

          {/* Reply Action */}
          {!replying && (
            <button
              onClick={() => setReplying(true)}
              className="mt-3 text-xs text-accent hover:underline flex items-center gap-1"
            >
              <MessageSquare className="w-3 h-3" />
              {review.admin_reply ? 'Edit Reply' : 'Reply'}
            </button>
          )}

          {/* Reply Form */}
          {replying && (
            <div className="mt-3 space-y-2">
              <textarea
                value={replyText}
                onChange={(e) => setReplyText(e.target.value)}
                rows={3}
                maxLength={2000}
                placeholder="Write your reply to this review..."
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none resize-none text-sm"
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
                  onClick={() => { setReplying(false); setReplyText(review.admin_reply || ''); }}
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
