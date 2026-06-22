import { http, HttpResponse } from 'msw'
import { CONFLICT_OPERATOR } from './membership'

export const operatorHandlers = [
  http.get('/api/v1/operators', () =>
    HttpResponse.json({
      operators: [
        {
          id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
          email: 'operator@example.com',
          displayName: 'Example Operator',
        },
        { id: '01J8XRNEWOP000000000000ZAB', email: 'new@example.com', displayName: null },
        { id: CONFLICT_OPERATOR, email: 'conflict@example.com', displayName: 'Conflict Operator' },
      ],
    }),
  ),
]
