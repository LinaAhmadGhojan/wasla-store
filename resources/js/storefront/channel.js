/**
 * Resolve post-login / post-register landing based on preferred_channel.
 * Explicit ?redirect= always wins when it is not just "/".
 */
export function homeForChannel(user, redirectTo = '/') {
  const explicit = redirectTo && redirectTo !== '/' ? redirectTo : null;
  if (explicit) return explicit;

  const channel = user?.preferred_channel || 'both';
  if (channel === 'express') return '/express';
  if (channel === 'store') return '/shop';

  const last = typeof localStorage !== 'undefined'
    ? localStorage.getItem('wasla_last_channel')
    : null;
  if (last === 'express') return '/express';
  if (last === 'store') return '/shop';

  return '/shop';
}

export function rememberChannel(channel) {
  if (typeof localStorage === 'undefined') return;
  if (channel === 'express' || channel === 'store') {
    localStorage.setItem('wasla_last_channel', channel);
  }
}
