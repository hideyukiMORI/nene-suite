export const originUpdateKeys = {
  all: ['origin', 'updates'] as const,
  list: () => [...originUpdateKeys.all] as const,
}
