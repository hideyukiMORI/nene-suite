import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import {
  render,
  renderHook,
  type RenderHookOptions,
  type RenderHookResult,
  type RenderOptions,
  type RenderResult,
} from '@testing-library/react'
import type { ReactElement, ReactNode } from 'react'
import { MemoryRouter } from 'react-router-dom'
import { I18nProvider } from '@/shared/i18n'
import { ThemeProvider } from '@/shared/ui'

export function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })
}

function Providers({ children }: { children: ReactNode }) {
  const queryClient = createTestQueryClient()

  return (
    <ThemeProvider>
      <MemoryRouter>
        <I18nProvider>
          <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
        </I18nProvider>
      </MemoryRouter>
    </ThemeProvider>
  )
}

export function renderWithProviders(ui: ReactElement, options?: RenderOptions): RenderResult {
  return render(ui, { wrapper: Providers, ...options })
}

/**
 * Render a hook inside the app providers (Router + React Query + i18n). Pairs
 * with MSW handlers to exercise feature hooks at the network boundary.
 */
export function renderHookWithProviders<Result, Props>(
  hook: (initialProps: Props) => Result,
  options?: RenderHookOptions<Props>,
): RenderHookResult<Result, Props> {
  return renderHook(hook, { wrapper: Providers, ...options })
}
