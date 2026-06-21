/**
 * English message catalog — source of truth for all NeNe Suite UI strings.
 *
 * Key naming: suite.{feature}.{element} | common.{element}
 * Param interpolation: {{paramName}}
 *
 * All other locales are Partial<MessageCatalog> and fall back to these values.
 * Operator-facing disclaimer copy aligns with docs/explanation/installer-disclaimer-copy.md
 */

export const en = {
  // ── Common actions ───────────────────────────────────────────────────────
  'common.actions.cancel': 'Cancel',
  'common.actions.confirm': 'Confirm',
  'common.actions.retry': 'Retry',
  'common.actions.back': 'Back',
  'common.actions.next': 'Next',
  'common.actions.finish': 'Finish',
  'common.actions.close': 'Close',
  'common.actions.save': 'Save',
  'common.actions.loading': 'Loading…',

  'common.state.loading': 'Loading…',
  'common.state.empty': 'Nothing to show yet.',
  'common.state.error': 'Something went wrong.',

  'common.error.unknown': 'Unknown error',
  'common.error.unauthorized': 'Authentication required. Please sign in.',
  'common.error.forbidden': 'You do not have permission to perform this action.',
  'common.error.notFound': 'The requested resource was not found.',
  'common.error.conflict': 'A conflict occurred. The resource may already exist.',
  'common.error.validation': 'The submitted data is invalid.',
  'common.error.rateLimit': 'Too many requests. Please wait and try again.',
  'common.error.serverError': 'A server error occurred. Please try again later.',

  'common.dialog.close': 'Close dialog',

  // ── Locale selector ──────────────────────────────────────────────────────
  'suite.locale.label': 'Language',
  'suite.locale.select': 'Select language',

  // ── Apex navigation ──────────────────────────────────────────────────────
  'suite.nav.home': 'Home',
  'suite.nav.install': 'Install apps',
  'suite.nav.audit': 'Audit log',
  'suite.nav.settings': 'Settings',
  'suite.nav.logout': 'Log out',
  'suite.nav.openMenu': 'Open navigation menu',
  'suite.nav.closeMenu': 'Close navigation menu',
  'suite.nav.appTitle': 'NeNe Suite',
  'suite.nav.organizations': 'Organizations',

  // ── Auth (apex login) ────────────────────────────────────────────────────
  'suite.auth.subtitle': 'Sign in to manage your NeNe installation',
  'suite.auth.emailLabel': 'Email',
  'suite.auth.emailPlaceholder': 'operator@example.com',
  'suite.auth.passwordLabel': 'Password',
  'suite.auth.passwordPlaceholder': '••••••••',
  'suite.auth.signIn': 'Sign in',
  'suite.auth.signingIn': 'Signing in…',
  'suite.auth.invalidCredentials': 'Invalid email or password',

  // ── App launcher (apex home) ─────────────────────────────────────────────
  'suite.launcher.title': 'Installed applications',
  'suite.launcher.description': 'Open an application installed through NeNe Suite.',
  'suite.launcher.empty.title': 'No applications installed',
  'suite.launcher.empty.description':
    'Run the installer to add NeNe Invoice, Clear, Records, or other catalog apps.',
  'suite.launcher.openApp': 'Open {{appName}}',
  'suite.launcher.ssotBilling': 'Billing system of record',
  'suite.launcher.ssotEvidence': 'Reconciliation evidence',
  'suite.launcher.ssotCms': 'Flexible content platform',
  'suite.launcher.ssotArchive': 'Document archive',
  'suite.launcher.startInstall': 'Start installer',

  // ── Installer wizard — shell ─────────────────────────────────────────────
  'suite.install.wizard.title': 'NeNe Suite installer',
  'suite.install.wizard.step.apps': 'Select apps',
  'suite.install.wizard.step.disclaimer': 'Disclaimer',
  'suite.install.wizard.step.review': 'Review',
  'suite.install.wizard.step.complete': 'Complete',

  // ── Installer — app selection ────────────────────────────────────────────
  'suite.install.apps.title': 'Choose applications to install',
  'suite.install.apps.description':
    'Each app uses its own database. Dependencies are installed in the correct order.',
  'suite.install.apps.selectedCount': '{{count}} app(s) selected',
  'suite.install.apps.requiredBy': 'Required by {{appName}}',
  'suite.install.apps.status.planned': 'Coming soon',
  'suite.install.apps.status.installable': 'Available',
  'suite.install.apps.status.deprecated': 'Deprecated',
  'suite.install.apps.empty': 'No installable apps in the catalog.',

  // ── Installer — disclaimer ───────────────────────────────────────────────
  'suite.disclaimer.title': 'Important notice',
  'suite.disclaimer.shortNotice':
    'NeNe Suite helps you install and configure NeNe applications. It does **not** guarantee business results, legal compliance, or accounting correctness. You remain solely responsible for how installed apps are used and for obtaining professional advice when required.',
  'suite.disclaimer.checkbox':
    'I understand that NeNe Suite provides technical installation only and does not certify tax, legal, or accounting compliance.',
  'suite.disclaimer.mustAccept': 'You must accept the disclaimer before continuing.',
  'suite.disclaimer.link': 'Read full disclaimer',

  // ── Installer — review ───────────────────────────────────────────────────
  'suite.install.review.title': 'Review configuration',
  'suite.install.review.description':
    'Confirm selected apps, databases, and integrations before provisioning.',
  'suite.install.review.orgName': 'Organization name',
  'suite.install.review.selectedApps': 'Selected apps',
  'suite.install.review.integrations.title': 'HTTP integrations',
  'suite.install.review.integrations.clearToInvoice':
    'Clear → Invoice (service API, explicit operator enable)',
  'suite.install.review.integrations.none': 'None enabled',
  'suite.install.review.preCompleteSummary':
    'You are about to finish setup. NeNe Suite will write configuration files and provision databases. **This does not certify** that your organization meets any tax, accounting, or industry rule.',

  // ── Installer — complete ─────────────────────────────────────────────────
  'suite.install.complete.title': 'Installation complete',
  'suite.install.complete.description':
    'Configuration has been written. Open the apex shell to launch installed applications.',
  'suite.install.complete.goToLauncher': 'Go to app launcher',

  // ── Installer — errors ───────────────────────────────────────────────────
  'suite.install.errors.sessionFailed': 'Installation could not be completed.',
  'suite.install.errors.dependencyMissing':
    'Selected apps have unmet dependencies. Adjust your selection.',
  'suite.install.errors.disclaimerRequired': 'Accept the disclaimer to continue.',

  // ── Apex footer ──────────────────────────────────────────────────────────
  'suite.disclaimer.footer': 'Setup orchestration only — no business or legal warranty.',

  // ── Audit log UI (Phase 2+) ──────────────────────────────────────────────
  'suite.audit.title': 'Orchestration audit log',
  'suite.audit.description':
    'History of suite configuration changes (before/after snapshots). Domain audit remains in each app.',
  'suite.audit.export': 'Export',
  'suite.audit.empty': 'No audit events yet.',
  'suite.audit.column.action': 'Action',
  'suite.audit.column.actor': 'Actor',
  'suite.audit.column.time': 'Time',

  // ── Organizations (superadmin console) ───────────────────────────────────
  'suite.org.title': 'Organizations',
  'suite.org.description': 'Manage tenant organizations. Platform superadmin only.',
  'suite.org.empty': 'No organizations yet.',
  'suite.org.column.name': 'Name',
  'suite.org.column.slug': 'Slug',
  'suite.org.column.status': 'Status',
  'suite.org.column.actions': 'Actions',
  'suite.org.status.active': 'Active',
  'suite.org.status.disabled': 'Disabled',
  'suite.org.create.title': 'Create organization',
  'suite.org.create.nameLabel': 'Name',
  'suite.org.create.namePlaceholder': 'Acme KK',
  'suite.org.create.slugLabel': 'Slug',
  'suite.org.create.slugPlaceholder': 'acme-kk',
  'suite.org.create.submit': 'Create',
  'suite.org.create.submitting': 'Creating…',
  'suite.org.rename.action': 'Rename',
  'suite.org.rename.nameLabel': 'New name',
  'suite.org.rename.submitting': 'Renaming…',
  'suite.org.disable.action': 'Disable',
  'suite.org.members': 'Members',
  'suite.org.error.slugConflict': 'An organization with this slug already exists.',
  'suite.org.error.validation': 'Check the organization name and slug.',
  'suite.org.error.notFound': 'That organization no longer exists.',
  'suite.org.indicator.superadmin': 'Superadmin',
  'suite.org.indicator.activeOrg': 'Org: {{org}}',
  'suite.org.indicator.noOrg': 'No active organization',
  'suite.org.indicator.role': 'Role: {{role}}',

  // ── Memberships (superadmin console) ─────────────────────────────────────
  'suite.member.title': 'Members',
  'suite.member.description': 'Manage who belongs to this organization and their role.',
  'suite.member.empty': 'No members yet.',
  'suite.member.column.operator': 'Operator',
  'suite.member.column.role': 'Role',
  'suite.member.column.actions': 'Actions',
  'suite.member.role.admin': 'Admin',
  'suite.member.role.member': 'Member',
  'suite.member.role.viewer': 'Viewer',
  'suite.member.grant.title': 'Add member',
  'suite.member.grant.operatorIdLabel': 'Operator ID',
  'suite.member.grant.operatorIdPlaceholder': '01J8XR0G7Q9V2H7K3N5M0B8TCA',
  'suite.member.grant.roleLabel': 'Role',
  'suite.member.grant.submit': 'Add',
  'suite.member.grant.submitting': 'Adding…',
  'suite.member.revoke.action': 'Remove',
  'suite.member.error.conflict': 'This operator is already a member of the organization.',
  'suite.member.error.invariant': 'The organization must keep at least one admin.',
  'suite.member.error.validation': 'Check the operator ID and role.',
  'suite.member.error.notFound': 'That membership no longer exists.',
}

export type MessageCatalog = typeof en
