import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ArrowLeft, Eye, EyeOff, Loader2 } from 'lucide-react';
import { authApi } from '../services/api/auth';

const resetPasswordSchema = z
  .object({
    email: z.string().email('Enter a valid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    confirmPassword: z.string(),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'],
  });

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

function getApiMessage(error: unknown, fallback: string) {
  const maybeError = error as { response?: { data?: { message?: string } } };
  return maybeError.response?.data?.message || fallback;
}

export function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmation, setShowConfirmation] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  const token = searchParams.get('token') || '';
  const emailFromUrl = searchParams.get('email') || '';

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: emailFromUrl,
      password: '',
      confirmPassword: '',
    },
  });

  useEffect(() => {
    reset({
      email: emailFromUrl,
      password: '',
      confirmPassword: '',
    });
  }, [emailFromUrl, reset]);

  const onSubmit = async (data: ResetPasswordFormData) => {
    if (!token) {
      setServerError(
        'This reset link is missing its token. Request a new one.',
      );
      return;
    }

    setIsSubmitting(true);
    setServerError(null);

    try {
      const response = await authApi.resetPassword({
        email: data.email,
        token,
        password: data.password,
        password_confirmation: data.confirmPassword,
      });

      navigate('/login', {
        replace: true,
        state: {
          message:
            response.data.message ||
            'Password reset successfully. Please sign in.',
        },
      });
    } catch (error) {
      setServerError(getApiMessage(error, 'Failed to reset password.'));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold text-center mb-2">
        Choose a New Password
      </h1>
      <p className="text-gray-500 text-center mb-8">
        Set a new password for your account and then sign in again.
      </p>

      {!token && (
        <div className="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg mb-4 text-sm">
          This reset link is incomplete. Request a fresh email before
          continuing.
        </div>
      )}

      {serverError && (
        <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-4 text-sm">
          {serverError}
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div>
          <label htmlFor="email" className="block text-sm font-medium mb-1">
            Email
          </label>
          <input
            id="email"
            type="email"
            {...register('email')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
            placeholder="you@example.com"
          />
          {errors.email && (
            <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="password" className="block text-sm font-medium mb-1">
            New Password
          </label>
          <div className="relative">
            <input
              id="password"
              type={showPassword ? 'text' : 'password'}
              {...register('password')}
              className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent pr-12"
              placeholder="At least 8 characters"
            />
            <button
              type="button"
              onClick={() => setShowPassword((value) => !value)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              {showPassword ? (
                <EyeOff className="w-5 h-5" />
              ) : (
                <Eye className="w-5 h-5" />
              )}
            </button>
          </div>
          {errors.password && (
            <p className="text-red-500 text-xs mt-1">
              {errors.password.message}
            </p>
          )}
        </div>

        <div>
          <label
            htmlFor="confirmPassword"
            className="block text-sm font-medium mb-1"
          >
            Confirm New Password
          </label>
          <div className="relative">
            <input
              id="confirmPassword"
              type={showConfirmation ? 'text' : 'password'}
              {...register('confirmPassword')}
              className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent pr-12"
              placeholder="Repeat your new password"
            />
            <button
              type="button"
              onClick={() => setShowConfirmation((value) => !value)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              {showConfirmation ? (
                <EyeOff className="w-5 h-5" />
              ) : (
                <Eye className="w-5 h-5" />
              )}
            </button>
          </div>
          {errors.confirmPassword && (
            <p className="text-red-500 text-xs mt-1">
              {errors.confirmPassword.message}
            </p>
          )}
        </div>

        <button
          type="submit"
          disabled={isSubmitting || !token}
          className="w-full bg-accent hover:bg-accent-light text-white py-2.5 rounded-lg font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
        >
          {isSubmitting ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" /> Resetting password...
            </>
          ) : (
            'Reset Password'
          )}
        </button>
      </form>

      <p className="text-center text-sm text-gray-500 mt-6">
        <Link
          to="/login"
          className="inline-flex items-center gap-2 text-accent hover:underline font-medium"
        >
          <ArrowLeft className="w-4 h-4" /> Back to sign in
        </Link>
      </p>
    </div>
  );
}
