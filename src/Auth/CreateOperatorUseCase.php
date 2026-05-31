<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Provisions the first (or any subsequent) apex operator for the suite.
 * Called by the Tier B installer; not exposed over HTTP in Phase 1.
 *
 * Validates email format and minimum password length, enforces email uniqueness,
 * hashes the password with bcrypt, saves the operator, and records
 * `apex_operator.created` in the suite audit trail (audit-trail §4).
 *
 * Password hash is never written to `after_json` — only `{id, email, displayName}`.
 */
final readonly class CreateOperatorUseCase implements CreateOperatorUseCaseInterface
{
    private const MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private OperatorRepositoryInterface $operators,
        private PasswordHasher $hasher,
        private SuiteAuditRecorderInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(CreateOperatorInput $input): CreateOperatorOutput
    {
        $this->validateEmail($input->email);
        $this->validatePassword($input->password);

        if ($this->operators->findByEmail($input->email) !== null) {
            throw new OperatorEmailConflictException($input->email);
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $operator = new Operator(
            id: (string) new Ulid(),
            email: $input->email,
            passwordHash: $this->hasher->hash($input->password),
            displayName: $input->displayName,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->operators->save($operator);

        $this->audit->record(new RecordSuiteAuditEventCommand(
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
