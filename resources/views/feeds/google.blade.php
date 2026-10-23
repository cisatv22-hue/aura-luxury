<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>AURA</title>
        <link>{{ url('/') }}</link>
        <description>Catálogo de productos AURA</description>
        @foreach($items as $item)
            <item>
                <g:id>{{ $item['id'] }}</g:id>
                <g:title>{{ $item['title'] }}</g:title>
                <g:description>{{ $item['description'] }}</g:description>
                <g:link>{{ $item['link'] }}</g:link>
                @if($item['image_link'] !== '')<g:image_link>{{ $item['image_link'] }}</g:image_link>@endif
                <g:availability>{{ $item['availability'] }}</g:availability>
                <g:condition>new</g:condition>
                <g:price>{{ $item['price'] }}</g:price>
                <g:brand>{{ $item['brand'] }}</g:brand>
            </item>
        @endforeach
    </channel>
</rss>
