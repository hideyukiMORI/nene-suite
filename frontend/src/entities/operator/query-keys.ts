export const operatorKeys = {
  all: ['operators'] as const,
  list: () => [...operatorKeys.all, 'list'] as const,
}
