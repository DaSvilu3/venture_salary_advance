import type { SVGAttributes } from 'react';

/**
 * A coin rising from a calendar line: money a little before payday.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 4a11 11 0 1 1 0 22 11 11 0 0 1 0-22Zm0 4.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13Z" />
            <path d="M18 11h4v7h-4z" />
            <path d="M4 31.5h32V36H4z" />
            <path d="M8 29h4v2.5H8zM28 29h4v2.5h-4z" />
        </svg>
    );
}
