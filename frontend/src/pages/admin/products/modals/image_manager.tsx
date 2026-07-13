import {
  AlertTriangle,
  Crown,
  Link as LinkIcon,
  Loader2,
  Star,
  Trash2,
  Upload,
} from 'lucide-react';
import { useProductImageManager } from '../hooks/image_manager_hook';
import type { ProductImageManagerProps } from '../types';

export function ProductImageManager({
  productId,
  images: initialImages,
  onRefresh,
}: ProductImageManagerProps) {
  const {
    images,
    urlInput,
    altInput,
    isAdding,
    isUploading,
    busy,
    error,
    fileRef,
    setUrlInput,
    setAltInput,
    handleAddUrl,
    handleUpload,
    handleSetPrimary,
    handleDeleteImage,
  } = useProductImageManager({ productId, initialImages, onRefresh });

  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-2">
        Images
      </label>

      {error && (
        <div className="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-xl text-xs mb-3">
          <AlertTriangle className="w-3.5 h-3.5 mt-0.5 flex-shrink-0" />
          {error}
        </div>
      )}

      {images.length > 0 && (
        <div className="flex gap-2 flex-wrap mb-3">
          {images.map((img) => (
            <div key={img.id} className="relative group w-20 h-20">
              <img
                src={img.image_url}
                alt={img.alt_text || ''}
                className="w-full h-full rounded-xl object-cover border border-gray-200"
              />
              {img.is_primary && (
                <span className="absolute -top-1 -right-1 w-5 h-5 bg-accent text-white rounded-full flex items-center justify-center z-10">
                  <Crown className="w-3 h-3" />
                </span>
              )}
              <div className="absolute inset-0 bg-black/50 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                {busy === img.id ? (
                  <Loader2 className="w-5 h-5 text-white animate-spin" />
                ) : (
                  <>
                    {!img.is_primary && (
                      <button
                        onClick={() => handleSetPrimary(img.id)}
                        className="p-1.5 bg-white/20 rounded-lg hover:bg-white/40 transition-colors"
                        title="Set as primary"
                      >
                        <Star className="w-3.5 h-3.5 text-white" />
                      </button>
                    )}
                    <button
                      onClick={() => handleDeleteImage(img.id)}
                      className="p-1.5 bg-white/20 rounded-lg hover:bg-red-500/80 transition-colors"
                      title="Delete"
                    >
                      <Trash2 className="w-3.5 h-3.5 text-white" />
                    </button>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="flex gap-2 items-end mb-2">
        <div className="flex-1">
          <input
            value={urlInput}
            onChange={(event) => setUrlInput(event.target.value)}
            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-accent"
            placeholder="Image URL…"
          />
        </div>
        <div className="w-28">
          <input
            value={altInput}
            onChange={(event) => setAltInput(event.target.value)}
            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-accent"
            placeholder="Alt text"
          />
        </div>
        <button
          onClick={handleAddUrl}
          disabled={isAdding || !urlInput.trim()}
          className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-40"
          title="Add by URL"
        >
          {isAdding ? (
            <Loader2 className="w-3.5 h-3.5 animate-spin" />
          ) : (
            <LinkIcon className="w-3.5 h-3.5" />
          )}{' '}
          Add
        </button>
      </div>

      <input
        ref={fileRef}
        type="file"
        accept="image/jpeg,image/png,image/webp"
        onChange={handleUpload}
        className="hidden"
      />
      <button
        onClick={() => fileRef.current?.click()}
        disabled={isUploading}
        className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium border border-dashed border-gray-300 text-gray-500 hover:border-accent hover:text-accent rounded-lg transition-colors disabled:opacity-40"
      >
        {isUploading ? (
          <Loader2 className="w-3.5 h-3.5 animate-spin" />
        ) : (
          <Upload className="w-3.5 h-3.5" />
        )}{' '}
        Upload File
      </button>
    </div>
  );
}
