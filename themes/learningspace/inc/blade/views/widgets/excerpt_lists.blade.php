<div class = "">
    <ul class="{{ $title }} widget-post-list--excerpt">
        <h4 class="label">{{ $title }}</h4>
        @foreach ($data as $item)
            <li><h2><a href="{{ $item['link'] }}">{{ $item['title'] }}</a></h2><div class="excerpt_list_item">{{ $item['excerpt'] }}</div></li>
        @endforeach
    </ul>
</div>
