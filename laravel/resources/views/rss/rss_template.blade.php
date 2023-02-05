<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<rss xmlns:yandex="http://news.yandex.ru" xmlns:rambler="http://news.rambler.ru" xmlns:media="http://search.yahoo.com/mrss/" version="2.0">
    <channel>
        <title>Bankiroff.ru - Российский Финансовый поисковый сервис</title>
        <link>https://bankiroff.ru/</link>
        <description>Быстрый поиск данных о финансовых продуктах, банках, отделениях, банкоматах Москвы и Московской области.</description>
        <lastBuildDate>{{$lastmod}}</lastBuildDate>
        <language>ru</language>
        <copyright>© 2021 «Bankiroff.ru» Все права защищены.</copyright>
        <managingEditor>mk@bankiroff.ru (Кухарёнок Марина Васильевна)</managingEditor>
        <webMaster>klim@bankiroff.ru (Клим Еременко)</webMaster>
        @foreach($items as $item)
        <item>
            <title>{{$item["title"]}}</title>
            <author>{{$item["author"]}}</author>
            <yandex:genre>{{$item["type"]}}</yandex:genre>
            <link>{{"https://${subdomain}bankiroff.ru{$item["url"]}"}}</link>
            <pubDate>{{$item["published_at_RFC822"]}}</pubDate>
            @if($item["image"])
            <enclosure url="https://bankiroff.ru/images/news/{{$item['image']}}" type="image/{{$item['image_extension']}}" />
            @endif
            <yandex:full-text>{{$item["text_no_html"]}}</yandex:full-text>
            <rambler:full-text>{{$item["text_no_html"]}}</rambler:full-text>
        </item>
        @endforeach
    </channel>
</rss>