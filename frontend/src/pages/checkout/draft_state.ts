import {
  createContext,
  createElement,
  useContext,
  useMemo,
  useReducer,
} from 'react';
import type { PropsWithChildren } from 'react';
import type { CartItem } from '../../types';
import type { CheckoutDraftItem, CheckoutDraftState } from './types';

type CheckoutDraftAction =
  | { type: 'sync-with-cart'; cartItems: CartItem[] }
  | { type: 'initialize-from-cart'; cartItems: CartItem[] };

const CheckoutDraftInitialStateContext = createContext<
  CheckoutDraftState | undefined
>(undefined);

function buildDraftItemsFromCart(cartItems: CartItem[]): CheckoutDraftItem[] {
  return cartItems.map((item) => ({
    productId: item.product.id,
    quantity: item.quantity,
  }));
}

function syncDraftItemsWithCart(
  cartItems: CartItem[],
  draftItems: CheckoutDraftItem[],
): CheckoutDraftItem[] {
  const cartQuantities = new Map(
    cartItems.map((item) => [item.product.id, item.quantity]),
  );

  return draftItems.flatMap((item) => {
    const cartQuantity = cartQuantities.get(item.productId);

    if (!cartQuantity) {
      return [];
    }

    return [
      {
        productId: item.productId,
        quantity: Math.min(item.quantity, cartQuantity),
      },
    ];
  });
}

function getInitialDraftState(
  initialState?: CheckoutDraftState,
): CheckoutDraftState {
  return (
    initialState ?? {
      items: [],
      draftInitialized: false,
    }
  );
}

function checkoutDraftReducer(
  state: CheckoutDraftState,
  action: CheckoutDraftAction,
): CheckoutDraftState {
  switch (action.type) {
    case 'sync-with-cart':
      return {
        ...state,
        items: syncDraftItemsWithCart(action.cartItems, state.items),
      };

    case 'initialize-from-cart':
      if (state.draftInitialized || action.cartItems.length === 0) {
        return state;
      }

      return {
        draftInitialized: true,
        items: buildDraftItemsFromCart(action.cartItems),
      };

    default:
      return state;
  }
}

export function CheckoutDraftProvider({
  children,
  initialState,
}: PropsWithChildren<{ initialState?: Partial<CheckoutDraftState> }>) {
  const value = useMemo(() => {
    if (!initialState) {
      return undefined;
    }

    return {
      items: initialState.items ?? [],
      draftInitialized: initialState.draftInitialized ?? false,
    } satisfies CheckoutDraftState;
  }, [initialState]);

  return createElement(
    CheckoutDraftInitialStateContext.Provider,
    { value },
    children,
  );
}

export function useCheckoutDraftState(initialState?: CheckoutDraftState) {
  return useReducer(checkoutDraftReducer, initialState, getInitialDraftState);
}

export function useCheckoutDraftInitialState() {
  return useContext(CheckoutDraftInitialStateContext);
}
