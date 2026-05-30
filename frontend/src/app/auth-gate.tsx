import { Navigate, Outlet } from 'react-router-dom'
import { authStore } from '@/entities/auth'

/** Fail closed: unauthenticated operators are sent to the login route. */
export function RequireAuth() {
  if (!authStore.isAuthenticated()) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}
