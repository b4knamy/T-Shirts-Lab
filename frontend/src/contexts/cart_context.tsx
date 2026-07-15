import {
  createContext,
  useState,
  useEffect,
  useCallback,
  type PropsWithChildren,
} from 'react';
import type { CartItem, Product } from '../types';

interface CartContextType {
  items: CartItem[];
  isOpen: boolean;
  itemCount: number;
  total: number;
  add: (product: Product, quantity?: number) => void;
  remove: (productId: string) => void;
  update: (productId: string, quantity: number) => void;
  clear: () => void;
  toggle: () => void;
  setOpen: (open: boolean) => void;
}

export const CartContext = createContext<CartContextType | undefined>(
  undefined,
);

const loadCartFromStorage = (): CartItem[] => {
  try {
    const stored = localStorage.getItem('cart');
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
};

const saveCartToStorage = (items: CartItem[]) => {
  localStorage.setItem('cart', JSON.stringify(items));
};

interface CartProviderProps extends PropsWithChildren {
  initialItems?: CartItem[];
  initialIsOpen?: boolean;
}

export function CartProvider({
  children,
  initialItems,
  initialIsOpen = false,
}: CartProviderProps) {
  const [items, setItems] = useState<CartItem[]>(() => {
    if (initialItems !== undefined) return initialItems;
    return loadCartFromStorage();
  });
  const [isOpen, setIsOpenState] = useState<boolean>(initialIsOpen);

  // Keep localStorage sync'd
  useEffect(() => {
    if (initialItems === undefined) {
      saveCartToStorage(items);
    }
  }, [items, initialItems]);

  const add = useCallback((product: Product, quantity = 1) => {
    setItems((prevItems) => {
      const existingItem = prevItems.find(
        (item) => item.product.id === product.id,
      );
      if (existingItem) {
        return prevItems.map((item) =>
          item.product.id === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item,
        );
      }
      return [...prevItems, { product, quantity }];
    });
  }, []);

  const remove = useCallback((productId: string) => {
    setItems((prevItems) =>
      prevItems.filter((item) => item.product.id !== productId),
    );
  }, []);

  const update = useCallback((productId: string, quantity: number) => {
    setItems((prevItems) =>
      prevItems.map((item) =>
        item.product.id === productId
          ? { ...item, quantity: Math.max(1, quantity) }
          : item,
      ),
    );
  }, []);

  const clear = useCallback(() => {
    setItems([]);
  }, []);

  const toggle = useCallback(() => {
    setIsOpenState((prev) => !prev);
  }, []);

  const setOpen = useCallback((open: boolean) => {
    setIsOpenState(open);
  }, []);

  const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
  const total = items.reduce(
    (sum, item) => sum + Number(item.product.price) * item.quantity,
    0,
  );

  return (
    <CartContext.Provider
      value={{
        items,
        isOpen,
        itemCount,
        total,
        add,
        remove,
        update,
        clear,
        toggle,
        setOpen,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}
export { loadCartFromStorage, saveCartToStorage };
