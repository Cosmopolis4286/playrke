<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { termsContent } from '@js/modules/shared/utils/info/termsContent'

defineProps<{
    title: string
}>()

const formatParagraph = (text: string) => {
    return text.replace(
        /^(\d+\.\d+)/,
        '<strong>$1</strong>'
    )
}
</script>

<template>

    <Head :title="title" />

    <div class="info-wrapper">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="mb-3">{{ termsContent.title }}</h2>

                        <!-- Itera sobre las secciones -->
                        <div v-for="(section, index) in termsContent.sections" :key="index">
                            <h4>{{ section.heading }}</h4>
                            <p v-for="(paragraph, index) in section.paragraphs" :key="index" class="mb-3"
                                v-html="formatParagraph(paragraph)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.info-wrapper {
    max-width: 100%;
    overflow-x: hidden;
}
</style>
