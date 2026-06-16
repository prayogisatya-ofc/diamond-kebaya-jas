<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    image: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'website',
    },
    canonical: {
        type: String,
        default: '',
    },
    structuredData: {
        type: [Object, Array],
        default: () => [],
    },
})

const page = usePage()

const siteName = computed(() => page.props.catalogStore?.name || 'Diamond Kebaya & Jas')
const currentUrl = computed(() => page.props.ziggy?.location || '')
const defaultImage = computed(() => page.props.catalogStore?.logo_url || '/og-image.jpg')

const fullTitle = computed(() => {
    if (props.title.includes(siteName.value)) {
        return props.title
    }

    return `${props.title} | ${siteName.value}`
})

const canonicalUrl = computed(() => absolutize(props.canonical || currentUrl.value))
const imageUrl = computed(() => absolutize(props.image || defaultImage.value))
const structuredDataItems = computed(() => {
    const items = Array.isArray(props.structuredData)
        ? props.structuredData
        : (props.structuredData ? [props.structuredData] : [])

    return items
        .filter(Boolean)
        .map((item) => JSON.stringify(item))
})

function absolutize(value) {
    if (!value) {
        return ''
    }

    try {
        return new URL(value, currentUrl.value || window.location.origin).toString()
    } catch {
        return value
    }
}
</script>

<template>
    <Head :title="fullTitle">
        <meta head-key="meta-description" name="description" :content="description">
        <meta head-key="meta-robots" name="robots" content="index,follow">
        <meta head-key="meta-googlebot" name="googlebot" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
        <meta head-key="meta-og-type" property="og:type" :content="type">
        <meta head-key="meta-og-title" property="og:title" :content="fullTitle">
        <meta head-key="meta-og-description" property="og:description" :content="description">
        <meta head-key="meta-og-url" property="og:url" :content="canonicalUrl">
        <meta head-key="meta-og-site-name" property="og:site_name" :content="siteName">
        <meta head-key="meta-og-locale" property="og:locale" content="id_ID">
        <meta head-key="meta-og-image" property="og:image" :content="imageUrl">
        <meta head-key="meta-twitter-card" name="twitter:card" content="summary_large_image">
        <meta head-key="meta-twitter-title" name="twitter:title" :content="fullTitle">
        <meta head-key="meta-twitter-description" name="twitter:description" :content="description">
        <meta head-key="meta-twitter-image" name="twitter:image" :content="imageUrl">
        <link head-key="link-canonical" rel="canonical" :href="canonicalUrl">
        <component
            :is="'script'"
            v-for="(item, index) in structuredDataItems"
            :key="`schema-${index}`"
            :head-key="`schema-${index}`"
            type="application/ld+json"
            v-html="item"
        />
    </Head>
</template>
