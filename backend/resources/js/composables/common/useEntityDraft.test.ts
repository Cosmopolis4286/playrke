import { describe, it, expect, beforeEach } from 'vitest'
import { defineComponent, h, nextTick, ref } from 'vue'
import type { Ref } from 'vue'
import { mount } from '@vue/test-utils'
import { useEntityDraft } from './useFormDraft'

interface TestForm {
    name: string
}

function mountEntityDraft(
    initial: TestForm,
    opts: { entity: string; scope?: string; id: string | number }
) {
    let api!: ReturnType<typeof useEntityDraft<TestForm>>
    let formRef!: Ref<TestForm>
    const wrapper = mount(
        defineComponent({
            setup() {
                formRef = ref(initial) as Ref<TestForm>
                api = useEntityDraft(formRef, opts)
                return () => h('div')
            }
        })
    )
    return { wrapper, api, get form() { return formRef } }
}

describe('useEntityDraft', () => {
    beforeEach(() => {
        localStorage.clear()
    })

    it('deriva la storageKey de (entity, scope, id) — dos entidades distintas no colisionan', async () => {
        localStorage.setItem('form-draft:currency:default:create', JSON.stringify({
            v: 1, savedAt: new Date().toISOString(), data: { name: 'Draft de Currency' }
        }))

        const { api: currencyApi, wrapper: w1 } = mountEntityDraft({ name: '' }, { entity: 'currency', scope: 'default', id: 'create' })
        await nextTick()
        expect(currencyApi.hasDraft.value).toBe(true)
        w1.unmount()

        const { api: directoryApi, wrapper: w2 } = mountEntityDraft({ name: '' }, { entity: 'directory-room', id: 'create' })
        await nextTick()
        // Ninguna clave guardada para 'directory-room' -> no hay draft, aunque exista uno para 'currency'
        expect(directoryApi.hasDraft.value).toBe(false)
        w2.unmount()
    })

    it('omite el segmento scope de la clave cuando no se pasa', async () => {
        const { form, wrapper } = mountEntityDraft({ name: '' }, { entity: 'directory-room', id: 'create' })
        await nextTick()

        form.value = { name: 'Editado' }
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 900))

        expect(localStorage.getItem('form-draft:directory-room:create')).not.toBeNull()
        wrapper.unmount()
    })

    it('distingue entre id de creación y de edición para la misma entidad', async () => {
        localStorage.setItem('form-draft:currency:edit-42', JSON.stringify({
            v: 1, savedAt: new Date().toISOString(), data: { name: 'Editando moneda 42' }
        }))

        const { api: createApi, wrapper: w1 } = mountEntityDraft({ name: '' }, { entity: 'currency', id: 'create' })
        await nextTick()
        expect(createApi.hasDraft.value).toBe(false)
        w1.unmount()

        const { api: editApi, wrapper: w2 } = mountEntityDraft({ name: '' }, { entity: 'currency', id: 'edit-42' })
        await nextTick()
        expect(editApi.hasDraft.value).toBe(true)
        w2.unmount()
    })
})
