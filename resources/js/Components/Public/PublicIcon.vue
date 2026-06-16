<script setup>
import { computed } from 'vue'
import {
    IconApps,
    IconArrowLeft,
    IconBrandWhatsapp,
    IconCheck,
    IconChevronDown,
    IconClockHour3,
    IconDiamond,
    IconDots,
    IconHanger,
    IconHeart,
    IconMapPin,
    IconMenu2,
    IconPackage,
    IconPhoto,
    IconSearch,
    IconShieldCheck,
    IconShoe,
    IconShirt,
    IconSparkles,
    IconTie,
    IconX,
} from '@tabler/icons-vue'

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    size: {
        type: Number,
        default: 24,
    },
    strokeWidth: {
        type: [Number, String],
        default: 1.8,
    },
})

const icons = {
    'apps': IconApps,
    'arrow-left': IconArrowLeft,
    'brand-whatsapp': IconBrandWhatsapp,
    'check': IconCheck,
    'chevron-down': IconChevronDown,
    'clock': IconClockHour3,
    'diamond': IconDiamond,
    'dots': IconDots,
    'hanger': IconHanger,
    'heart': IconHeart,
    'map-pin': IconMapPin,
    'menu-2': IconMenu2,
    'package': IconPackage,
    'photo': IconPhoto,
    'search': IconSearch,
    'shield-check': IconShieldCheck,
    'shoe': IconShoe,
    'shirt': IconShirt,
    'sparkles': IconSparkles,
    'tie': IconTie,
    'x': IconX,
}

const iconComponent = computed(() => icons[props.name] ?? IconSearch)
</script>

<template>
    <component :is="iconComponent" :size="size" :stroke-width="strokeWidth" />
</template>
