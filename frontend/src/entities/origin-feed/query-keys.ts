export const originFeedKeys = {
  all: ['origin', 'feeds'] as const,
  announcements: (locale: string) => [...originFeedKeys.all, 'announcements', locale] as const,
  houseAds: (locale: string) => [...originFeedKeys.all, 'house-ads', locale] as const,
}
