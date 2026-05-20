@extends('layouts.desktop')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div class="content-grid" style="grid-template-columns: 1fr 340px;">
    <!-- CENTER COLUMN: FEED & UPDATES -->
    <div class="feed-column">
        
        <!-- Create Post Widget (Extracted Component) -->
        <x-compose-post :currentUser="$currentUser" />

        <!-- Dynamic Posts -->
        <div id="posts-container">
            @include('desktop.partials.post-list')
        </div>
        
        <!-- Loading Indicator -->
        <div id="loading-indicator" style="display: none; padding: 20px;">
            <div class="post-card" style="animation: pulse 1.5s infinite; border-color: transparent; box-shadow: none;">
                <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--bg-main);"></div>
                    <div style="flex: 1; padding-top: 4px;">
                        <div style="width: 150px; height: 16px; background: var(--bg-main); border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 100px; height: 12px; background: var(--bg-main); border-radius: 4px;"></div>
                    </div>
                </div>
                <div style="width: 100%; height: 14px; background: var(--bg-main); border-radius: 4px; margin-bottom: 8px;"></div>
                <div style="width: 80%; height: 14px; background: var(--bg-main); border-radius: 4px; margin-bottom: 16px;"></div>
                <div style="width: 100%; height: 200px; background: var(--bg-main); border-radius: 12px;"></div>
            </div>
            <style>
                @keyframes pulse {
                    0% { opacity: 1; }
                    50% { opacity: 0.5; }
                    100% { opacity: 1; }
                }
            </style>
        </div>

    </div>

    <!-- RIGHT COLUMN: WIDGETS (Extracted Component) -->
    <x-feed-widgets />

</div>

<!-- FEED SCRIPTS (Extracted Component) -->
<x-feed-scripts :posts="$posts" />

@endsection
