import {
    ArrowDownToLine,
    BarChart3,
    Home,
    Package,
    Settings,
    ShoppingBag,
    Tag,
    Users,
    UserRound,
} from '@lucide/vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Label kategori per prefix route. Dipakai sidebar (jika perlu) dan breadcrumb.
 */
const sectionLabels = {
    dashboard: 'Dashboard',
    rentals: 'Rental',
    products: 'Produk',
    'product-categories': 'Kategori',
    'product-variants': 'Varian',
    'rental-packages': 'Paket',
    customers: 'Customer',
    expenses: 'Pengeluaran',
    reports: 'Laporan',
    users: 'User',
    settings: 'Setting',
    profile: 'Profil',
}

/**
 * Label aksi (segmen kedua route) untuk breadcrumb.
 */
const actionLabels = {
    create: 'Tambah',
    edit: 'Edit',
    show: 'Detail',
    transactions: 'Transaksi',
    payments: 'Pembayaran',
    'rented-products': 'Produk Disewa',
    variants: 'Varian',
}

export function useNavigation() {
    const page = usePage()
    const isOwner = computed(() => page.props.auth?.user?.role === 'owner')

    const navigation = computed(() => {
        const items = [
            { label: 'Dashboard', route: 'dashboard', icon: Home },
            { label: 'Rental', route: 'rentals.index', icon: ShoppingBag },
            { label: 'Produk', route: 'products.index', icon: Package },
            { label: 'Paket', route: 'rental-packages.index', icon: Tag },
            { label: 'Customer', route: 'customers.index', icon: Users },
            { label: 'Pengeluaran', route: 'expenses.index', icon: ArrowDownToLine },
            { label: 'Laporan', route: 'reports.transactions', icon: BarChart3 },
        ]

        if (isOwner.value) {
            items.push(
                { label: 'User', route: 'users.index', icon: UserRound },
                { label: 'Setting', route: 'settings.edit', icon: Settings },
            )
        }

        return items
    })

    function isActive(item) {
        if (item.route === 'dashboard') {
            return route().current('dashboard')
        }

        const prefix = item.route.split('.')[0]

        return route().current(`${prefix}.*`)
    }

    /**
     * Bangun breadcrumb dari nama route aktif, contoh: rentals.show -> Home › Rental › Detail.
     */
    const breadcrumbs = computed(() => {
        const current = route().current() || 'dashboard'
        const trail = [{ label: 'Home', route: 'dashboard' }]

        if (current === 'dashboard') {
            return trail
        }

        const [section, action] = current.split('.')
        const sectionLabel = sectionLabels[section]

        if (sectionLabel) {
            const sectionRoute = section === 'reports'
                ? 'reports.transactions'
                : `${section}.index`

            trail.push({
                label: sectionLabel,
                route: routeExists(sectionRoute) ? sectionRoute : null,
            })
        }

        const actionLabel = actionLabels[action]

        if (actionLabel) {
            trail.push({ label: actionLabel, route: null })
        }

        return trail
    })

    return {
        navigation,
        breadcrumbs,
        isActive,
        isOwner,
        sectionLabels,
    }
}

function routeExists(name) {
    try {
        return Boolean(route().has(name))
    } catch {
        return false
    }
}
