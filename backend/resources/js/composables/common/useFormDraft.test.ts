import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent, h, nextTick, ref, watch } from 'vue'
import type { Ref } from 'vue'
import { mount } from '@vue/test-utils'
import { useFormDraft } from './useFormDraft'
import type { UseFormDraftOptions } from './useFormDraft'

interface TestForm {
    name: string
    count: number
}

const STORAGE_KEY = 'test-draft-key'

/** Monta un componente host para que onMounted/onUnmounted del composable se disparen,
 *  y expone tanto la API del composable como el ref del form para mutarlo en los tests. */
function mountDraft(initial: TestForm, options?: UseFormDraftOptions<TestForm>) {
    let api!: ReturnType<typeof useFormDraft<TestForm>>
    let formRef!: Ref<TestForm>
    const wrapper = mount(
        defineComponent({
            setup() {
                formRef = ref(initial) as Ref<TestForm>
                api = useFormDraft(formRef, STORAGE_KEY, options)
                return () => h('div')
            }
        })
    )
    return { wrapper, api, get form() { return formRef } }
}

/**
 * Variante de mountDraft que además registra, DESPUÉS de useFormDraft (mismo
 * orden que BingoRoomForm.vue: useBingoRoomForm() -> useBingoRoomDraft() ->
 * watchers de sincronización), un watch con `immediate: true` que muta el
 * form — replica el patrón real que causaba el bug (ver useRoomRounds.ts,
 * sincroniza roundsConfig a la cantidad de rondas apenas se monta el form).
 */
function mountDraftWithImmediateSync(initial: TestForm, options?: UseFormDraftOptions<TestForm>) {
    let api!: ReturnType<typeof useFormDraft<TestForm>>
    let formRef!: Ref<TestForm>
    const wrapper = mount(
        defineComponent({
            setup() {
                formRef = ref(initial) as Ref<TestForm>
                api = useFormDraft(formRef, STORAGE_KEY, options)
                // Simula un watcher del formulario real que auto-completa/normaliza
                // un campo apenas se monta, sin ninguna interacción del usuario.
                watch(
                    () => formRef.value.count,
                    () => { formRef.value = { ...formRef.value, name: formRef.value.name || 'auto-filled' } },
                    { immediate: true }
                )
                return () => h('div')
            }
        })
    )
    return { wrapper, api, get form() { return formRef } }
}

