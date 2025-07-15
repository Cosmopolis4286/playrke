<template>
  <div class="btn-group" role="group" aria-label="Botones dinámicos">
    <button v-for="(btn, index) in buttons" :key="index" type="button" class="btn btn-sm"
      :class="[btn.color, `btn-${name}`]" :data-type="btn.dataType" :data-id="dataId"
      @click="$emit('action', { type: btn.dataType, id: dataId })">
      <i :class="btn.icon"></i> {{ btn.title || '' }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  buttons: {
    type: Array,
    required: true,
    // Ejemplo esperado: [{ color: 'btn-primary', icon: 'fa fa-copy', dataType: 'copy', title: 'Copiar' }]
  },
  name: {
    type: String,
    required: true
  },
  dataId: {
    type: [String, Number],
    required: true
  }
})

// También puedes usar defineEmits si deseas emitir un evento personalizado:
defineEmits(['action'])
</script>
