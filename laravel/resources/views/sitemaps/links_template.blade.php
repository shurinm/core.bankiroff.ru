<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($items as $item)
    <url>
        <loc>{{"https://${subdomain}bankiroff.ru{$item["url"]}"}}</loc>
        <lastmod>{{$item["lastmod"]}}</lastmod>
        <changefreq>{{$item["changefreq"]}}</changefreq>
        <priority>{{$item["priority"]}}</priority>
    </url>
@endforeach
</urlset>