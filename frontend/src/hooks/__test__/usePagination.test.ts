import { renderHook, act } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { usePagination } from '../usePagination';

describe('usePagination', () => {
  it('should initialize with default states', () => {
    const { result } = renderHook(() => usePagination());
    expect(result.current.current_page).toBe(1);
    expect(result.current.per_page).toBe(1);
    expect(result.current.total).toBe(0);
    expect(result.current.last_page).toBe(1);
    expect(result.current.from).toBe(1);
    expect(result.current.to).toBe(1);
    expect(result.current.limit).toBe(10);
    expect(result.current.total_pages).toBe(1);
    expect(result.current.page).toBe(1);
  });

  it('should apply pagination and calculate from/to correctly', () => {
    const { result } = renderHook(() => usePagination());

    act(() => {
      result.current.applyPagination({
        total: 50,
        total_pages: 5,
        limit: 10,
        page: 2,
      });
    });

    expect(result.current.current_page).toBe(2);
    expect(result.current.last_page).toBe(5);
    expect(result.current.per_page).toBe(10);
    expect(result.current.total).toBe(50);
    expect(result.current.from).toBe(11);
    expect(result.current.to).toBe(20);
  });

  it('should navigate to next page and previous page', () => {
    const { result } = renderHook(() => usePagination());

    act(() => {
      result.current.applyPagination({
        total: 50,
        total_pages: 5,
        limit: 10,
        page: 2,
      });
    });

    act(() => {
      result.current.goToNextPage();
    });
    expect(result.current.current_page).toBe(3);

    act(() => {
      result.current.goToPreviousPage();
    });
    expect(result.current.current_page).toBe(2);
  });

  it('should not go below page 1', () => {
    const { result } = renderHook(() => usePagination());

    act(() => {
      result.current.applyPagination({
        total: 30,
        total_pages: 3,
        limit: 10,
        page: 1,
      });
    });

    act(() => {
      result.current.goToPreviousPage();
    });
    expect(result.current.current_page).toBe(1);
  });

  it('should change page using changePage', () => {
    const { result } = renderHook(() => usePagination());

    act(() => {
      result.current.applyPagination({
        total: 50,
        total_pages: 5,
        limit: 10,
        page: 1,
      });
    });

    act(() => {
      result.current.changePage(3);
    });
    expect(result.current.current_page).toBe(3);

    // If changePage is called with same page, should do nothing
    act(() => {
      result.current.changePage(3);
    });
    expect(result.current.current_page).toBe(3);
  });

  it('should reset pagination to initial state', () => {
    const { result } = renderHook(() => usePagination());

    act(() => {
      result.current.applyPagination({
        total: 50,
        total_pages: 5,
        limit: 10,
        page: 3,
      });
    });

    act(() => {
      result.current.resetPagination();
    });

    expect(result.current.current_page).toBe(1);
    expect(result.current.total).toBe(0);
    expect(result.current.from).toBe(1);
  });
});
