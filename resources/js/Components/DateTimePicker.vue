<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { CalendarDays, ChevronLeft, ChevronRight, Clock } from '@lucide/vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    placeholder: {
        type: String,
        default: 'Pilih tanggal dan jam',
    },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const pickerRoot = ref(null)
const calendarMonth = ref(monthStart(parseValue(props.modelValue) || new Date()))

watch(
    () => props.modelValue,
    (value) => {
        const parsed = parseValue(value)

        if (parsed) {
            calendarMonth.value = monthStart(parsed)
        }
    },
)

const selectedDate = computed(() => parseValue(props.modelValue))

const selectedTime = computed(() => {
    if (!selectedDate.value) {
        return '09:00'
    }

    return `${pad(selectedDate.value.getHours())}:${pad(selectedDate.value.getMinutes())}`
})

const monthLabel = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        month: 'long',
        year: 'numeric',
    }).format(calendarMonth.value)
})

const displayValue = computed(() => {
    if (!selectedDate.value) {
        return ''
    }

    const datePart = new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(selectedDate.value)

    const timePart = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        hourCycle: 'h23',
    }).format(selectedDate.value)

    return `${datePart}, ${timePart}`
})

const calendarDays = computed(() => {
    const year = calendarMonth.value.getFullYear()
    const month = calendarMonth.value.getMonth()
    const firstDay = new Date(year, month, 1)
    const startOffset = (firstDay.getDay() + 6) % 7
    const daysInMonth = new Date(year, month + 1, 0).getDate()
    const days = []

    for (let index = 0; index < startOffset; index += 1) {
        days.push(null)
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        days.push(new Date(year, month, day))
    }

    return days
})

const weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
const hourOptions = Array.from({ length: 24 }, (_, index) => pad(index))
const minuteOptions = Array.from({ length: 60 }, (_, index) => pad(index))

const selectedHour = computed(() => selectedTime.value.split(':')[0])
const selectedMinute = computed(() => selectedTime.value.split(':')[1])

function pad(value) {
    return String(value).padStart(2, '0')
}

function parseValue(value) {
    if (!value) {
        return null
    }

    const parsed = new Date(value)

    if (Number.isNaN(parsed.getTime())) {
        return null
    }

    return parsed
}

function monthStart(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1)
}

function inputValue(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function setDate(date) {
    const [hours, minutes] = selectedTime.value.split(':').map(Number)
    const nextDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), hours, minutes)

    emit('update:modelValue', inputValue(nextDate))
}

function setTimeParts(hours, minutes) {
    const baseDate = selectedDate.value || new Date()
    const nextDate = new Date(baseDate.getFullYear(), baseDate.getMonth(), baseDate.getDate(), hours, minutes)

    emit('update:modelValue', inputValue(nextDate))
}

function setHour(event) {
    setTimeParts(Number(event.target.value), Number(selectedMinute.value))
}

function setMinute(event) {
    setTimeParts(Number(selectedHour.value), Number(event.target.value))
}

function previousMonth() {
    calendarMonth.value = new Date(calendarMonth.value.getFullYear(), calendarMonth.value.getMonth() - 1, 1)
}

function nextMonth() {
    calendarMonth.value = new Date(calendarMonth.value.getFullYear(), calendarMonth.value.getMonth() + 1, 1)
}

function isSelected(date) {
    if (!date || !selectedDate.value) {
        return false
    }

    return date.toDateString() === selectedDate.value.toDateString()
}

function isToday(date) {
    if (!date) {
        return false
    }

    return date.toDateString() === new Date().toDateString()
}

function closeWhenClickOutside(event) {
    if (!pickerRoot.value || pickerRoot.value.contains(event.target)) {
        return
    }

    isOpen.value = false
}

onMounted(() => {
    document.addEventListener('pointerdown', closeWhenClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', closeWhenClickOutside)
})
</script>

<template>
    <div ref="pickerRoot" class="relative grid min-w-0 gap-2">
        <span v-if="label" class="text-sm font-semibold text-diamond-text">{{ label }}</span>

        <button
            class="flex min-h-12 w-full min-w-0 cursor-pointer items-center justify-between gap-3 rounded-xl border border-diamond-border bg-white px-4 py-3 text-left text-sm outline-none transition hover:bg-diamond-surface-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
            type="button"
            @click="isOpen = !isOpen"
        >
            <span class="flex min-w-0 items-center gap-3">
                <CalendarDays :size="18" class="shrink-0 text-diamond-muted" />
                <span class="truncate font-semibold" :class="displayValue ? 'text-diamond-text' : 'text-diamond-soft'">
                    {{ displayValue || placeholder }}
                </span>
            </span>
            <Clock :size="17" class="shrink-0 text-diamond-muted" />
        </button>

        <span v-if="error" class="text-sm text-diamond-danger">{{ error }}</span>

        <div
            v-if="isOpen"
            class="absolute left-1/2 top-[calc(100%+0.5rem)] z-[80] grid w-[min(22.5rem,calc(100vw-2rem))] max-w-none -translate-x-1/2 gap-4 rounded-3xl border border-diamond-border bg-white p-4 sm:left-0 sm:w-[22.5rem] sm:translate-x-0"
        >
            <div class="flex items-center justify-between gap-3">
                <button
                    class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-primary"
                    type="button"
                    @click="previousMonth"
                >
                    <ChevronLeft :size="18" />
                </button>
                <p class="text-sm font-bold text-diamond-text">{{ monthLabel }}</p>
                <button
                    class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-primary"
                    type="button"
                    @click="nextMonth"
                >
                    <ChevronRight :size="18" />
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center">
                <span v-for="weekday in weekdayLabels" :key="weekday" class="py-1 text-[11px] font-bold text-diamond-muted">
                    {{ weekday }}
                </span>
                <span v-for="(date, index) in calendarDays" :key="date?.toISOString() || `empty-${index}`" class="aspect-square">
                    <button
                        v-if="date"
                        class="flex h-full w-full cursor-pointer items-center justify-center rounded-2xl text-sm font-bold transition"
                        :class="[
                            isSelected(date)
                                ? 'bg-diamond-primary text-white'
                                : isToday(date)
                                    ? 'bg-diamond-primary-soft text-diamond-primary'
                                    : 'text-diamond-text hover:bg-diamond-surface-soft',
                        ]"
                        type="button"
                        @click="setDate(date)"
                    >
                        {{ date.getDate() }}
                    </button>
                </span>
            </div>

            <div class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Jam</span>
                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
                    <select
                        class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-center text-sm font-bold text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        :value="selectedHour"
                        @change="setHour"
                    >
                        <option v-for="hour in hourOptions" :key="hour" :value="hour">{{ hour }}</option>
                    </select>
                    <span class="text-lg font-bold text-diamond-muted">:</span>
                    <select
                        class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-center text-sm font-bold text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        :value="selectedMinute"
                        @change="setMinute"
                    >
                        <option v-for="minute in minuteOptions" :key="minute" :value="minute">{{ minute }}</option>
                    </select>
                </div>
            </div>

            <button
                class="min-h-11 cursor-pointer rounded-2xl bg-diamond-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-diamond-primary/90"
                type="button"
                @click="isOpen = false"
            >
                Selesai
            </button>
        </div>
    </div>
</template>
