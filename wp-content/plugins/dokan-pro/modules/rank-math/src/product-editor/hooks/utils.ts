// Runtime globals Rank Math sets on window (rankMath, rankMathEditor, wp, jQuery).
export const w = window as any;

// Rank Math's metabox mounts ~1s after load; poll every 250ms and bail after ~10s.
const POLL_INTERVAL_MS = 250;
const POLL_MAX_TRIES = 40;

// Poll until `check` returns truthy, run `onReady` once, then stop; returns a canceller.
export const pollUntil = (
    check: () => any,
    onReady: ( value: any ) => void,
    maxTries = POLL_MAX_TRIES,
    delay = POLL_INTERVAL_MS
): ( () => void ) => {
    const value = check();
    if ( value ) {
        onReady( value );
        return () => {};
    }
    let tries = 0;
    const timer = setInterval( () => {
        const ready = check();
        if ( ready ) {
            clearInterval( timer );
            onReady( ready );
        } else if ( ++tries >= maxTries ) {
            clearInterval( timer );
        }
    }, delay );
    return () => clearInterval( timer );
};

// Rank Math's store dispatch once it exposes the re-point actions; null until ready.
export const getRepointDispatch = (): any => {
    const wpData = w.wp?.data;
    if (
        ! wpData?.select ||
        ! wpData?.dispatch ||
        ! wpData.select( 'rank-math' )
    ) {
        return null;
    }
    const dispatch = wpData.dispatch( 'rank-math' );
    return dispatch?.resetStore && dispatch?.updatePostID ? dispatch : null;
};

// True once Rank Math has finished loading its metabox data.
export const isRankMathLoaded = (): boolean =>
    !! w.wp?.data?.select?.( 'rank-math' )?.isLoaded?.();
