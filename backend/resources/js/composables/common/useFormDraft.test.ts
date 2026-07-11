import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent, h, nextTick, ref } from 'vue'
import type { Ref } from 'vue'
import { mount } from '@vue/test-utils'
import { useFormDraft } from './useFormDraft'

interface TestForm {
    name: string
    count: number
}

const STORAGE_KEY = 'test-draft-key'

/** Monta un componente host para que onMounted/onUnmounted del composable se disparen,
 *  y expone tanto la API del composable como el ref del form para mutarlo en los tests. */
function mountDraft(initial: TestForm, options?: Parameters<typeof useFormDraft>[2]) {
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
})
