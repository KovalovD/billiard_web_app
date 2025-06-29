interface SeoMeta {
    title?: string;
    description?: string;
    keywords?: string[];
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogType?: string;
    twitterCard?: string;
    canonicalUrl?: string;
    robots?: string;
    jsonLd?: Record<string, any>;
}

export function useSeo() {
    const setSeoMeta = (meta: SeoMeta) => {
        // Set document title
        if (meta.title) {
            document.title = `${meta.title} | WinnerBreak - Professional Billiard League Platform`;
        }

        // Remove existing meta tags
        const existingMetas = document.querySelectorAll('meta[name^="description"], meta[name^="keywords"], meta[property^="og:"], meta[name^="twitter:"], link[rel="canonical"]');
        existingMetas.forEach(el => el.remove());

        // Add meta description
        if (meta.description) {
            const descMeta = document.createElement('meta');
            descMeta.name = 'description';
            descMeta.content = meta.description;
            document.head.appendChild(descMeta);
        }

        // Add keywords
        if (meta.keywords && meta.keywords.length > 0) {
            const keywordsMeta = document.createElement('meta');
            keywordsMeta.name = 'keywords';
            keywordsMeta.content = meta.keywords.join(', ');
            document.head.appendChild(keywordsMeta);
        }

        // Add Open Graph tags
        const ogTags = {
            'og:title': meta.ogTitle || meta.title,
            'og:description': meta.ogDescription || meta.description,
            'og:image': meta.ogImage || '/images/og-default.jpg',
            'og:type': meta.ogType || 'website',
            'og:url': meta.canonicalUrl || window.location.href,
            'og:site_name': 'WinnerBreak'
        };

        Object.entries(ogTags).forEach(([property, content]) => {
            if (content) {
                const ogMeta = document.createElement('meta');
                ogMeta.setAttribute('property', property);
                ogMeta.content = content as string;
                document.head.appendChild(ogMeta);
            }
        });

        // Add Twitter Card tags
        const twitterTags = {
            'twitter:card': meta.twitterCard || 'summary_large_image',
            'twitter:title': meta.ogTitle || meta.title,
            'twitter:description': meta.ogDescription || meta.description,
            'twitter:image': meta.ogImage || '/images/og-default.jpg'
        };

        Object.entries(twitterTags).forEach(([name, content]) => {
            if (content) {
                const twitterMeta = document.createElement('meta');
                twitterMeta.name = name;
                twitterMeta.content = content as string;
                document.head.appendChild(twitterMeta);
            }
        });

        // Add canonical URL
        if (meta.canonicalUrl) {
            const canonicalLink = document.createElement('link');
            canonicalLink.rel = 'canonical';
            canonicalLink.href = meta.canonicalUrl;
            document.head.appendChild(canonicalLink);
        }

        // Add robots meta
        if (meta.robots) {
            const robotsMeta = document.createElement('meta');
            robotsMeta.name = 'robots';
            robotsMeta.content = meta.robots;
            document.head.appendChild(robotsMeta);
        }

        // Add JSON-LD structured data
        if (meta.jsonLd) {
            // Remove existing JSON-LD
            const existingJsonLd = document.querySelector('script[type="application/ld+json"]');
            if (existingJsonLd) existingJsonLd.remove();

            const jsonLdScript = document.createElement('script');
            jsonLdScript.type = 'application/ld+json';
            jsonLdScript.textContent = JSON.stringify(meta.jsonLd);
            document.head.appendChild(jsonLdScript);
        }
    };

    const generateBreadcrumbJsonLd = (breadcrumbs: Array<{ name: string, url: string }>) => {
        return {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": breadcrumbs.map((item, index) => ({
                "@type": "ListItem",
                "position": index + 1,
                "name": item.name,
                "item": item.url
            }))
        };
    };

    const generateOrganizationJsonLd = () => {
        return {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "WinnerBreak",
            "url": window.location.origin,
            "logo": `${window.location.origin}/images/logo.png`,
            "description": "Professional billiard league management platform for competitive players",
            "sameAs": [
                "https://facebook.com/winnerbreak",
                "https://twitter.com/winnerbreak",
                "https://instagram.com/winnerbreak"
            ]
        };
    };

    const generateSportsEventJsonLd = (event: any) => {
        return {
            "@context": "https://schema.org",
            "@type": "SportsEvent",
            "name": event.name,
            "description": event.description,
            "startDate": event.startDate,
            "endDate": event.endDate,
            "location": event.location ? {
                "@type": "Place",
                "name": event.location.name,
                "address": {
                    "@type": "PostalAddress",
                    "addressLocality": event.location.city,
                    "addressCountry": event.location.country
                }
            } : undefined,
            "organizer": {
                "@type": "Organization",
                "name": event.organizer || "WinnerBreak"
            },
            "sport": "Billiards"
        };
    };

    return {
        setSeoMeta,
        generateBreadcrumbJsonLd,
        generateOrganizationJsonLd,
        generateSportsEventJsonLd
    };
}
