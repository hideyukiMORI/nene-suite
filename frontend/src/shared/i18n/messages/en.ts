import type { MessageCatalog, MessageKey } from './ja'

/**
 * English message catalog — the reference locale and runtime fallback for all
 * NeNe Suite UI strings.
 *
 * Authority note (規約 04 I18N-8): the message key set is owned by `ja.ts`
 * (`MessageKey = keyof typeof ja`). This catalog is typed `MessageCatalog`
 * (= `Record<MessageKey, string>`), so it mirrors `ja` exactly — adding or
 * removing a key in `ja` becomes a compile error here.
 *
 * Key naming: suite.{feature}.{element} | common.{element}
 * Param interpolation: {{paramName}}
 * All other locales are Partial<MessageCatalog> and fall back to these values.
 * Operator-facing disclaimer copy aligns with docs/explanation/installer-disclaimer-copy.md
 */

export const en: MessageCatalog = {
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

  // ── Theme ────────────────────────────────────────────────────────────────
  'suite.theme.toggle': 'Toggle light / dark theme',
  'suite.theme.light': 'Light',
  'suite.theme.dark': 'Dark',

  // ── Apex navigation ──────────────────────────────────────────────────────
  'suite.nav.home': 'Home',
  'suite.nav.catalog': 'Catalog',
  'suite.nav.install': 'Install apps',
  'suite.nav.audit': 'Audit log',
  'suite.nav.admin': 'Admin',
  'suite.nav.settings': 'Settings',
  'suite.nav.logout': 'Log out',
  'suite.nav.getApps': 'Get apps',
  'suite.nav.openMenu': 'Open navigation menu',
  'suite.nav.closeMenu': 'Close navigation menu',
  'suite.nav.appTitle': 'NeNe Suite',
  'suite.nav.organizations': 'Organizations',
  'suite.nav.group.operations': 'Operations',
  'suite.nav.group.governance': 'Governance',
  'suite.nav.group.support': 'Support',

  // ── App shell (header chrome) ────────────────────────────────────────────
  'suite.shell.navLabel': 'Navigation',
  'suite.shell.search': 'Search or jump to…',
  'suite.shell.commandPalette': 'Command palette',
  'suite.shell.commandPalette.empty': 'No matches.',
  'suite.shell.commandPalette.navGroup': 'Go to',
  'suite.shell.commandPalette.actionGroup': 'Actions',
  'suite.shell.notifications': 'Notifications',
  'suite.shell.notifications.placeholder':
    'Update, announcement, and key-expiry signals arrive with Origin (Phase B).',
  'suite.shell.account.menu': 'Account menu',
  'suite.shell.account.profile': 'Profile',
  'suite.shell.account.security': 'Security',

  // ── Placeholder surfaces (built in a later phase) ────────────────────────
  'suite.catalog.title': 'App catalog',
  'suite.catalog.placeholder':
    'The catalog store lands in a later phase. For now, install apps through the wizard.',
  'suite.catalog.subtitle': 'Browse NeNe apps and add them to your installation.',
  'suite.catalog.filter.all': 'All',
  'suite.catalog.get': 'Get',
  'suite.catalog.requires': 'Requires {{names}}',
  'suite.catalog.empty': 'No apps match this filter.',
  'suite.catalog.version.installed': 'Installed {{version}}',
  'suite.catalog.version.available': 'Latest {{version}}',

  // ── App detail drawer ────────────────────────────────────────────────────
  'suite.appDetail.view': 'Details',
  'suite.appDetail.role': 'Role',
  'suite.appDetail.provides': 'Provides {{names}}',
  'suite.appDetail.changelog': 'Change history',
  'suite.appDetail.changelogPlaceholder': 'The change history is Origin-fed (Phase B).',
  'suite.appDetail.latest': 'Latest {{version}}',
  'suite.appDetail.viewChangelog': 'View change history',

  // ── Admin hub ────────────────────────────────────────────────────────────
  'suite.admin.title': 'Admin',
  'suite.admin.subtitle': 'Manage organizations, members, operators, and federation keys.',
  'suite.admin.tab.organizations': 'Organizations',
  'suite.admin.tab.members': 'Members',
  'suite.admin.tab.operators': 'Operators',
  'suite.admin.tab.keys': 'Federation keys',
  'suite.admin.members.selectOrg':
    'Select an organization from the Organizations tab to manage its members.',
  'suite.admin.operators.email': 'Email',
  'suite.admin.operators.name': 'Name',
  'suite.admin.operators.scope': 'Scope',
  'suite.admin.operators.mfa': 'MFA',
  'suite.admin.operators.lastAccess': 'Last access',
  'suite.admin.operators.empty': 'No operators.',
  'suite.admin.operators.phaseB': 'Shown with the operator detail API (Phase B)',
  'suite.admin.keys.placeholder':
    'Federation signing keys are managed via operator commands today; a read API and console land in a later phase.',
  'suite.settings.title': 'Settings',
  'suite.settings.placeholder':
    'Disclaimer, Origin polling, edition, and update channel settings land in a later phase.',
  'suite.settings.subtitle': 'Suite configuration, integrations, and legal.',
  'suite.settings.section.general': 'General',
  'suite.settings.section.about': 'Help / About',
  'suite.settings.edition': 'Edition',
  'suite.settings.federationKeys': 'Federation keys',
  'suite.settings.federationKeys.manage': 'Manage',
  'suite.settings.originPolling': 'Origin polling interval',
  'suite.settings.updateChannel': 'Update channel',
  'suite.settings.phaseB': 'Configurable in a later phase',
  'suite.settings.disclaimer': 'Disclaimer',
  'suite.settings.disclaimer.view': 'View disclaimer',
  'suite.settings.disclaimer.binding': 'Binding — please read before operating (ADR 0003).',
  'suite.account.title': 'Account & security',
  'suite.account.placeholder':
    'Profile, password / MFA, and active sessions land in a later phase.',
  'suite.account.subtitle': 'Your profile, security, and signed-in sessions.',
  'suite.account.tab.profile': 'Profile',
  'suite.account.tab.security': 'Security',
  'suite.account.tab.sessions': 'Sessions',
  'suite.account.field.email': 'Email',
  'suite.account.field.role': 'Role',
  'suite.account.profile.phaseB': 'Editing your display name and email lands in a later phase.',
  'suite.account.security.title': 'Password & MFA',
  'suite.account.security.phaseB':
    'Password change and multi-factor authentication land in a later phase.',
  'suite.account.sessions.current': 'Current session',
  'suite.account.sessions.expires': 'Expires',
  'suite.account.sessions.activeOrg': 'Active organization',
  'suite.account.sessions.none': 'No active organization',
  'suite.account.sessions.phaseB':
    'Per-device sessions and “log out everywhere” land in a later phase.',

  // ── Home / dashboard ─────────────────────────────────────────────────────
  'suite.home.eyebrow': 'Operations console',
  'suite.home.greeting': 'Welcome back,',
  'suite.home.subtitle': 'Launch installed apps, and manage updates, organizations, and access.',
  'suite.home.appsTitle': 'Your apps',
  'suite.home.open': 'Open',
  'suite.home.status.active': 'Active',
  'suite.home.kpi.installed': 'Active apps',
  'suite.home.kpi.updates': 'Updates',
  'suite.home.kpi.members': 'Members',
  'suite.home.kpi.audit': 'Audit (24h)',
  'suite.home.kpi.unavailable': 'Available with Origin (Phase B)',
  'suite.home.firstRun.title': 'Welcome to NeNe Suite',
  'suite.home.firstRun.subtitle':
    'Install the apps your back office needs — then launch and manage them from here.',
  'suite.home.updates.title': 'Available updates',
  'suite.home.updates.checking': 'Checking for updates…',
  'suite.home.updates.empty': 'No update information.',
  'suite.home.updates.latest': 'Latest {{version}}',
  'suite.home.updates.status.up_to_date': 'Up to date',
  'suite.home.updates.status.update_available': 'Update available',
  'suite.home.updates.status.forced': 'Security update',
  'suite.home.updates.status.unknown': 'Latest available',
  'suite.home.updates.status.unavailable': 'Unavailable',
  'suite.home.announcements.title': 'Announcements',
  'suite.home.announcements.empty': 'No announcements.',
  'suite.home.houseAds.title': 'Sponsored',
  'suite.home.feeds.placeholder':
    'Update, announcement, and house-ad feeds arrive with Origin (Phase B).',

  // ── Auth (apex login) ────────────────────────────────────────────────────
  'suite.auth.subtitle': 'Sign in to manage your NeNe installation',
  'suite.auth.emailLabel': 'Email',
  'suite.auth.emailPlaceholder': 'operator@example.com',
  'suite.auth.passwordLabel': 'Password',
  'suite.auth.passwordPlaceholder': '••••••••',
  'suite.auth.signIn': 'Sign in',
  'suite.auth.signingIn': 'Signing in…',
  'suite.auth.invalidCredentials': 'Invalid email or password',
  'suite.auth.emptyFields': 'Enter your email and password',
  'suite.auth.help.contact': "Can't sign in? Contact your install administrator.",
  'suite.auth.hero.headline': 'Your data is yours.',
  'suite.auth.hero.support': 'Start free. Move to your own server whenever you need to.',
  'suite.auth.hero.eyebrow': 'Operator sign-in',
  'suite.auth.hero.portfolioLabel': 'Your NeNe portfolio',
  'suite.auth.hero.trust.export': 'Export anytime',
  'suite.auth.hero.trust.selfhost': 'Move to your own server',
  'suite.auth.hero.trust.nolockin': 'No lock-in',
  'suite.auth.footer.poweredBy': 'Powered by NENE2 · © {{year}} AYANE',
  'suite.auth.footer.site': 'Website',
  'suite.auth.footer.help': 'Help',

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
  'suite.install.wizard.step.database': 'Databases',
  'suite.install.wizard.step.disclaimer': 'Disclaimer',
  'suite.install.wizard.step.review': 'Review',
  'suite.install.wizard.step.complete': 'Complete',
  'suite.install.wizard.step.announce': 'Step {{current}} of {{total}}: {{label}}',

  // ── Installer — app selection ────────────────────────────────────────────
  'suite.install.apps.title': 'Choose applications to install',
  'suite.install.apps.description':
    'Each app uses its own database. Dependencies are installed in the correct order.',
  'suite.install.apps.selectedCount': '{{count}} app(s) selected',
  'suite.install.apps.requires': 'Requires {{appName}}',
  'suite.install.apps.status.planned': 'Coming soon',
  'suite.install.apps.status.installable': 'Available',
  'suite.install.apps.status.deprecated': 'Deprecated',
  'suite.install.apps.empty': 'No installable apps in the catalog.',

  // ── Installer — database targets ─────────────────────────────────────────
  'suite.install.database.title': 'Database targets',
  'suite.install.database.description':
    'For each app, provision a new database or adopt an existing one. An adopted database is registered as-is — the suite never creates or modifies it.',
  'suite.install.database.mode.label': 'Database mode for {{appName}}',
  'suite.install.database.mode.provision': 'Provision new',
  'suite.install.database.mode.adopt': 'Adopt existing',
  'suite.install.database.server.label': 'Server',
  'suite.install.database.server.placeholder': 'Suite server (default)',
  'suite.install.database.name.label': 'Database name',
  'suite.install.database.name.placeholder': 'Suite convention (default)',
  'suite.install.database.empty': 'No apps selected.',
  'suite.install.database.adopt.note':
    'Both fields are optional. Leave blank to use suite defaults.',
  'suite.install.database.server.help': "Blank uses the suite's default server.",
  'suite.install.database.name.help': 'Blank follows the suite naming convention.',
  'suite.install.database.summary.provision': 'provision · suite server',
  'suite.install.database.summary.adopt': 'adopt existing',
  'suite.install.database.modeHint':
    'Provision creates a brand-new database for the app. Adopt registers an existing database as-is — the suite never creates or changes its contents. If unsure, leave it on provision.',
  'suite.install.database.modeHintLabel': 'What does the database mode mean?',

  // ── Installer — disclaimer ───────────────────────────────────────────────
  'suite.disclaimer.title': 'Important notice',
  'suite.disclaimer.shortNotice':
    'NeNe Suite helps you install and configure NeNe applications. It does not guarantee business results, legal compliance, or accounting correctness. You remain solely responsible for how installed apps are used and for obtaining professional advice when required.',
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
    'You are about to finish setup. NeNe Suite will write configuration files and provision databases. This does not certify that your organization meets any tax, accounting, or industry rule.',

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
  'suite.audit.exportCsv': 'Export CSV',
  'suite.audit.empty': 'No audit events yet.',
  'suite.audit.noMatch': 'No events match the filters.',
  'suite.audit.loadMore': 'Load more',
  'suite.audit.filter.allActors': 'All actors',
  'suite.audit.clearFilters': 'Clear',
  'suite.audit.column.action': 'Action',
  'suite.audit.column.change': 'Change',
  'suite.audit.column.actor': 'Actor',
  'suite.audit.column.entity': 'Entity',
  'suite.audit.column.time': 'Time',
  'suite.audit.events': 'events',
  'suite.audit.appendOnly': 'append-only',
  'suite.audit.searchPlaceholder': 'Search action, actor, or id…',
  'suite.audit.hint': 'Click a row to open the detail (metadata + before/after diff) on the right.',
  'suite.audit.source.all': 'All sources',
  'suite.audit.change.all': 'All',
  'suite.audit.change.created': 'Created',
  'suite.audit.change.updated': 'Updated',
  'suite.audit.change.deleted': 'Deleted',
  'suite.audit.detail.title': 'Audit event detail',
  'suite.audit.metadata': 'Metadata',
  'suite.audit.meta.actor': 'Actor',
  'suite.audit.meta.type': 'Entity type',
  'suite.audit.meta.target': 'Entity id',
  'suite.audit.meta.time': 'Time',
  'suite.audit.meta.source': 'Source',
  'suite.audit.meta.request': 'Request id',
  'suite.audit.meta.session': 'Install session',
  'suite.audit.meta.org': 'Org (orgExternalId)',
  'suite.audit.meta.suite': 'Suite id',
  'suite.audit.actor.machine': 'machine',
  'suite.audit.actor.human': 'human',
  'suite.audit.diff.title': 'Changes',
  'suite.audit.diff.unified': 'Unified',
  'suite.audit.diff.split': 'Split',
  'suite.audit.diff.before': 'Before',
  'suite.audit.diff.after': 'After',
  'suite.audit.diff.added': 'added',
  'suite.audit.diff.removed': 'removed',
  'suite.audit.diff.changed': 'changed',
  'suite.audit.diff.none': '(none)',
  'suite.audit.diff.onlyChanged': 'Changed only',
  'suite.audit.diff.unchanged': '{{count}} unchanged',
  'suite.audit.diff.noChange': 'No differences.',
  'suite.audit.diff.bannerCreate': 'Created — no before snapshot',
  'suite.audit.diff.bannerDelete': 'Deleted — no after snapshot',

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
  'suite.org.create.slugRule':
    'Use lowercase letters and numbers in hyphen-separated segments (e.g. "acme-kk", max 160 characters).',
  'suite.org.search.placeholder': 'Filter by name or slug…',
  'suite.org.search.noMatch': 'No organizations match your filter.',
  'suite.org.rename.action': 'Rename',
  'suite.org.rename.nameLabel': 'New name',
  'suite.org.rename.submitting': 'Renaming…',
  'suite.org.disable.action': 'Disable',
  'suite.org.disable.confirm': 'Disable this organization?',
  'suite.org.disable.impact':
    'Disabling is a reversible freeze, not deletion. Members can no longer sign in to this organization, and its app data stays stored untouched. You can re-enable it here at any time; the action is recorded in the audit log.',
  'suite.org.enable.action': 'Re-enable',
  'suite.org.enable.confirm': 'Re-enable this organization?',
  'suite.org.enable.impact':
    'Members can sign in again and the frozen data becomes available exactly as before. The action is recorded in the audit log.',
  'suite.org.members': 'Members',
  'suite.org.error.slugConflict': 'An organization with this slug already exists.',
  'suite.org.error.validation': 'Check the organization name and slug.',
  'suite.org.error.notFound': 'That organization no longer exists.',
  'suite.org.indicator.superadmin': 'Superadmin',
  'suite.org.indicator.activeOrg': 'Org: {{org}}',
  'suite.org.indicator.noOrg': 'No active organization',
  'suite.org.indicator.role': 'Role: {{role}}',
  'suite.org.switcher.label': 'Active organization',
  'suite.org.switcher.placeholder': 'Select organization',

  // ── Memberships (superadmin console) ─────────────────────────────────────
  'suite.member.title': 'Members',
  'suite.member.description': 'Manage who belongs to this organization and their role.',
  'suite.member.orgContext': 'Target organization',
  'suite.member.empty': 'No members yet.',
  'suite.member.column.operator': 'Operator',
  'suite.member.column.role': 'Role',
  'suite.member.column.actions': 'Actions',
  'suite.member.role.admin': 'Admin',
  'suite.member.role.member': 'Member',
  'suite.member.role.viewer': 'Viewer',
  'suite.member.grant.title': 'Add member',
  'suite.member.grant.operatorLabel': 'Operator',
  'suite.member.grant.operatorPlaceholder': 'Select an operator',
  'suite.member.grant.noOperators': 'No operators available to add.',
  'suite.member.grant.roleLabel': 'Role',
  'suite.member.grant.submit': 'Add',
  'suite.member.grant.subtitle': 'Add an operator to this organization and set their role.',
  'suite.member.grant.submitting': 'Adding…',
  'suite.member.stale': 'Removed operator ({{operatorId}})',
  'suite.member.revoke.action': 'Remove',
  'suite.member.revoke.confirm': 'Revoke this member?',
  'suite.member.error.conflict': 'This operator is already a member of the organization.',
  'suite.member.error.invariant': 'The organization must keep at least one admin.',
  'suite.member.error.validation': 'Check the operator and role.',
  'suite.member.error.notFound': 'That membership no longer exists.',
  'suite.member.lastAdmin.hint':
    "This is the organization's only admin. Add another admin before you change or remove this one.",
  'suite.member.lastAdmin.label': "Why can't I change this?",

  // ── Help center ──────────────────────────────────────────────────────────
  'suite.nav.help': 'Help',
  'suite.help.pageLink': 'How to use this page',
  'suite.help.title': 'Help & guides',
  'suite.help.subtitle': 'Learn how to use NeNe Suite, one step at a time.',
  'suite.help.searchPlaceholder': 'Search help and glossary…',
  'suite.help.tasksTitle': 'Find by what you want to do',
  'suite.help.glossaryCta': 'Open the glossary',
  'suite.help.resultsTitle': 'Search results',
  'suite.help.noResults': 'No matches. Try a different word.',
  'suite.help.infoHint': 'Show explanation',
  'suite.help.bodyNotice':
    'Help articles are currently available in Japanese only — an English version is in preparation. Navigation and screen labels stay in your language.',
  'suite.help.tocLabel': 'Help contents',
  'suite.help.homeLink': 'Help home',
  'suite.help.termsGroup': 'Terms',
  'suite.help.related': 'Related',
  'suite.help.notFound.title': 'Page not found',
  'suite.help.notFound.body': 'This help page does not exist or may have moved.',
  'suite.help.notFound.back': 'Back to help home',
  'suite.help.task.install': 'Install an app',
  'suite.help.task.roles': 'Understand roles (permissions)',
  'suite.help.task.safety': 'Learn to operate safely',
  'suite.help.task.faq': 'When you are stuck (FAQ)',
  'suite.help.group.start': 'Getting started',
  'suite.help.group.screens': 'How to use each screen',
  'suite.help.group.tutorial': 'Tutorials',
  'suite.help.glossary.title': 'Glossary',
  'suite.help.glossary.subtitle': 'Tricky words, explained in plain language.',
  'suite.help.glossary.searchPlaceholder': 'Search terms…',
  'suite.help.glossary.searchLabel': 'Search terms',
  'suite.help.glossary.noResults': 'No matching terms.',
  'suite.help.category.basics': 'Basics',
  'suite.help.category.tenancy': 'Organizations & members',
  'suite.help.category.install': 'Installation',
  'suite.help.category.database': 'Databases',
  'suite.help.category.federation': 'Federation',
  'suite.help.category.origin': 'Updates & announcements',
  'suite.help.category.operation': 'Operations & safety',
  'suite.help.callout.danger': 'Important: ',
  'suite.help.callout.warning': 'Caution: ',
}

// `MessageCatalog`/`MessageKey` authority now lives in `ja.ts` (規約 04 I18N-8).
// Re-exported here so existing `./en` type imports keep resolving unchanged.
export type { MessageCatalog, MessageKey }
