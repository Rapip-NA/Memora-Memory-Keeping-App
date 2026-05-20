@foreach($posts as $post)
    <x-post-card :post="$post" :currentUser="isset($currentUser) ? $currentUser : null" />
@endforeach
