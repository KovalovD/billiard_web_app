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
    author?: string;
    publisher?: string;
    alternateLanguages?: Array<{ lang: string; url: string }>;
    jsonLd?: Record<string, any>;
    additionalMeta?: Array<{ name?: string; property?: string; content: string }>;
}

export function useSeo() {
    const setSeoMeta = (meta: SeoMeta) => {
        // Set document title
        if (meta.title) {
            document.title = `${meta.title} | WinnerBreak - Professional Billiard League Platform`;
        }

        // Remove existing meta tags
        const existingMetas = document.querySelectorAll('meta[name^="description"], meta[name^="keywords"], meta[property^="og:"], meta[name^="twitter:"], link[rel="canonical"], link[rel="alternate"], meta[name="author"], meta[name="publisher"], meta[name="robots"]');
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

        // Add author
        if (meta.author) {
            const authorMeta = document.createElement('meta');
            authorMeta.name = 'author';
            authorMeta.content = meta.author;
            document.head.appendChild(authorMeta);
        }

        // Add publisher
        if (meta.publisher) {
            const publisherMeta = document.createElement('meta');
            publisherMeta.name = 'publisher';
            publisherMeta.content = meta.publisher;
            document.head.appendChild(publisherMeta);
        }

        // Add Open Graph tags
        const ogTags = {
            'og:title': meta.ogTitle || meta.title,
            'og:description': meta.ogDescription || meta.description,
            'og:image': meta.ogImage || '/images/og-default.jpg',
            'og:type': meta.ogType || 'website',
            'og:url': meta.canonicalUrl || window.location.href,
            'og:site_name': 'WinnerBreak',
            'og:locale': document.documentElement.lang || 'en_US',
            'og:locale:alternate': document.documentElement.lang === 'en' ? 'uk_UA' : 'en_US'
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
            'twitter:image': meta.ogImage || '/images/og-default.jpg',
            'twitter:site': '@winnerbreak',
            'twitter:creator': '@winnerbreak'
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

        // Add alternate language links
        if (meta.alternateLanguages) {
            meta.alternateLanguages.forEach(alt => {
                const alternateLink = document.createElement('link');
                alternateLink.rel = 'alternate';
                alternateLink.hreflang = alt.lang;
                alternateLink.href = alt.url;
                document.head.appendChild(alternateLink);
            });

            // Add x-default
            const defaultLink = document.createElement('link');
            defaultLink.rel = 'alternate';
            defaultLink.hreflang = 'x-default';
            defaultLink.href = window.location.origin;
            document.head.appendChild(defaultLink);
        }

        // Add robots meta
        if (meta.robots) {
            const robotsMeta = document.createElement('meta');
            robotsMeta.name = 'robots';
            robotsMeta.content = meta.robots;
            document.head.appendChild(robotsMeta);
        }

        // Add additional custom meta tags
        if (meta.additionalMeta) {
            meta.additionalMeta.forEach(tag => {
                const customMeta = document.createElement('meta');
                if (tag.name) {
                    customMeta.name = tag.name;
                } else if (tag.property) {
                    customMeta.setAttribute('property', tag.property);
                }
                customMeta.content = tag.content;
                document.head.appendChild(customMeta);
            });
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
            "alternateName": "Winner Break",
            "url": window.location.origin,
            "logo": `${window.location.origin}/images/logo.png`,
            "description": "Professional billiard league management platform for competitive players worldwide",
            "foundingDate": "2025",
            "foundingLocation": {
                "@type": "Place",
                "address": {
                    "@type": "PostalAddress",
                    "addressLocality": "Lviv",
                    "addressCountry": "UA"
                }
            },
            "sameAs": [
                "https://facebook.com/winnerbreak",
                "https://twitter.com/winnerbreak",
                "https://instagram.com/winnerbreak",
                "https://youtube.com/winnerbreak"
            ],
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+380-98-443-8205",
                "contactType": "customer service",
                "availableLanguage": ["English", "Ukrainian"]
            }
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
            "eventStatus": "https://schema.org/EventScheduled",
            "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
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
            "sport": "Billiards",
            "competitor": event.competitors || []
        };
    };

    const generatePersonJsonLd = (person: any) => {
        return {
            "@context": "https://schema.org",
            "@type": "Person",
            "name": person.name,
            "givenName": person.firstname,
            "familyName": person.lastname,
            "description": person.description || `Professional billiard player`,
            "image": person.image,
            "url": person.url,
            "sameAs": person.socialProfiles || [],
            "affiliation": {
                "@type": "Organization",
                "name": person.club || "Independent"
            }
        };
    };

    const generateFAQJsonLd = (faqs: Array<{ question: string; answer: string }>) => {
        return {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": faqs.map(faq => ({
                "@type": "Question",
                "name": faq.question,
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": faq.answer
                }
            }))
        };
    };

    const generateGameJsonLd = (game: any) => {
        return {
            "@context": "https://schema.org",
            "@type": "Game",
            "name": game.name,
            "description": game.description,
            "gameLocation": window.location.origin,
            "numberOfPlayers": {
                "@type": "QuantitativeValue",
                "minValue": game.minPlayers || 2,
                "maxValue": game.maxPlayers || 100
            }
        };
    };

    const getAlternateLanguageUrls = (currentPath: string) => {
        const baseUrl = window.location.origin;
        const currentLang = document.documentElement.lang || 'en';

        return [
            {lang: 'en', url: `${baseUrl}/en${currentPath}`},
            {lang: 'uk', url: `${baseUrl}/uk${currentPath}`},
            {lang: 'ru', url: `${baseUrl}/ru${currentPath}`}
        ].filter(alt => alt.lang !== currentLang);
    };

    return {
        setSeoMeta,
        generateBreadcrumbJsonLd,
        generateOrganizationJsonLd,
        generateSportsEventJsonLd,
        generatePersonJsonLd,
        generateFAQJsonLd,
        generateGameJsonLd,
        getAlternateLanguageUrls
    };
}
