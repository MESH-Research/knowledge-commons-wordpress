<div class = "">
    <ul class="{{ title }} widget-post-list">
        <h4 class="label">{{ $title }}</h4>
        @foreach ($data as $item)
        <li><a href="{{ $item['link'] }}">{{ $item['title'] }}</a></li>
        @endforeach
    </ul>
</div>
