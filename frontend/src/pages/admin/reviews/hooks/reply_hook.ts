import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { reviewsApi } from '../../../../services/api/reviews';
import type { AdminReview } from '../types';

export function useReviewReply(review: AdminReview) {
  const queryClient = useQueryClient();

  const [replying, setReplying] = useState(false);
  const [replyText, setReplyText] = useState(review.admin_reply || '');
  const [saving, setSaving] = useState(false);

  const startReply = () => setReplying(true);

  const cancelReply = () => {
    setReplying(false);
    setReplyText(review.admin_reply || '');
  };

  const handleReply = async () => {
    if (!replyText.trim()) return;
    setSaving(true);
    try {
      await reviewsApi.adminReply(review.id, replyText);
      setReplying(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-reviews'] });
    } catch {
      // silent
    } finally {
      setSaving(false);
    }
  };

  return {
    replying,
    replyText,
    saving,
    setReplyText,
    startReply,
    cancelReply,
    handleReply,
  };
}
