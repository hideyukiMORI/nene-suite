import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { AuditEventsPage } from '@/pages/admin/audit-events/AuditEventsPage'
import { HomePage } from '@/pages/home/HomePage'
import { InstallPage } from '@/pages/install/InstallPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { RequireAuth } from './auth-gate'
import { RootErrorBoundary } from './root-error-boundary'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: <RequireAuth />,
    errorElement: <RootErrorBoundary />,
    children: [
      { path: '/', element: <HomePage /> },
      { path: '/install', element: <InstallPage /> },
      { path: '/admin/audit-events', element: <AuditEventsPage /> },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
