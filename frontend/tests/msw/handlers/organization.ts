import { http, HttpResponse } from 'msw'

interface CreateBody {
  name?: unknown
  slug?: unknown
}

interface RenameBody {
  name?: unknown
}

function organization(
  id: string,
  name: string,
  slug: string,
  status: 'active' | 'disabled' = 'active',
) {
  return { id, externalId: `${id}EXT`, name, slug, status }
}

export const organizationHandlers = [
  http.get('/api/v1/organizations', () =>
    HttpResponse.json({
      organizations: [organization('01J8XR0G7Q9V2H7K3N5M0B8TCA', 'Acme KK', 'acme-kk')],
    }),
  ),
  http.post('/api/v1/organizations', async ({ request }) => {
    const body = (await request.json()) as CreateBody

    if (body.slug === 'taken') {
      return HttpResponse.json(
        {
          type: 'https://nene-suite.dev/problems/organization-slug-conflict',
          title: 'Organization slug already exists',
          status: 409,
          detail: 'An organization with this slug already exists.',
          instance: '/api/v1/organizations',
        },
        { status: 409 },
      )
    }

    const name = typeof body.name === 'string' ? body.name : 'New Org'
    const slug = typeof body.slug === 'string' ? body.slug : 'new-org'

    return HttpResponse.json(organization('01J8XRNEWORG00000000000ZAB', name, slug), {
      status: 201,
    })
  }),
  http.patch('/api/v1/organizations/:id', async ({ params, request }) => {
    const body = (await request.json()) as RenameBody
    const name = typeof body.name === 'string' ? body.name : 'Renamed'

    return HttpResponse.json(organization(String(params.id), name, 'acme-kk'))
  }),
  http.post('/api/v1/organizations/:id/disable', ({ params }) =>
    HttpResponse.json(organization(String(params.id), 'Acme KK', 'acme-kk', 'disabled')),
  ),
]
