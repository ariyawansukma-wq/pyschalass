import { createMongoAbility } from '@casl/ability';

/**
 * Build CASL rules for the given user.
 * Mirrors the Laravel Gates defined in AppServiceProvider.
 *
 * @param {Object|null} user  - auth.user from Inertia shared props
 * @returns {Array}  Array of ability rules
 */
export function buildRules(user) {
  const rules = [];

  if (!user) return rules;

  if (user.role === 'admin') {
    // Admin has unrestricted access — mirrors Gate::before() in Laravel
    rules.push({ action: 'manage', subject: 'all' });
  } else if (user.role === 'officer') {
    // Officer: dashboard, athletes, own sport branches, own benchmarks, folders (read-only), comparison
    rules.push({ action: 'read',   subject: 'Dashboard' });
    rules.push({ action: 'manage', subject: 'Athlete' });
    rules.push({ action: 'manage', subject: 'SportBranch' });
    rules.push({ action: 'manage', subject: 'Benchmark' });
    rules.push({ action: 'read',   subject: 'Folder' });
    rules.push({ action: 'read',   subject: 'Comparison' });
    rules.push({ action: 'read',   subject: 'Export' });
    rules.push({ action: 'manage', subject: 'CameraAssessment' });
  } else if (user.role === 'kader') {
    // Kader: dashboard, screening anak, riwayat skrining, perpustakaan (baca)
    rules.push({ action: 'read',   subject: 'Dashboard' });
    rules.push({ action: 'manage', subject: 'Screening' });
    rules.push({ action: 'read',   subject: 'Library' });
  }

  return rules;
}

/**
 * Create a reactive CASL ability instance for the given user.
 * Use this once in app.js; call ability.update(buildRules(user)) on navigation.
 *
 * @param {Object|null} user
 * @returns {import('@casl/ability').MongoAbility}
 */
export function defineAbilityFor(user) {
  return createMongoAbility(buildRules(user));
}
