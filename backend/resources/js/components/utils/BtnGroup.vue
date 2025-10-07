<template>
  <div class="btn-group" role="group" aria-label="Botones dinámicos">
    <button v-for="(btn, index) in buttons" :key="index" type="button" class="btn btn-sm"
      :class="[btn.color, `btn-${name}`]" :data-type="btn.dataType" :data-id="dataId"
      @click="$emit('action', { type: btn.dataType, id: dataId })">
      <i :class="btn.icon"></i> {{ btn.title || '' }}
    </button>
  </div>
</template>

<script setup lang="ts">
/**
 * Interfaz para definir un botón del grupo
 */
interface ButtonConfig {
  color: string;
  icon: string;
  dataType: string;
  title?: string;
}

/**
 * Interfaz para el evento emitido al hacer click
 */
interface ActionEvent {
  type: string;
  id: string | number;
}

/**
 * Props del componente BtnGroup
 */
interface Props {
  buttons: ButtonConfig[];
  name: string;
  dataId: string | number;
}

defineProps<Props>();

/**
 * Eventos emitidos por el componente
 */
defineEmits<{
  action: [event: ActionEvent];
}>();
</script>
