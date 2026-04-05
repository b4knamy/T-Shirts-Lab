import { configureStore } from '@reduxjs/toolkit';
import authReducer, { logout } from './slices/authSlice';
import cartReducer from './slices/cartSlice';
import productReducer from './slices/productSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    cart: cartReducer,
    products: productReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: false,
    }),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;

// ── Sync Redux state with the axios interceptor events ─────────────────────
// When the interceptor can't refresh the token it dispatches 'auth:logout'.
// When it successfully refreshes, 'auth:refreshed' fires — we don't need to
// do anything in the store for that case because localStorage is already
// updated and the next request will carry the new token automatically.
window.addEventListener('auth:logout', () => {
  store.dispatch(logout());
});
