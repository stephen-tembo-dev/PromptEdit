import { echoIsConfigured, useEcho } from '@laravel/echo-vue';
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Live status over the user's private channel.
 *
 * A message only says "one of your pictures changed", so we reload the given
 * props (and the balance, which moves when a picture settles) from the server
 * instead of trusting the message. Messages sent while the connection was down
 * are lost, so we also catch up whenever the channel subscribes (Pusher does
 * that again after every reconnect) and when the tab comes back into view.
 * Catching up is skipped when everything on the page has already finished.
 */
export function useLiveGenerations(hasUnfinished: () => boolean, only: string[]): void {
    const props = [...only, 'balance'];
    const refresh = () => router.reload({ only: props });
    const catchUp = () => {
        if (hasUnfinished()) {
            refresh();
        }
    };

    const onVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            catchUp();
        }
    };

    onMounted(() => document.addEventListener('visibilitychange', onVisibilityChange));
    onBeforeUnmount(() => document.removeEventListener('visibilitychange', onVisibilityChange));

    const userId = usePage().props.auth.user?.id;

    if (userId === undefined || !echoIsConfigured()) {
        return;
    }

    const { channel } = useEcho(`users.${userId}`, '.generation.updated', refresh);

    channel().subscribed(catchUp);
}
