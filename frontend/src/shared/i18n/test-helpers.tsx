import { render, type RenderOptions, type RenderResult } from '@testing-library/react'
import type { ReactElement } from 'react'
import { I18nProvider } from './i18n-context'
import { LOCALE_STORAGE_KEY, type SupportedLocale } from './locales'

export interface RenderWithI18nOptions extends Omit<RenderOptions, 'wrapper'> {
  locale?: SupportedLocale
}

export function renderWithI18n(
  ui: ReactElement,
  { locale = 'en', ...options }: RenderWithI18nOptions = {},
): RenderResult {
  try {
    localStorage.setItem(LOCALE_STORAGE_KEY, locale)
  } catch {
    // ignore
  }
  return render(ui, {
    wrapper: ({ children }) => <I18nProvider>{children}</I18nProvider>,
    ...options,
  })
}

export function withI18n(locale: SupportedLocale = 'en') {
  return function I18nDecorator(Story: () => ReactElement): ReactElement {
    try {
      localStorage.setItem(LOCALE_STORAGE_KEY, locale)
    } catch {
      // ignore
    }
    return (
      <I18nProvider>
        <Story />
      </I18nProvider>
    )
  }
}
