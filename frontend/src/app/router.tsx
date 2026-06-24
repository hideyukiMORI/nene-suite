import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { AppShell } from '@/features/app-shell'
import { AccountPage } from '@/pages/account/AccountPage'
import { AuditEventsPage } from '@/pages/admin/audit-events/AuditEventsPage'
import { MembershipsPage } from '@/pages/admin/organizations/MembershipsPage'
import { OrganizationsPage } from '@/pages/admin/organizations/OrganizationsPage'
import { CatalogPage } from '@/pages/catalog/CatalogPage'
import { HomePage } from '@/pages/home/HomePage'
import { InstallPage } from '@/pages/install/InstallPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { SettingsPage } from '@/pages/settings/SettingsPage'
import { RequireAuth, RequireSuperadmin } from './auth-gate'
import { RootErrorBoundary } from './root-error-boundary'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: <RequireAuth />,
    errorElement: <RootErrorBoundary />,
    children: [
      {
        element: <AppShell />,
        children: [
          { path: '/', element: <HomePage /> },
          { path: '/catalog', element: <CatalogPage /> },
          { path: '/install', element: <InstallPage /> },
          { path: '/account', element: <AccountPage /> },
          { path: '/settings', element: <SettingsPage /> },
          { path: '/admin/audit-events', element: <AuditEventsPage /> },
          {
            element: <RequireSuperadmin />,
            children: [
              { path: '/admin/organizations', element: <OrganizationsPage /> },
              { path: '/admin/organizations/:id/memberships', element: <MembershipsPage /> },
            ],
          },
        ],
      },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
