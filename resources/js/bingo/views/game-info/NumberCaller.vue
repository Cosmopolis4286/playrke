 <!-- NumberCaller.vue -->
<template>
    <div>
        <div class="input-group mt-3" style="max-width: 200px;">
            <input type="text" class="form-control" :value="localInput" autofocus @input="onInput"
                @keydown.enter="tryCallNumber" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                placeholder="1 - 75" />
            <button @click="tryCallNumber" class="btn btn-primary" :disabled="!isValidNumber || isNumberCalled">
                Llamar número
            </button>
        </div>

        <div v-if="localInput !== '' && !isValidNumber" class="text-danger mt-1">
            Número debe estar entre 1 y 75.
        </div>
        <div v-else-if="isNumberCalled" class="text-warning mt-1">
            El número ya fue llamado.
        </div>
    </div>
</template>

<script setup>
import { ref, computed, defineEmits, defineProps } from 'vue'

const props = defineProps({
    calledNumbers: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['callNumber'])

const localInput = ref('')

const onInput = (event) => {
    let val = event.target.value
    // Eliminar todo lo que no sea dígito
    val = val.replace(/\D/g, '')

    // Limitar a 2 dígitos max
    if (val.length > 2) {
        val = val.slice(0, 2)
    }

    // Convertir a número para validar rango
    const num = Number(val)
    if (num > 75) {
        val = '75'
    }

    localInput.value = val
}

const isValidNumber = computed(() => {
    const num = Number(localInput.value)
    return localInput.value !== '' && Number.isInteger(num) && num >= 1 && num <= 75
})

const isNumberCalled = computed(() => {
    const num = Number(localInput.value)
    return isValidNumber.value && props.calledNumbers.includes(num)
})

function tryCallNumber() {
    if (!isValidNumber.value || isNumberCalled.value) return
    emit('callNumber', Number(localInput.value))
    localInput.value = ''
}
</script>
