import {type ClassValue, clsx} from 'clsx';
import {twMerge} from 'tailwind-merge';

/** Merges Tailwind classes without conflicts. */
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}
