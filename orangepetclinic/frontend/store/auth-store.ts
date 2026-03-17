import { create } from "zustand";

type UserRole = "admin" | "customer" | null;

type AuthState = {
  accessToken: string | null;
  role: UserRole;
  setAuth: (token: string, role: Exclude<UserRole, null>) => void;
  clearAuth: () => void;
};

export const useAuthStore = create<AuthState>((set) => ({
  accessToken: null,
  role: null,
  setAuth: (token, role) => set({ accessToken: token, role }),
  clearAuth: () => set({ accessToken: null, role: null }),
}));
