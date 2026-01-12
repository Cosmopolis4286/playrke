<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { privacyContent } from '@js/modules/shared/utils/info/privacyContent'

defineProps<{
    title: string
}>()
</script>

<template>

    <Head :title="title" />

    <div class="privacy-wrapper">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="card mb-3">
                    <div class="card-body privacy-content">
                        <h2 class="mb-4">{{ privacyContent.title }}</h2>

                        <p v-for="(intro, index) in privacyContent.intro" :key="`intro-${index}`" class="mb-3">
                            {{ intro }}
                        </p>

                        <p class="mb-4"><small class="text-muted">{{ privacyContent.effectiveDate }}</small></p>

                        <section v-for="(section, sectionIndex) in privacyContent.sections" :key="`section-${sectionIndex}`" class="mb-4">
                            <h4 class="mb-3">{{ section.title }}</h4>

                            <template v-for="(item, itemIndex) in section.content" :key="`item-${sectionIndex}-${itemIndex}`">
                                <p v-if="item.type === 'paragraph'" class="mb-3">
                                    {{ item.text }}
                                </p>

                                <ul v-else-if="item.type === 'list'" class="mb-3">
                                    <li v-for="(listItem, listIndex) in item.items" :key="`list-${sectionIndex}-${itemIndex}-${listIndex}`" v-html="listItem.text"></li>
                                </ul>
                            </template>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.privacy-wrapper {
    max-width: 100%;
    overflow-x: hidden;
}

.privacy-wrapper .row {
    margin-left: 0;
    margin-right: 0;
}

.privacy-wrapper .card {
    max-width: 100%;
    overflow: hidden;
}

.privacy-content {
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    overflow-x: hidden;
}

.privacy-content h2,
.privacy-content h4,
.privacy-content p,
.privacy-content li {
    max-width: 100%;
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
}

.privacy-content ul {
    padding-left: 1.5rem;
}
</style>
