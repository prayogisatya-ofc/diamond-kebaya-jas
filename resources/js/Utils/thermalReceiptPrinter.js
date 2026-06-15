function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

function formatDate(value) {
    if (!value) {
        return '-'
    }

    const parsedDate = new Date(value)
    const datePart = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
    }).format(parsedDate)
    const timePart = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        hourCycle: 'h23',
    }).format(parsedDate)

    return `${datePart} ${timePart}`
}

function statusLabel(value) {
    return {
        booked: 'Booking',
        picked_up: 'Diambil',
        returned: 'Kembali',
        completed: 'Selesai',
        overdue: 'Terlambat',
        cancelled: 'Batal',
        unpaid: 'Belum bayar',
        dp: 'DP',
        paid: 'Lunas',
        overpaid: 'Lebih bayar',
    }[value] || value || '-'
}

function paymentTypeLabel(value) {
    return {
        dp: 'DP',
        pelunasan: 'Lunas',
        denda: 'Denda',
        refund: 'Refund',
        adjustment: 'Adjust',
    }[value] || value || '-'
}

function paymentMethodLabel(value) {
    return {
        cash: 'Cash',
        transfer: 'Transfer',
        qris: 'QRIS',
        debit: 'Debit',
        other: 'Lainnya',
    }[value] || value || '-'
}

const bluetoothPrinterTargets = [
    {
        service: 'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
        characteristic: 'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f',
    },
    {
        service: '49535343-fe7d-4ae5-8fa9-9fafd205e455',
        characteristic: '49535343-8841-43f4-a8d4-ecbe34729bb3',
    },
    {
        service: '49535343-fe7d-4ae5-8fa9-9fafd205e455',
        characteristic: '49535343-aca3-481c-91ec-d85e28a60318',
    },
]
const bluetoothPrinterServices = [...new Set(bluetoothPrinterTargets.map((target) => target.service))]
const serialBaudRates = [9600, 19200, 38400, 57600, 115200]

function normalizePrinterText(value) {
    return String(value ?? '')
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\x20-\x7E\n\r]/g, '')
}

function padRight(value, width) {
    const text = normalizePrinterText(value).slice(0, width)

    return text.padEnd(width, ' ')
}

function line(left, right = '', width = 32) {
    const leftText = normalizePrinterText(left)
    const rightText = normalizePrinterText(right)
    const availableLeftWidth = Math.max(1, width - rightText.length)

    return `${padRight(leftText, availableLeftWidth)}${rightText}\n`
}

function wrapText(value, width = 32) {
    const words = normalizePrinterText(value).split(/\s+/).filter(Boolean)
    const lines = []
    let currentLine = ''

    words.forEach((word) => {
        if ((currentLine.length + word.length + 1) > width) {
            if (currentLine) {
                lines.push(currentLine)
            }

            currentLine = word
            return
        }

        currentLine = currentLine ? `${currentLine} ${word}` : word
    })

    if (currentLine) {
        lines.push(currentLine)
    }

    return lines.length ? lines.join('\n') : ''
}

function centerText(value, width = 32) {
    const text = normalizePrinterText(value).slice(0, width)
    const padding = Math.max(0, Math.floor((width - text.length) / 2))

    return `${' '.repeat(padding)}${text}\n`
}

function printerCenteredWrappedText(value, width = 32) {
    return wrapText(value, width)
        .split('\n')
        .filter(Boolean)
        .map((text) => `${text}\n`)
        .join('')
}

export function buildRentalEscPosReceipt({ store, rental, items, payments }) {
    const width = 32
    const separator = `${'-'.repeat(width)}\n`
    const feedAndCut = '\n\n\n\x1D\x56\x00'
    let text = '\x1B\x40'

    text += '\x1B\x61\x01'
    text += printerCenteredWrappedText(store.name, width)
    text += printerCenteredWrappedText(store.address, width)
    text += printerCenteredWrappedText(`WA: ${store.whatsapp_number}`, width)
    text += '\x1B\x61\x00'
    text += separator
    text += line('No', rental.invoice_number, width)
    text += line('Tgl', formatDate(rental.created_at), width)
    text += line('Customer', rental.customer?.name || '-', width)
    text += line('Ambil', formatDate(rental.pickup_at), width)
    text += line('Kembali', formatDate(rental.return_due_at), width)
    text += line('Status', `${statusLabel(rental.status)} / ${statusLabel(rental.payment_status)}`, width)
    text += separator
    text += centerText('ITEM RENTAL', width)

    items.forEach((item) => {
        const itemName = item.variant_name_snapshot
            ? `${item.item_name_snapshot} (${item.variant_name_snapshot})`
            : item.item_name_snapshot

        text += `${wrapText(itemName, width)}\n`

        text += line(`${item.quantity} x ${formatMoney(item.unit_price)}`, formatMoney(item.final_price), width)
    })

    if (!items.length) {
        text += centerText('Belum ada item', width)
    }

    text += separator
    text += line('Subtotal', formatMoney(rental.subtotal_amount), width)

    if (Number(rental.custom_adjustment_amount || 0) !== 0) {
        text += line('Adjustment', formatMoney(rental.custom_adjustment_amount), width)
    }

    if (Number(rental.penalty_amount || 0) > 0) {
        text += line(`Denda ${Number(rental.penalty_days || 0)} hari`, formatMoney(rental.penalty_amount), width)
    }

    text += line('TOTAL', formatMoney(rental.total_amount), width)
    text += line('Dibayar', formatMoney(rental.paid_amount), width)
    text += line('Sisa', formatMoney(rental.remaining_amount), width)

    if (payments.length) {
        text += separator
        text += centerText('PEMBAYARAN', width)

        payments.forEach((payment) => {
            text += line(
                `${paymentTypeLabel(payment.payment_type)} ${paymentMethodLabel(payment.payment_method)}`,
                formatMoney(payment.amount),
                width,
            )
        })
    }

    text += separator
    text += '\x1B\x61\x01'
    text += printerCenteredWrappedText(store.footer_note, width)
    text += printerCenteredWrappedText('Terima kasih', width)
    text += feedAndCut

    return new TextEncoder().encode(text)
}

