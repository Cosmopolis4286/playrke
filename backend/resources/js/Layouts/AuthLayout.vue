<template>
    <div
        class="d-flex flex-column min-vh-100 hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
        <div id="app-wrapper" class="flex-grow-1">
            <main class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
// Layout stub — the real navbar/sidebar are rendered by Blade templates.
// This file is kept for compatibility but not actively used by any page.
</script>
