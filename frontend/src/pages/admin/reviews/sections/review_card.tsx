import { Link } from 'react-router-dom';
import { MessageSquare, Send, Shield, Star, Trash2 } from 'lucide-react';
import { StarRating } from '../../../../components/common/StarRating';
import { useReviewReply } from '../hooks/reply_hook';
import type { ReviewCardProps } from '../types';

export function ReviewCard({ review, onDelete }: ReviewCardProps) {
  const {
    replying,
    replyText,
    saving,
    setReplyText,
    startReply,
    cancelReply,
    handleReply,
  } = useReviewReply(review);

  const initials =
    `${review.user.first_name?.[0] || ''}${review.user.last_name?.[0] || ''}`.toUpperCase();

  return (
    <div className="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
      <div className="flex items-start gap-4">
        {review.user.profile_picture_url ? (
          <img
            src={review.user.profile_picture_url}
            alt=""
            className="w-10 h-10 rounded-full object-cover flex-shrink-0"
          />
        ) : (
          <div className="w-10 h-10 rounded-full bg-accent/10 text-accent flex items-center justify-center font-semibold text-sm flex-shrink-0">
            {initials}
          </div>
        )}

        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="font-medium text-sm">
                {review.user.first_name} {review.user.last_name}
              </p>
              <div className="flex items-center gap-3 mt-0.5 flex-wrap">
                <StarRating rating={review.rating} size="sm" />
                <span className="text-xs text-gray-400">
                  {new Date(review.created_at).toLocaleDateString()}
                </span>
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

          {review.comment && (
            <p className="text-sm text-gray-600 mt-2 leading-relaxed">
              {review.comment}
            </p>
          )}

          {review.admin_reply && !replying && (
            <div className="mt-3 ml-4 pl-4 border-l-2 border-accent/30 bg-accent/5 rounded-r-lg p-3">
              <p className="text-xs font-semibold text-accent mb-1">
                Your Reply
              </p>
              <p className="text-sm text-gray-600">{review.admin_reply}</p>
            </div>
          )}

          {!replying && (
            <button
              onClick={startReply}
              className="mt-3 text-xs text-accent hover:underline flex items-center gap-1"
            >
              <MessageSquare className="w-3 h-3" />
              {review.admin_reply ? 'Edit Reply' : 'Reply'}
            </button>
          )}

          {replying && (
            <div className="mt-3 space-y-2">
              <textarea
                value={replyText}
                onChange={(event) => setReplyText(event.target.value)}
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
                  <Send className="w-3 h-3" />{' '}
                  {saving ? 'Sending...' : 'Send Reply'}
                </button>
                <button
                  onClick={cancelReply}
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
