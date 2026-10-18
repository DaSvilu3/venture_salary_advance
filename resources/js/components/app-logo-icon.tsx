import { useId } from 'react';
import type { SVGAttributes } from 'react';

/**
 * The Zarf (ظرف) mark: a coin leaving the pay envelope a little early, the coin
 * echoing the dot of the letter ظ. The envelope carries the dawn gradient (sea
 * to rose) and the coin uses the brand violet, so place it on a light or dark
 * tile rather than on violet.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    const id = useId().replace(/[^a-zA-Z0-9_-]/g, '');
    const gradientId = `zarf-gradient-${id}`;
    const gapId = `zarf-gap-${id}`;

    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient
                    id={gradientId}
                    x1="6"
                    y1="19"
                    x2="42"
                    y2="42"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="#10bcd2" />
                    <stop offset="0.55" stopColor="#8b74ff" />
                    <stop offset="1" stopColor="#efa7c0" />
                </linearGradient>
                <mask id={gapId}>
                    <rect width="48" height="48" fill="#fff" />
                    <circle cx="24" cy="14" r="10" fill="#000" />
                </mask>
            </defs>
            <rect
                x="6"
                y="19"
                width="36"
                height="23"
                rx="6"
                fill={`url(#${gradientId})`}
                mask={`url(#${gapId})`}
            />
            <path
                d="m9 24 15 9.5L39 24"
                fill="none"
                stroke="#fff"
                strokeWidth="3.25"
                strokeLinecap="round"
                strokeLinejoin="round"
                opacity="0.9"
            />
            <path
                fill="var(--brand, #5a37f5)"
                fillRule="evenodd"
                d="M24 6.5a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm-1.6 4.6a1.6 1.6 0 0 1 3.2 0v5.8a1.6 1.6 0 0 1-3.2 0Z"
            />
        </svg>
    );
}
