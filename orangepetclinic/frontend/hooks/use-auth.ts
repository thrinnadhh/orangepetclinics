import { useAuthStore } from "@/store/auth-store";

export function useAuth() {
  const { accessToken, role, setAuth, clearAuth } = useAuthStore();

  return {
    accessToken,
    role,
    isAuthenticated: Boolean(accessToken),
    setAuth,
    clearAuth,
  };
}
