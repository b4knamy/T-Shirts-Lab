import { useRef, useState } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { ProductImage } from '../../../../types';
import type { UseProductImageManagerOptions } from '../types';

export function useProductImageManager({
  productId,
  initialImages,
  onRefresh,
}: UseProductImageManagerOptions) {
  const [images, setImages] = useState<ProductImage[]>(initialImages);
  const [urlInput, setUrlInput] = useState('');
  const [altInput, setAltInput] = useState('');
  const [isAdding, setIsAdding] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [busy, setBusy] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  const reload = async () => {
    try {
      const res = await adminApi.getProductImages(productId);
      setImages(res.data.data || []);
      onRefresh();
    } catch {
      /* silent */
    }
  };

  const handleAddUrl = async () => {
    if (!urlInput.trim()) return;
    setIsAdding(true);
    setError(null);
    try {
      await adminApi.addProductImage(productId, {
        image_url: urlInput.trim(),
        alt_text: altInput.trim() || undefined,
      });
      setUrlInput('');
      setAltInput('');
      await reload();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e.response?.data?.message || 'Failed to add image');
    } finally {
      setIsAdding(false);
    }
  };

  const handleUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setIsUploading(true);
    setError(null);
    try {
      const fd = new FormData();
      fd.append('image', file);
      await adminApi.uploadProductImage(productId, fd);
      await reload();
    } catch (err: unknown) {
      const ex = err as { response?: { data?: { message?: string } } };
      setError(ex.response?.data?.message || 'Upload failed');
    } finally {
      setIsUploading(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  };

  const handleSetPrimary = async (imageId: string) => {
    setBusy(imageId);
    setError(null);
    try {
      await adminApi.updateProductImage(productId, imageId, {
        is_primary: true,
      });
      await reload();
    } catch {
      setError('Failed to set primary');
    } finally {
      setBusy(null);
    }
  };

  const handleDeleteImage = async (imageId: string) => {
    setBusy(imageId);
    setError(null);
    try {
      await adminApi.deleteProductImage(productId, imageId);
      await reload();
    } catch {
      setError('Failed to delete image');
    } finally {
      setBusy(null);
    }
  };

  return {
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
  };
}
