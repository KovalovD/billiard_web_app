<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
>
    @foreach($tags as $tag)
        @include('sitemap::' . $tag->getType())
    @endforeach
</urlset>
