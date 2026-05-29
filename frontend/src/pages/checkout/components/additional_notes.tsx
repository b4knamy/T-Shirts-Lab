import type { AdditionalNotesSectionProps } from '../types';

export function AdditionalNotesSection({ register }: AdditionalNotesSectionProps) {
  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
      <h2 className="font-semibold text-lg mb-4">Additional Notes</h2>
      <textarea
        {...register('customerNotes')}
        rows={3}
        className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent resize-none"
        placeholder="Any special instructions for your order..."
      />
    </div>
  );
}