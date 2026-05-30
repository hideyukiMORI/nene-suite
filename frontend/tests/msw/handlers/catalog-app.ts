import { http, HttpResponse } from 'msw'

export const catalogAppHandlers = [
  http.get('/api/v1/catalog/apps', () =>
    HttpResponse.json({
      version: 1,
      apps: [
        {
          id: 'nene-invoice',
          name: 'NeNe Invoice',
          repository: 'hideyukiMORI/nene-invoice',
          path: 'nene-invoice',
          status: 'installable',
          requires: [],
          provides: ['billing-api'],
          installEntry: '/install/index.php',
          databaseEnvPrefix: 'NENE_INVOICE_DB_',
        },
        {
          id: 'nene-clear',
          name: 'NeNe Clear',
          repository: 'hideyukiMORI/nene-clear',
          path: 'nene-clear',
          status: 'installable',
          requires: ['nene-invoice'],
          provides: ['reconciliation-api'],
          installEntry: '/install/index.php',
          databaseEnvPrefix: 'NENE_CLEAR_DB_',
        },
        {
          id: 'nene-vault',
          name: 'NeNe Vault',
          repository: 'hideyukiMORI/nene-vault',
          path: 'nene-vault',
          status: 'planned',
          requires: [],
          provides: ['archive-api'],
          installEntry: '/install/index.php',
          databaseEnvPrefix: 'NENE_VAULT_DB_',
        },
      ],
    }),
  ),
]
