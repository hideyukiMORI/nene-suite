<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Provisions the first (or any subsequent) apex operator for the suite.
 * Called by the Tier B installer; not exposed over HTTP in Phase 1.
 *
 * Validates email format and minimum password length, enforces email uniqueness,
 * hashes the password with bcrypt, then — in a single database transaction
 * (ADR 0007 §5) — saves the operator and records `apex_operator.created` in the
 * suite audit trail (audit-trail §4). The repository and recorder are built from
 * the transaction's executor so both writes commit or roll back together; an audit
 * failure leaves no orphaned operator row.
 *
 * Password hash is never written to `after_json` — only `{id, email, displayName}`.
 */
final readonly class CreateOperatorUseCase implements CreateOperatorUseCaseInterface
{
    private const MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private OperatorRepositoryFactoryInterface $operators,
        private SuiteAuditRecorderFactoryInterface $audit,
        private PasswordHasher $hasher,
        private string $suiteId,
    ) {
    }

    public function execute(CreateOperatorInput $input): CreateOperatorOutput
    {
        $this->validateEmail($input->email);
        $this->validatePassword($input->password);

        // Hash outside the transaction — bcrypt is slow and must not hold the
        // transaction (and its locks) open longer than the two writes need.
        $passwordHash = $this->hasher->hash($input->password);

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input, $passwordHash): CreateOperatorOutput {
                $operators = $this->operators->create($query);
                $audit = $this->audit->create($query);

                if ($operators->findByEmail($input->email) !== null) {
                    throw new OperatorEmailConflictException($input->email);
                }

                $now = gmdate('Y-m-d\TH:i:s\Z');
                $operator = new Operator(
                    id: (string) new Ulid(),
                    email: $input->email,
                    passwordHash: $passwordHash,
                    displayName: $input->displayName,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $operators->save($operator);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'apex_operator.created',
                    entityType: 'apex_operator',
                    entityId: $operator->id,
                    beforeJson: null,
                    afterJson: OperatorView::toArray($operator),
                    source: 'installer_ui',
                    requestId: $input->requestId,
                ));

                return new CreateOperatorOutput($operator);
            },
        );
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new OperatorValidationException('email', 'A valid email address is required.');
        }
    }

    private function validatePassword(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new OperatorValidationException(
                'password',
                'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.',
            );
        }
    }
}
