import { z } from 'zod'

/* ─── Schema Zod (estructura del formulario serializable) ────────────────── */

export const bingoCardGenerateSchema = z.object({
    selectedCountry: z.string({ error: 'Debe seleccionar un país.' }).min(1, 'Debe seleccionar un país.'),
    numCtns: z
        .number({ error: 'La cantidad de cartones es requerida.' })
        .int('Debe ser un número entero.')
        .min(1, 'Mínimo 1 cartón.')
        .max(40, 'Máximo 40 cartones.'),
    phone:     z.string({ error: 'El teléfono es requerido.' }).trim().min(1, 'El teléfono es requerido.'),
    firstName: z.string({ error: 'El nombre es requerido.' }).trim().min(1, 'El nombre es requerido.'),
    lastName:  z.string({ error: 'El apellido es requerido.' }).trim().min(1, 'El apellido es requerido.'),
    receiptNumber: z.string({ error: 'El número de comprobante es requerido.' }).trim().min(1, 'El número de comprobante es requerido.'),
    // z.instanceof(File) lanzaría ReferenceError en entornos sin File (SSR/test).
    // custom() evalúa File en tiempo de ejecución, no al importar el módulo.
    receiptImage: z.custom<File>(
        val => typeof File !== 'undefined' && val instanceof File,
        { message: 'El comprobante de pago es requerido.' }
    ),
})

export type BingoCardGenerateFormData = z.infer<typeof bingoCardGenerateSchema>

/* ─── Contexto externo que Zod no puede verificar ───────────────────────── */

export interface BingoCardGenerateContext {
    hasRoom:         boolean
    hasPriceLoaded:  boolean
    isLoadingPrice:  boolean
}

/* ─── Función de validación completa ────────────────────────────────────── */

export function validateBingoCardGenerate(
    data: Partial<{
        selectedCountry: string | null
        numCtns: number
        phone: string
        firstName: string
        lastName: string
        receiptNumber: string | null | undefined
        receiptImage: File | null | undefined
    }>,
    ctx: BingoCardGenerateContext
): boolean {
    if (!ctx.hasRoom || ctx.isLoadingPrice || !ctx.hasPriceLoaded) return false

    const result = bingoCardGenerateSchema.safeParse({
        selectedCountry: data.selectedCountry ?? '',
        numCtns:         data.numCtns ?? 0,
        phone:           data.phone ?? '',
        firstName:       data.firstName ?? '',
        lastName:        data.lastName ?? '',
        receiptNumber:   data.receiptNumber ?? '',
        receiptImage:    data.receiptImage,
    })

    return result.success
}
