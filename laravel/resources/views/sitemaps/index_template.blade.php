<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
  <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($items as $item)
    <sitemap>
        <loc>{{"https://${subdomain}bankiroff.ru/{$item["path"]}.xml"}}</loc>
        <lastmod>{{$item["lastmod"]}}</lastmod>
    </sitemap>
    @endforeach
  </sitemapindex>