import { http, HttpResponse } from 'msw'

export const installedAppHandlers = [
  http.get('/api/v1/installed-apps', () =>
    HttpResponse.json({
      apps: [
        {
          catalogId: 'nene-invoice',
          name: 'NeNe Invoice',
          publicUrl: 'https://example.com/nene-invoice/',
          databaseName: 'nene_invoice',
          ssotRole: 'billing',
        },
        {
          catalogId: 'nene-clear',
          name: 'NeNe Clear',
          publicUrl: 'https://example.com/nene-clear/',
          databaseName: 'nene_clear',
          ssotRole: 'reconciliation_evidence',
        },
      ],
    }),
  ),
]
