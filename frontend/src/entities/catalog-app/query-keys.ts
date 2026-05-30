export const catalogAppKeys = {
  all: ['catalog-apps'] as const,
  list: () => [...catalogAppKeys.all, 'list'] as const,
}
