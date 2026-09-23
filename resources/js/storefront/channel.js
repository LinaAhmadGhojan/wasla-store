/**
 * Resolve post-login / post-register landing based on preferred_channel.
 * Explicit ?redirect= always wins when it is not just "/".
 */
export function homeForChannel(user, redirectTo = '/') {
  const explicit = redirectTo && redirectTo !== '/' ? redirectTo : null;
  if (explicit) return explicit;

  const channel = user?.preferred_channel || 'both';
  if (channel === 'express') return '/express';
  if (channel === 'store') return '/';

  const last = getLastChannel();
  if (last === 'express') return '/express';
  return '/';
}

export function getLastChannel() {
  if (typeof localStorage === 'undefined') return 'store';
  const last = localStorage.getItem('wasla_last_channel');
  return last === 'express' ? 'express' : 'store';
}

export function rememberChannel(channel) {
  if (typeof localStorage === 'undefined') return;
  if (channel === 'express' || channel === 'store') {
    localStorage.setItem('wasla_last_channel', channel);
    window.dispatchEvent(new CustomEvent('wasla-channel-changed', { detail: { channel } }));
  }
}

/** Store catalog vs طلباتي — sticky until user switches via ملابس / أكل or channel cards. */
export function resolveChannelMode(pathname = window.location.pathname) {
  const path = pathname || '/';

  if (path.startsWith('/express')) {
    return 'express';
  }

  if (
    path === '/'
    || path.startsWith('/shop')
    || path.startsWith('/product/')
    || path.startsWith('/browse')
    || path.startsWith('/buy-from-anywhere')
    || path.startsWith('/compare')
    || path.startsWith('/stores/')
  ) {
    return 'store';
  }

  return getLastChannel();
}
