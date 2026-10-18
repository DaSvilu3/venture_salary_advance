import { useId } from 'react';
import type { SVGAttributes } from 'react';

/**
 * The Zarf (ظرف) mark: a coin leaving the pay envelope a little early. The coin
 * above the envelope also echoes the dot of the letter ظ. Drawn with real
 * cut-outs so it works in one colour on any background.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    const gapId = `zarf-gap-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`;

    return (
        <svg
            fill="currentColor"
            {...props}
            viewBox="0 0 48 48"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <mask id={gapId}>
                    <rect width="48" height="48" fill="#fff" />
                    <circle cx="24" cy="15" r="10.5" fill="#000" />
                </mask>
            </defs>
            <path
                fillRule="evenodd"
                mask={`url(#${gapId})`}
                d="M10 19h28a5 5 0 0 1 5 5v14a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5V24a5 5 0 0 1 5-5Zm-1 4.6 15 9.6 15-9.6v5L24 38.2 9 28.6Z"
            />
            <path
                fillRule="evenodd"
                d="M24 7a8 8 0 1 1 0 16 8 8 0 0 1 0-16Zm-2.25 5.75a2.25 2.25 0 0 1 4.5 0v4.5a2.25 2.25 0 0 1-4.5 0Z"
            />
        </svg>
    );
}
