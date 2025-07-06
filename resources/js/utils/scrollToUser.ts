// resources/js/utils/scrollToUser.ts

export const scrollToUserElement = async (userId: number | undefined): Promise<boolean> => {
    if (!userId) {
        console.error('No user ID provided for scrolling');
        return false;
    }

    // Wait longer for DOM updates, especially for desktop table rendering
    await new Promise(resolve => setTimeout(resolve, 500));

    // Query for all elements with the user ID
    const selector = `[data-user-id="${userId}"]`;
    const allElements = document.querySelectorAll(selector);

    if (allElements.length === 0) {
        console.error('No elements found with user ID:', userId);
        return false;
    }

    let targetElement: Element | null = null;

    // Helper function to check if element is visible
    function isElementVisible(el: Element): boolean {
        const rect = el.getBoundingClientRect();
        const style = window.getComputedStyle(el);
        return rect.width > 0 &&
            rect.height > 0 &&
            style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            style.opacity !== '0';
    }

    // Find the appropriate VISIBLE container element
    for (const element of allElements) {
        // Skip if element is not visible
        if (!isElementVisible(element)) {
            continue;
        }

        // Check if it's a table row (desktop view)
        if (element.tagName === 'TR') {
            targetElement = element;
            break;
        }

        // Check if it's a mobile card
        if (element.classList.contains('mobile-card')) {
            targetElement = element;
            break;
        }
    }

    if (!targetElement) {
        console.error('Could not find visible user element for ID:', userId);
        return false;
    }


    // Get the container that might have scroll
    const scrollContainer = targetElement.closest('.overflow-x-auto') ||
        targetElement.closest('[data-rating-table]') ||
        document.documentElement;

    // Scroll the element into view
    targetElement.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest'
    });

    // If there's a horizontal scroll container, ensure the element is visible horizontally
    if (scrollContainer && scrollContainer !== document.documentElement) {
        const containerRect = scrollContainer.getBoundingClientRect();
        const elementRect = targetElement.getBoundingClientRect();

        if (elementRect.left < containerRect.left || elementRect.right > containerRect.right) {
            scrollContainer.scrollLeft = elementRect.left - containerRect.left - (containerRect.width / 2) + (elementRect.width / 2);
        }
    }

    // Add highlight animation
    targetElement.classList.add(
        'animate-pulse',
        'bg-indigo-100',
        'dark:bg-indigo-900/30',
        'transition-all',
        'duration-300'
    );

    // Remove highlight after animation
    setTimeout(() => {
        targetElement?.classList.remove(
            'animate-pulse',
            'bg-indigo-100',
            'dark:bg-indigo-900/30'
        );
    }, 2000);

    return true;
};
