/** Whether `href` is the page being shown (or, with `prefix`, a page under it). */
export function isCurrentUrl(href: string, currentUrl: string, prefix = false): boolean {
    const path = (url: string) => new URL(url, 'http://localhost').pathname.replace(/\/+$/, '') || '/';
    const target = path(href);
    const current = path(currentUrl);

    if (target === current) return true;

    return prefix && target !== '/' && current.startsWith(`${target}/`);
}
