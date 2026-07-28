export type UseInitialsReturn = {
    getInitials: (fullName?: string) => string;
};

export function getInitials(fullName?: string): string {
    if (!fullName) {
        return '';
    }

    const name = fullName.trim();
    if (name.length === 0) {
        return '';
    }

    return name.charAt(0).toUpperCase();
}

export function useInitials(): UseInitialsReturn {
    return { getInitials };
}
