import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeAll, afterAll } from 'vitest';
import { ErrorBoundary } from '../ErrorBoundary';

function ThrowError() {
  throw new Error('Test boundary error');
}

describe('ErrorBoundary', () => {
  const consoleError = console.error;
  beforeAll(() => {
    console.error = vi.fn();
  });
  afterAll(() => {
    console.error = consoleError;
  });

  it('renders children normally when there is no error', () => {
    render(
      <ErrorBoundary>
        <div>Normal Content</div>
      </ErrorBoundary>
    );

    expect(screen.getByText('Normal Content')).toBeInTheDocument();
  });

  it('renders default error fallback when error is thrown', () => {
    render(
      <ErrorBoundary>
        <ThrowError />
      </ErrorBoundary>
    );

    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    expect(screen.getByText('Test boundary error')).toBeInTheDocument();
  });

  it('renders custom fallback if provided when error is thrown', () => {
    render(
      <ErrorBoundary fallback={<div>Custom Fallback</div>}>
        <ThrowError />
      </ErrorBoundary>
    );

    expect(screen.queryByText('Something went wrong')).not.toBeInTheDocument();
    expect(screen.getByText('Custom Fallback')).toBeInTheDocument();
  });

  it('triggers window.location.reload when reload button clicked', async () => {
    const reloadMock = vi.fn();
    Object.defineProperty(window, 'location', {
      writable: true,
      value: { reload: reloadMock },
    });

    render(
      <ErrorBoundary>
        <ThrowError />
      </ErrorBoundary>
    );

    const button = screen.getByRole('button', { name: 'Reload Page' });
    await userEvent.click(button);
    expect(reloadMock).toHaveBeenCalled();
  });
});