export function canUseSerialPrinter() {
    return typeof navigator !== 'undefined' && 'serial' in navigator
}

export function canUseBluetoothPrinter() {
    return typeof navigator !== 'undefined' && 'bluetooth' in navigator
}

function delay(ms) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms)
    })
}

async function writeBluetoothReceipt(characteristic, receiptBytes) {
    const chunkSize = 20

    for (let offset = 0; offset < receiptBytes.length; offset += chunkSize) {
        const chunk = receiptBytes.slice(offset, offset + chunkSize)

        if (typeof characteristic.writeValueWithoutResponse === 'function') {
            await characteristic.writeValueWithoutResponse(chunk)
        } else if (typeof characteristic.writeValueWithResponse === 'function') {
            await characteristic.writeValueWithResponse(chunk)
        } else {
            await characteristic.writeValue(chunk)
        }

        await delay(8)
    }
}

async function findWritableBluetoothCharacteristic(server, statusCallback) {
    let lastError = null

    for (const target of bluetoothPrinterTargets) {
        try {
            statusCallback('Mencari channel printer BLE...')
            const service = await server.getPrimaryService(target.service)
            const characteristic = await service.getCharacteristic(target.characteristic)

            return characteristic
        } catch (error) {
            lastError = error
        }
    }

    throw lastError || new Error('Channel Bluetooth printer tidak ditemukan.')
}

async function openSerialPort(port, statusCallback) {
    let lastError = null

    for (const baudRate of serialBaudRates) {
        try {
            statusCallback(`Membuka koneksi serial ${baudRate} bps...`)
            await port.open({ baudRate })

            return
        } catch (error) {
            lastError = error

            if (port?.readable || port?.writable) {
                await port.close().catch(() => {})
            }
        }
    }

    const error = new Error('Gagal membuka port serial. Pastikan printer tidak sedang dipakai aplikasi lain, pilih port Bluetooth serial Woya/RPP02N yang benar, lalu coba matikan-nyalakan printer atau pair ulang.')
    error.cause = lastError

    throw error
}

async function printRentalThermalReceiptViaSerial(payload, statusCallback) {
    let port = null
    let writer = null

    try {
        statusCallback('Pilih printer Woya yang sudah di-pair...')
        port = await navigator.serial.requestPort()

        await openSerialPort(port, statusCallback)

        writer = port.writable.getWriter()
        statusCallback('Mengirim data struk...')
        await writer.write(buildRentalEscPosReceipt(payload))
        statusCallback('Struk berhasil dikirim ke printer.')
    } finally {
        if (writer) {
            writer.releaseLock()
        }

        if (port?.readable || port?.writable) {
            await port.close().catch(() => {})
        }
    }
}

async function printRentalThermalReceiptViaBluetooth(payload, statusCallback) {
    let device = null

    try {
        statusCallback('Pilih printer Woya/RPP02N dari daftar Bluetooth...')
        device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: bluetoothPrinterServices,
        })

        statusCallback('Menghubungkan Bluetooth BLE...')
        const server = await device.gatt.connect()
        const characteristic = await findWritableBluetoothCharacteristic(server, statusCallback)

        statusCallback('Mengirim data struk via Bluetooth...')
        await writeBluetoothReceipt(characteristic, buildRentalEscPosReceipt(payload))
        statusCallback('Struk berhasil dikirim ke printer.')
    } finally {
        if (device?.gatt?.connected) {
            device.gatt.disconnect()
        }
    }
}

export async function printRentalThermalReceipt(payload, statusCallback = () => {}) {
    if (canUseSerialPrinter()) {
        try {
            await printRentalThermalReceiptViaSerial(payload, statusCallback)

            return
        } catch (error) {
            if (!canUseBluetoothPrinter() || error?.name === 'NotFoundError') {
                throw error
            }

            statusCallback('Print serial gagal, mencoba Bluetooth BLE...')
        }
    }

    if (canUseBluetoothPrinter()) {
        await printRentalThermalReceiptViaBluetooth(payload, statusCallback)

        return
    }

    throw new Error('Browser ini belum mendukung Web Serial atau Web Bluetooth. Gunakan Chrome desktop/Android dengan HTTPS atau localhost.')
}
