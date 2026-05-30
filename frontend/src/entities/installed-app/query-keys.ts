export const installedAppKeys = {
  all: ['installed-apps'] as const,
  list: () => [...installedAppKeys.all, 'list'] as const,
}
