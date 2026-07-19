import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { StarRating } from '../StarRating';

describe('StarRating', () => {
  it('renders exactly 5 star buttons', () => {
    render(<StarRating rating={3} />);
    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(5);
  });

  it('renders correct styles for rated stars', () => {
    const { container } = render(<StarRating rating={3} />);
    const stars = container.querySelectorAll('svg');
    expect(stars).toHaveLength(5);
    
    expect(stars[0]).toHaveClass('fill-yellow-400');
    expect(stars[1]).toHaveClass('fill-yellow-400');
    expect(stars[2]).toHaveClass('fill-yellow-400');
    expect(stars[3]).toHaveClass('fill-none');
    expect(stars[4]).toHaveClass('fill-none');
  });

  it('disables buttons when not interactive', () => {
    render(<StarRating rating={3} interactive={false} />);
    const buttons = screen.getAllByRole('button');
    buttons.forEach((btn) => {
      expect(btn).toBeDisabled();
    });
  });

  it('enables buttons and triggers onChange when interactive', async () => {
    const onChangeMock = vi.fn();
    render(<StarRating rating={3} interactive={true} onChange={onChangeMock} />);
    
    const buttons = screen.getAllByRole('button');
    buttons.forEach((btn) => {
      expect(btn).not.toBeDisabled();
    });

    await userEvent.click(buttons[3]);
    expect(onChangeMock).toHaveBeenCalledWith(4);
  });
});
