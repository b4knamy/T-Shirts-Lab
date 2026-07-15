import {
  createContext,
  useState,
  useEffect,
  useCallback,
  type PropsWithChildren,
} from 'react';
import type { User } from '../types';
import {
  authApi,
  type RegisterData,
  type LoginData,
} from '../services/api/auth';

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  login: (
    data: LoginData,
  ) => Promise<{ user: User; access_token: string; refresh_token: string }>;
  register: (
    data: RegisterData,
  ) => Promise<{ user: User; access_token: string; refresh_token: string }>;
  loadProfile: () => Promise<User>;
  signOut: () => void;
  resetError: () => void;
  setUser: (user: User | null) => void;
}

export const AuthContext = createContext<AuthContextType | undefined>(
  undefined,
);

interface AuthProviderProps extends PropsWithChildren {
  initialUser?: User | null;
  initialIsAuthenticated?: boolean;
}

export function AuthProvider({
  children,
  initialUser = null,
  initialIsAuthenticated,
}: AuthProviderProps) {
  const [user, setUserState] = useState<User | null>(initialUser);
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(
    initialIsAuthenticated !== undefined
      ? initialIsAuthenticated
      : !!localStorage.getItem('accessToken'),
  );
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const setUser = useCallback((u: User | null) => {
    setUserState(u);
  }, []);

  const login = useCallback(async (data: LoginData) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await authApi.login(data);
      const result = response.data.data;
      localStorage.setItem('accessToken', result.access_token);
      localStorage.setItem('refreshToken', result.refresh_token);
      setUserState(result.user);
      setIsAuthenticated(true);
      return result;
    } catch (err: unknown) {
      const apiErr = err as {
        response?: { data?: { error?: { message?: string } } };
      };
      const errorMsg = apiErr.response?.data?.error?.message || 'Login failed';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const register = useCallback(async (data: RegisterData) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await authApi.register(data);
      const result = response.data.data;
      localStorage.setItem('accessToken', result.access_token);
      localStorage.setItem('refreshToken', result.refresh_token);
      setUserState(result.user);
      setIsAuthenticated(true);
      return result;
    } catch (err: unknown) {
      const apiErr = err as {
        response?: { data?: { error?: { message?: string } } };
      };
      const errorMsg =
        apiErr.response?.data?.error?.message || 'Registration failed';
      setError(errorMsg);
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const loadProfile = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await authApi.getProfile();
      const loadedUser = response.data.data as User;
      setUserState(loadedUser);
      setIsAuthenticated(true);
      return loadedUser;
    } catch (err: unknown) {
      setIsAuthenticated(false);
      setUserState(null);
      const apiErr = err as {
        response?: { data?: { error?: { message?: string } } };
      };
      const errorMsg =
        apiErr.response?.data?.error?.message || 'Failed to fetch profile';
      throw new Error(errorMsg);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const signOut = useCallback(() => {
    setUserState(null);
    setIsAuthenticated(false);
    setError(null);
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
  }, []);

  const resetError = useCallback(() => {
    setError(null);
  }, []);

  useEffect(() => {
    const handleLogout = () => {
      signOut();
    };
    window.addEventListener('auth:logout', handleLogout);
    return () => {
      window.removeEventListener('auth:logout', handleLogout);
    };
  }, [signOut]);

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated,
        isLoading,
        error,
        login,
        register,
        loadProfile,
        signOut,
        resetError,
        setUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