describe('useFormDraft', () => {
    beforeEach(() => {
        localStorage.clear()
        vi.useRealTimers()
    })

    it('no reporta borrador cuando localStorage está vacío', async () => {
        const { api, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()
        expect(api.hasDraft.value).toBe(false)
        wrapper.unmount()
    })

    it('detecta un borrador guardado que difiere de los datos iniciales', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Sala Vieja', count: 5 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()

        expect(api.hasDraft.value).toBe(true)
        wrapper.unmount()
    })

    it('ignora (y borra) un draft huérfano idéntico a los datos iniciales', async () => {
        const initial = { name: 'Sala X', count: 1 }
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: initial
        }))

        const { api, wrapper } = mountDraft(initial)
        await nextTick()

        expect(api.hasDraft.value).toBe(false)
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        wrapper.unmount()
    })

    it('descarta un draft con versión de envelope distinta', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 999,
            savedAt: new Date().toISOString(),
            data: { name: 'Otra', count: 9 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()

        expect(api.hasDraft.value).toBe(false)
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        wrapper.unmount()
    })

    it('descarta un draft más viejo que el TTL configurado', async () => {
        const eightDaysAgo = new Date(Date.now() - 8 * 24 * 60 * 60 * 1000).toISOString()
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: eightDaysAgo,
            data: { name: 'Vieja', count: 2 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 }, { ttlMs: 24 * 60 * 60 * 1000 })
        await nextTick()

        expect(api.hasDraft.value).toBe(false)
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        wrapper.unmount()
    })

    it('conserva un draft dentro del TTL configurado', async () => {
        const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString()
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: oneHourAgo,
            data: { name: 'Reciente', count: 3 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 }, { ttlMs: 24 * 60 * 60 * 1000 })
        await nextTick()

        expect(api.hasDraft.value).toBe(true)
        wrapper.unmount()
    })

    it('guarda el form en localStorage tras el debounce, y no antes', async () => {
        vi.useFakeTimers()
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 500 })
        await nextTick()

        form.value = { name: 'Editado por el usuario', count: 1 }
        await nextTick()

        expect(api.draftStatus.value).toBe('saving')
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()

        await vi.advanceTimersByTimeAsync(500)

        expect(api.draftStatus.value).toBe('saved')
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.data).toEqual({ name: 'Editado por el usuario', count: 1 })

        wrapper.unmount()
        vi.useRealTimers()
    })

    it('restoreDraft aplica los datos guardados al form y oculta el banner', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Restaurada', count: 7 }
        }))

        const { api, form, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()

        expect(api.hasDraft.value).toBe(true)
        const restored = api.restoreDraft()

        expect(restored).toBe(true)
        expect(form.value).toEqual({ name: 'Restaurada', count: 7 })
        expect(api.hasDraft.value).toBe(false)
        wrapper.unmount()
    })

    it('discardDraft limpia localStorage y el estado del banner', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Para borrar', count: 1 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()
        expect(api.hasDraft.value).toBe(true)

        api.discardDraft()

        expect(api.hasDraft.value).toBe(false)
        expect(api.draftSavedAt.value).toBeNull()
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        wrapper.unmount()
    })

    it('isDraftStale es true sin draft guardado, y false cuando el form difiere del draft', async () => {
        const { api: apiNoDraft, wrapper: w1 } = mountDraft({ name: '', count: 0 })
        await nextTick()
        expect(apiNoDraft.isDraftStale()).toBe(true)
        w1.unmount()

        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Guardado', count: 4 }
        }))
        const { api: apiWithDraft, wrapper: w2 } = mountDraft({ name: 'distinto', count: 0 })
        await nextTick()

        expect(apiWithDraft.hasDraft.value).toBe(true)
        expect(apiWithDraft.isDraftStale()).toBe(false)
        w2.unmount()
    })

    it('isDraftStale es true una vez que el form coincide con el draft restaurado', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Guardado', count: 4 }
        }))
        const { api, wrapper } = mountDraft({ name: 'distinto', count: 0 })
        await nextTick()

        api.restoreDraft()

        expect(api.isDraftStale()).toBe(true)
        wrapper.unmount()
    })

    it('peekDraft retorna null si el envelope está corrupto', async () => {
        localStorage.setItem(STORAGE_KEY, '{ esto no es json válido')

        const { api, wrapper } = mountDraft({ name: '', count: 0 })
        await nextTick()

        expect(api.hasDraft.value).toBe(false)
        expect(api.peekDraft()).toBeNull()
        wrapper.unmount()
    })

    it('descarta un draft guardado con una versión de esquema distinta a la configurada', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Shape viejo', count: 1 }
        }))

        const { api, wrapper } = mountDraft({ name: '', count: 0 }, { version: 2 })
        await nextTick()

        expect(api.hasDraft.value).toBe(false)
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        wrapper.unmount()
    })

    it('guarda y detecta correctamente usando una versión de esquema no-default', async () => {
        vi.useFakeTimers()
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { version: 3, debounceMs: 500 })
        await nextTick()

        form.value = { name: 'Con version 3', count: 9 }
        await nextTick()
        await vi.advanceTimersByTimeAsync(500)

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.v).toBe(3)
        wrapper.unmount()
        vi.useRealTimers()
    })

    it('sanitize repara el draft leído antes de exponerlo (peekDraft y restoreDraft)', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Con referencia muerta', count: -5 }
        }))

        const sanitize = (data: TestForm): TestForm => ({ ...data, count: Math.max(0, data.count) })
        const { api, form, wrapper } = mountDraft({ name: '', count: 0 }, { sanitize })
        await nextTick()

        expect(api.peekDraft()).toEqual({ name: 'Con referencia muerta', count: 0 })

        api.restoreDraft()
        expect(form.value.count).toBe(0)
        wrapper.unmount()
    })

    it('flush guarda inmediatamente el último valor pendiente sin esperar el debounce', async () => {
        vi.useFakeTimers()
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 5000 })
        await nextTick()

        form.value = { name: 'Cambio reciente sin flush', count: 1 }
        await nextTick()

        // Antes del debounce: nada persistido todavía
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()

        api.flush()

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.data).toEqual({ name: 'Cambio reciente sin flush', count: 1 })
        expect(api.draftStatus.value).toBe('saved')

        wrapper.unmount()
        vi.useRealTimers()
    })

    it('al desmontar (ej. modal cerrado) hace flush automático del cambio pendiente', async () => {
        vi.useFakeTimers()
        const { form, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 5000 })
        await nextTick()

        form.value = { name: 'Cerrado antes del debounce', count: 2 }
        await nextTick()

        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()

        wrapper.unmount()

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.data).toEqual({ name: 'Cerrado antes del debounce', count: 2 })
        vi.useRealTimers()
    })

    it('no guarda un draft fantasma cuando un watch immediate:true del propio formulario muta el form al montar, sin que el usuario haya tocado nada', async () => {
        // Regresión: formularios reales (ej. BingoRoomForm.vue) suelen tener
        // watchers con immediate:true que auto-completan/normalizan campos
        // apenas se montan (ej. generar la config de rondas según la cantidad
        // de rondas). Esa mutación ocurre durante el mismo setup(), antes de
        // que onMounted() fije snapshotLocked — sin el fix, disparaba igual
        // el autosave con debounce y dejaba un "borrador" guardado pese a que
        // el usuario nunca interactuó con el formulario. Abrir y cerrar el
        // modal sin cambiar nada terminaba mostrando "hay un borrador" en la
        // siguiente apertura.
        vi.useFakeTimers()
        const { api, wrapper } = mountDraftWithImmediateSync({ name: '', count: 0 }, { debounceMs: 500 })
        await nextTick()

        await vi.advanceTimersByTimeAsync(500)
        expect(api.draftStatus.value).not.toBe('saving')
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()

        wrapper.unmount()
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()

        vi.useRealTimers()
    })

    it('con confirmOverwrite: si el usuario confirma, arranca el autosave y persiste el cambio nuevo', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Borrador viejo', count: 5 }
        }))

        vi.useFakeTimers()
        const confirmOverwrite = vi.fn().mockResolvedValue(true)
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 500, confirmOverwrite })
        await nextTick()
        expect(api.hasDraft.value).toBe(true)

        form.value = { name: 'Editado sin restaurar', count: 1 }
        await nextTick()
        // confirmOverwrite es async: hay que dejar correr sus microtasks antes de que arme el autosave
        await Promise.resolve()
        await Promise.resolve()
        await nextTick()

        expect(confirmOverwrite).toHaveBeenCalledTimes(1)
        expect(api.hasDraft.value).toBe(false)

        await vi.advanceTimersByTimeAsync(500)
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.data).toEqual({ name: 'Editado sin restaurar', count: 1 })

        wrapper.unmount()
        vi.useRealTimers()
    })

    it('con confirmOverwrite: si el usuario cancela, revierte el cambio y deja el borrador guardado intacto', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Borrador que quiero conservar', count: 5 }
        }))

        vi.useFakeTimers()
        const confirmOverwrite = vi.fn().mockResolvedValue(false)
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 500, confirmOverwrite })
        await nextTick()
        expect(api.hasDraft.value).toBe(true)

        form.value = { name: 'Edité por error sin restaurar', count: 1 }
        await nextTick()
        await Promise.resolve()
        await Promise.resolve()
        await nextTick()

        expect(confirmOverwrite).toHaveBeenCalledTimes(1)
        // El form vuelve al estado previo al cambio cancelado
        expect(form.value).toEqual({ name: '', count: 0 })
        // El borrador guardado en localStorage sigue siendo el original, sin pisar
        expect(api.hasDraft.value).toBe(true)
        const stillSaved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(stillSaved.data).toEqual({ name: 'Borrador que quiero conservar', count: 5 })

        await vi.advanceTimersByTimeAsync(500)
        expect(api.draftStatus.value).not.toBe('saving')

        wrapper.unmount()
        vi.useRealTimers()
    })

    it('sin confirmOverwrite configurado, edita libremente y pisa el borrador viejo sin preguntar (comportamiento default)', async () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            v: 1,
            savedAt: new Date().toISOString(),
            data: { name: 'Borrador viejo', count: 5 }
        }))

        vi.useFakeTimers()
        const { form, api, wrapper } = mountDraft({ name: '', count: 0 }, { debounceMs: 500 })
        await nextTick()
        expect(api.hasDraft.value).toBe(true)

        form.value = { name: 'Editado sin preguntar', count: 1 }
        await nextTick()
        await vi.advanceTimersByTimeAsync(500)

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)!)
        expect(saved.data).toEqual({ name: 'Editado sin preguntar', count: 1 })

        wrapper.unmount()
        vi.useRealTimers()
    })
})
