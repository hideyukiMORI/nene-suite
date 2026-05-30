export const installSessionKeys = {
  all: ['install-sessions'] as const,
  detail: (id: string) => [...installSessionKeys.all, 'detail', id] as const,
}
